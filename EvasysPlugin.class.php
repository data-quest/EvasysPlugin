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

class EvasysPlugin extends StudIPPlugin implements SystemPlugin, StandardPlugin, AdminCourseAction {

    public function __construct() {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            $nav = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "admin/index"));
            Navigation::addItem("/start/evasys", $nav);
            Navigation::addItem("/evasys", clone $nav);
            $nav = new AutoNavigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "admin/index"));
            Navigation::addItem("/evasys/courses", $nav);
        }

        /*if ($_SESSION['SessionSeminar'] && Navigation::hasItem("/course")) {
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
        }*/

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


    public function getIconNavigation($course_id, $last_visit, $user_id = null) {
        $evasys_seminars = EvaSysSeminar::findBySeminar($course_id);
        $activated = false;
        foreach ($evasys_seminars as $evasys_seminar) {
            if ($evasys_seminar['activated']) {
                $activated = true;
            }
        }
        if ($activated) {
            $tab = new AutoNavigation(_("Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
            $tab->setImage(Assets::image_path("icons/16/grey/evaluation"), array('title' => _("Evaluation")));
            $number = $evasys_seminar->getEvaluationStatus();
            if ($number > 0) {
                $tab->setImage(Assets::image_path("icons/16/red/evaluation"), array('title' => sprintf(_("%s neue Evaluation"), $number)));
            }
            return $tab;
        }
    }

    public function getTabNavigation($course_id) {
        $evasys_seminars = EvaSysSeminar::findBySeminar($course_id);
        $activated = false;
        foreach ($evasys_seminars as $evasys_seminar) {
            if ($evasys_seminar['activated']) {
                $activated = true;
            }
        }
        if ($activated) {
            $tab = new AutoNavigation(_("Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
            $tab->setImage(Assets::image_path("icons/16/white/evaluation"));
            return array('evasys' => $tab);
        }
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

    public function getAdminActionURL()
    {
        return $GLOBALS['perm']->have_perm("admin") ? PluginEngine::getURL($this, array(), "admin/upload_courses") : null;
    }

    public function useMultimode() {
        return _("EvaSys aktivieren");
    }

    public function getAdminCourseActionTemplate($course_id, $values = null, $semester = null) {
        $factory = new Flexi_TemplateFactory(__DIR__."/views");
        $template = $factory->open("admin/_admin_checkbox.php");
        $template->set_attribute("course_id", $course_id);
        $template->set_attribute("plugin", $this);
        return $template;
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
            $template->set_layout($GLOBALS['template_factory']->open($layout ? 'layouts/base' : null));
        }
        return $template;
    }
}