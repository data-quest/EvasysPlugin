<?php
/*
 *  Copyright (c) 2011  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

if (file_exists('lib/classes/Semester.class.php')) {
    include_once 'lib/classes/Semester.class.php';
}
if (file_exists('lib/models/Semester.class.php')) {
    include_once 'lib/models/Semester.class.php';
}
require_once dirname(__file__)."/classes/EvaSysSeminar.class.php";
require_once 'lib/classes/QuickSearch.class.php';

class EvasysPlugin extends StudIPPlugin implements SystemPlugin, StandardPlugin {

    public function __construct() {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            $nav = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "admin"));
            Navigation::addItem("/start/evasys", $nav);
            Navigation::addItem("/evasys", clone $nav);
            $nav = new AutoNavigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "admin"));
            Navigation::addItem("/evasys/courses", $nav);
        }
        if ($_SESSION['SessionSeminar'] && Navigation::hasItem("/course")) {
            $evasys_seminars = EvaSysSeminar::findBySeminar($_SESSION['SessionSeminar']);
            $activated = false;
            foreach ($evasys_seminars as $evasys_seminar) {
                if ($evasys_seminar['activated']) {
                    $activated = true;
                }
            }
            if ($activated) {
                $tab = new AutoNavigation(_("Evaluation"), PluginEngine::getLink($this, array(), "show"));
                $tab->setImage(Assets::image_path("icons/16/white/vote.png"));
                Navigation::addItem("/course/evasys", $tab);
            }
        }

        //Infofenster für Server-Angaben:
        if ($GLOBALS['perm']->have_perm("root") && (strpos($_SERVER['REQUEST_URI'], "dispatch.php/plugin_admin") || strpos($_SERVER['REQUEST_URI'], "dispatch.php/admin/plugin")) ) {
            if (count($_POST) && Request::get("EVASYS_WSDL") && Request::get("EVASYS_USER") && Request::get("EVASYS_PASSWORD")) {
                write_config("EVASYS_WSDL", Request::get("EVASYS_WSDL"));
                write_config("EVASYS_URI", Request::get("EVASYS_URI"));
                write_config("EVASYS_USER", Request::get("EVASYS_USER"));
                write_config("EVASYS_PASSWORD", Request::get("EVASYS_PASSWORD"));
                write_config("EVASYS_CACHE", Request::int("EVASYS_CACHE"));
                write_config("EVASYS_PUBLISH_RESULTS", Request::int("EVASYS_PUBLISH_RESULTS"));
            }

            $EVASYS_WSDL = get_config("EVASYS_WSDL");
            $EVASYS_USER = get_config("EVASYS_USER");
            $EVASYS_PASSWORD = get_config("EVASYS_PASSWORD");
            $EVASYS_URI = get_config("EVASYS_URI");
            $EVASYS_CACHE = get_config("EVASYS_CACHE");
            if (!$EVASYS_WSDL || !$EVASYS_USER || !$EVASYS_PASSWORD || !$EVASYS_URI || $EVASYS_CACHE === "" || $EVASYS_CACHE === null) {
                PageLayout::addBodyElements($this->getTemplate("settings_window.php", null)->render());
            }
        }
    }

    public function admin_action() {
        $db = DBManager::get();
        $pageCount = 20;
        $msg = array();

        if (Request::submitted("absenden")) {
            $activate = Request::getArray("activate");
            $evasys_seminar = array();
            foreach (Request::getArray("course") as $course_id) {
                $evasys_evaluations = EvaSysSeminar::findBySeminar($course_id);
                if (count($evasys_evaluations)) {
                    foreach ($evasys_evaluations as $evaluation) {
                        $evaluation['activated'] = $activate[$course_id] ? 1 : 0;
                        if (!$evaluation['activated']) {
                            $evaluation->store();
                            unset($evasys_seminar[$course_id]);
                        } else {
                            $evasys_seminar[$course_id] = $evaluation;
                        }
                    }
                } else {
                    $evasys_seminar[$course_id] = new EvaSysSeminar(array($course_id, ""));
                    $evasys_seminar[$course_id]['activated'] = $activate[$course_id] ? 1 : 0;
                }
            }
            if (count($evasys_seminar) > 0) {
                $success = EvaSysSeminar::UploadSessions($evasys_seminar);
                if ($success === true) {
                    foreach (Request::getArray("course") as $course_id) {
                        if (isset($evasys_seminar[$course_id])) {
                            $evasys_seminar[$course_id]->store();
                        }
                    }
                    $msg[] = array("success", sprintf("%s Veranstaltungen mit EvaSys synchronisiert.", count($activate)));
                } else {
                    $msg[] = array("error", "Fehler beim Synchronisieren mit EvaSys. ".$success);
                }
            } else {
                $msg[] = array("info", "Veranstaltungen abgewählt. Keine Synchronisation erfolgt.");
            }
        }

        if (Request::get("inst") || Request::get("sem_type") || Request::get("sem_name") || Request::get("semester") || Request::get("sem_dozent")) {
            if (Request::get("semester")) {
                $semester = Semester::find(Request::option("semester"));
                $sem_condition = "AND seminare.start_time <=".$semester["beginn"]."
                                AND (".$semester["beginn"]." <= (seminare.start_time + seminare.duration_time)
                                OR seminare.duration_time = -1)";
            }
            if (Request::get("inst")) {
                $inst_condition = "AND seminar_inst.institut_id = ".$db->quote(Request::get("inst"))." ";
            }

            if (Request::get("sem_type")) {
                $sem_type_condition = "AND seminare.status = ".$db->quote(Request::get("sem_type"))." ";
            }
            if (Request::get("sem_dozent")) {
                $dozent_condition = "AND seminar_user.user_id = ".$db->quote(Request::get("sem_dozent"))." AND seminar_user.status = 'dozent' ";
            }
            if (Request::get("sem_name")) {
                $name_condition = "AND CONCAT_WS(' ', seminare.VeranstaltungsNummer, seminare.Name) LIKE ".$db->quote('%'.Request::get("sem_name").'%')." ";
            }

            $courses = $db->query(
                "SELECT seminare.Name, seminare.Seminar_id, seminare.VeranstaltungsNummer, IFNULL(evasys_seminar.activated, 0) AS activated, seminare.start_time, seminare.duration_time, evasys_seminar.evasys_id, GROUP_CONCAT(seminar_user.user_id ORDER BY seminar_user.position ASC SEPARATOR '_') AS dozenten " .
                "FROM seminare " .
                    "LEFT JOIN evasys_seminar ON (evasys_seminar.Seminar_id = seminare.Seminar_id) " .
                    ($inst_condition ? "INNER JOIN seminar_inst ON (seminar_inst.seminar_id = seminare.Seminar_id) " : "") .
                    "INNER JOIN seminar_user ON (seminar_user.Seminar_id = seminare.Seminar_id AND seminar_user.status = 'dozent') " .
                "WHERE TRUE  " .
                    ($sem_condition ? $sem_condition: "") .
                    ($inst_condition ? $inst_condition : "") .
                    ($sem_type_condition ? $sem_type_condition : "") .
                    ($name_condition ? $name_condition : "") .
                    ($dozent_condition ? $dozent_condition : "") .
                "GROUP BY seminare.Seminar_id " .
                "ORDER BY Name ASC " .
                //"LIMIT ".(Request::get('page') ? Request::int('page') * $pageCount : 0).", ".addslashes($pageCount + 1)." " .
            "")->fetchAll(PDO::FETCH_ASSOC);
            /*if (count($courses) > $pageCount) {
                array_pop($courses);
                $nextPage = true;
            }*/
            $searched = true;
        } else {
            $searched = false;
        }


        $institute = $db->query(
            "SELECT i2.* " .
            "FROM Institute AS i1 " .
                "INNER JOIN Institute AS i2 ON (i2.fakultaets_id = i1.Institut_id) " .
            "ORDER BY i1.Name ASC, i2.Name " .
        "")->fetchAll(PDO::FETCH_ASSOC);

        /*$bad_courses = $db->query(
            "SELECT SUM( c )
            FROM (
                SELECT count( * ) AS c
                FROM `seminare`
                WHERE VeranstaltungsNummer <> ''
                GROUP BY VeranstaltungsNummer
                HAVING count( * ) > 1
            ) AS a" .
        "")->fetch(PDO::FETCH_COLUMN, 0);*/

        $template = $this->getTemplate("courses.php");
        $template->set_attribute("nextPage", (bool) $nextPage);
        $template->set_attribute("courses", $courses);
        $template->set_attribute('institute', $institute);
        $template->set_attribute('bad_courses', (int) $bad_courses);
        $template->set_attribute('searched', $searched);
        $template->set_attribute('msg', $msg);
        echo $template->render();
    }

    public function show_action() {
        $tab = Navigation::getItem("/course/evasys");
        $tab->setImage(Assets::image_path("icons/16/black/vote.png"));

        $evasys_seminars = EvaSysSeminar::findBySeminar($_SESSION['SessionSeminar']);
        $surveys = array();
        $open_surveys = array();
        $active = array();
        $user_can_participate = array();
        $publish = false;
        if (Request::get("dozent_vote")) {
            foreach ($evasys_seminars as $evasys_seminar) {
                $evasys_seminar->vote(Request::get("dozent_vote") === "y");
            }
        }
        foreach ($evasys_seminars as $evasys_seminar) {
            $survey_information = $evasys_seminar->getSurveyInformation();
            $publish = $publish || $evasys_seminar->publishingAllowed();
            if (is_array($survey_information)) {
                foreach ($survey_information as $info) {
                    $surveys[] = $info;
                    if ($info->m_nState > 0) {
                        $active[] = count($surveys) - 1;
                    }
                }
            }
        }
        if (count($evasys_seminars)
                && count($active)
                && !$GLOBALS['perm']->have_studip_perm("dozent", $_SESSION['SessionSeminar'])) {
            $open_surveys = $evasys_seminars[0]->getSurveys($GLOBALS['user']->id);
            if (is_array($open_surveys)) {
                foreach ($open_surveys as $one) {
                    if (is_object($one)) {
                        $user_can_participate[] = count($open_surveys) - 1;
                        break;
                    }
                }
            }
        }
        if ($GLOBALS['perm']->have_studip_perm("dozent", $_SESSION['SessionSeminar'])
                || (count($active) > 0 && $publish && !count($open_surveys))) {
            $template = $this->getTemplate("survey_dozent.php", "base_with_infobox");
            $template->set_attribute('surveys', $surveys);
            $template->set_attribute('evasys_seminar', $evasys_seminar);
            $template->set_attribute('plugin', $this);
        } else {
            $template = $this->getTemplate("surveys_student.php");
            $template->set_attribute('open_surveys', $open_surveys);
            if ($user_can_participate) {
                unset($_SESSION['EVASYS_SEMINAR_SURVEYS'][$_SESSION['SessionSeminar']]);
            }
        }
        echo $template->render();
    }

    public function getIconNavigation($course_id, $last_visit, $user_id = null) {
        return null;
    }

    public function getTabNavigation($course_id) {
        return null;
    }

    public function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }

    public function getInfoTemplate($course_id) {
        return null;
    }

    protected function getDisplayName() {
        if (Navigation::hasItem("/course") && Navigation::getItem("/course")->isActive()) {
            return $GLOBALS['SessSemName'][0].": "._("Evaluation");
        } else {
            return _("EvaSys-Plugin aktivieren");
        }
    }

    protected function getTemplate($template_file_name, $layout = "without_infobox") {
        if (!$this->template_factory) {
            $this->template_factory = new Flexi_TemplateFactory(dirname(__file__)."/templates");
        }
        $template = $this->template_factory->open($template_file_name);
        if ($layout) {
            if (method_exists($this, "getDisplayName")) {
                PageLayout::setTitle($this->getDisplayName());
            } else {
                PageLayout::setTitle(get_class($this));
            }
            $template->set_layout($GLOBALS['template_factory']->open($layout === "without_infobox" ? 'layouts/base_without_infobox' : 'layouts/base'));
        }
        return $template;
    }
}