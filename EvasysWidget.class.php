<?php
/*
 *  Copyright (c) 2011-2018  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once __DIR__."/lib/EvasysSeminar.php";
require_once __DIR__."/lib/EvasysForm.php";
require_once __DIR__."/lib/EvasysCourseProfile.php";
require_once __DIR__."/lib/EvasysInstituteProfile.php";
require_once __DIR__."/lib/EvasysGlobalProfile.php";
require_once __DIR__."/lib/EvasysProfileSemtypeForm.php";
require_once __DIR__."/lib/EvasysMatching.php";

class EvasysWidget extends StudIPPlugin implements PortalPlugin
{
    function getPortalTemplate()
    {
        $statement = DBManager::get()->prepare("
            SELECT seminare.*
            FROM seminar_user
                INNER JOIN seminare ON (seminare.Seminar_id = seminar_user.Seminar_id)
                INNER JOIN evasys_course_profiles ON (evasys_course_profiles.seminar_id = seminare.Seminar_id)
            WHERE seminar_user.user_id = :user_id
                AND seminar_user.status = 'dozent'
                AND evasys_course_profiles.semester_id = :semester_id
                AND evasys_course_profiles.applied = '1'
                AND evasys_course_profiles.transferred = '1'
            GROUP BY seminare.Seminar_id
            ORDER BY seminare.name ASC
        ");
        $statement->execute([
            'user_id' => $GLOBALS['user']->id,
            'semester_id' => Semester::findCurrent()->id
        ]);
        $seminar_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        $active_seminar_ids = [];
        foreach ($seminar_ids as $seminar_id) {
            $profile = EvasysCourseProfile::findBySemester($seminar_id);
            if ($profile['applied'] && $profile['transferred'] && ($profile->getFinalBegin() < time()) && ($profile->getFinalEnd() >= time())) {
                $seminar = new EvasysSeminar($seminar_id);
                $exported_id = $seminar->getExportedId();
                if ($profile['split']) {
                    $active_seminar_ids[$exported_id.$GLOBALS['user']->id] = $seminar_id;
                } else {
                    $active_seminar_ids[$exported_id] = $seminar_id;
                }
            }
        }



        $user = User::findCurrent();

        $courses = [];

        //user GetCoursesByUserId soap method? Need integer user_id of evasys for dozent
        if (count($active_seminar_ids)) {
            $soap = EvasysSoap::get();

            if (!$_SESSION['EVASYS_MY_IDS']) {
                //fetch user_id for dozent GetUser
                $evasys_user_object = $soap->soapCall("GetUserIdsByParams", [
                    'Params' => ['Email' => $user['email']]
                ]);
                if (is_a($evasys_user_object, "SoapFault")) {
                    PageLayout::postError("SOAP-error: " . $evasys_user_object->getMessage());
                    return;
                } else {
                    $ids = array_values((array)$evasys_user_object->Strings);
                    $_SESSION['EVASYS_MY_IDS'] = $ids;
                }
            } else {
                $ids = $_SESSION['EVASYS_MY_IDS'];
            }

            $evasys_surveys_object = $soap->soapCall("GetSurveyIDsByParams", [
                'Params' => [
                    'Instructors' => ["Strings" => $ids],
                    'Name' => "%",
                    'ExtendedResponseAsJSON' => true
                ]]
            );
            if (is_a($evasys_surveys_object, "SoapFault")) {
                PageLayout::postError("SOAP-error: " . $evasys_surveys_object->getMessage());
                return;
            }
            foreach ($evasys_surveys_object->Strings as $json) {
                $json = json_decode($json, true);
                if ($active_seminar_ids[$json['CourseCode']]
                        && (!$courses[$active_seminar_ids[$json['CourseCode']]] || $json['OpenState'])) {
                    $course = Course::find($active_seminar_ids[$json['CourseCode']]);
                    $courses[$active_seminar_ids[$json['CourseCode']]] = [
                        'Nummer' => $course['veranstaltungsnummer'],
                        'Name' => $course['name'],
                        'Seminar_id' => $active_seminar_ids[$json['CourseCode']],
                        'split' => (strlen($json['CourseCode']) > 32),
                        'ResponseCount' => $json['ResponseCount'],
                        'ParticipantCount' => $json['ParticipantCount'],
                        'open' => $json['OpenState']
                    ];
                }
            }


            //iterate through courses and filter for active surveys
        }

        uasort($courses, function ($a, $b) {
            return strcasecmp($a['Name'], $b['Name']);
        });

        $tf = new Flexi_TemplateFactory(__DIR__ . "/views");
        $widget = $tf->open("widget/widget");
        $widget->title = dgettext("evasys", "Lehrveranstaltungsevaluationen");
        $widget->courses = $courses;
        //$widget->surveys = $surveys;
        $widget->plugin = $this;
        return $widget;
    }
}
