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
        if (!Config::get()->EVASYS_ENABLE_PROFILES) {
            $tf = new Flexi_TemplateFactory(__DIR__."/views");
            $widget = $tf->open("widget/nothing");
            $widget->title = _("Lehrevaluationen");
            return $widget;
        } else {





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
                    AND evasys_course_profiles.begin <= UNIX_TIMESTAMP()
                GROUP BY seminare.Seminar_id
                ORDER BY seminare.name ASC
            ");
            $statement->execute(array(
                'user_id' => $GLOBALS['user']->id,
                'semester_id' => Semester::findCurrent()->id
            ));
            $seminar_data = $statement->fetchAll(PDO::FETCH_ASSOC);
            $courses = array_map("Course::buildExisting", $seminar_data);

            //user GetCoursesByUserId soap method? Need integer user_id of evasys for dozent
            if (false && count($courses)) {



                //fetch user_id for dozent GetUser

                //GetSurveyIDsByParams

                //iterate through courses and filter for active surveys
            }

            $surveys = array();
            foreach ($courses as $course) {
                $evasys_seminars = EvasysSeminar::findBySeminar($course->getId());

                foreach ($evasys_seminars as $evasys_seminar) {
                    $survey_information = $evasys_seminar->getSurveyInformation();
                    if (is_array($survey_information)) {
                        foreach ($survey_information as $info) {
                            $surveys[$course->getId()][] = $info;
                        }
                    }
                }
            }

            $tf = new Flexi_TemplateFactory(__DIR__ . "/views");
            $widget = $tf->open("widget/widget");
            $widget->title = _("Lehrevaluationen");
            $widget->courses = $courses;
            $widget->surveys = $surveys;
            $widget->plugin = $this;
            return $widget;
        }
    }
}