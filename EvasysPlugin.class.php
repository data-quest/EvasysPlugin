<?php
/*
 *  Copyright (c) 2011-2018  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once __DIR__."/classes/EvasysSeminar.php";
require_once __DIR__."/classes/EvasysForm.php";
require_once __DIR__."/classes/EvasysCourseProfile.php";
require_once __DIR__."/classes/EvasysInstituteProfile.php";
require_once __DIR__."/classes/EvasysGlobalProfile.php";
require_once __DIR__."/classes/EvasysProfileSemtypeForm.php";

class EvasysPlugin extends StudIPPlugin implements SystemPlugin, StandardPlugin, AdminCourseAction
{

    public function useLowerPermissionLevels()
    {
        return (bool) Config::get()->EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS;
    }

    public function __construct()
    {
        parent::__construct();
        
        //The user must be root
        if ($this->isRoot()) {
            $nav = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), Config::get()->EVASYS_ENABLE_PROFILES ? "globalprofile" : "forms/index"));
            Navigation::addItem("/admin/evasys", $nav);
            if (Config::get()->EVASYS_ENABLE_PROFILES) {
                $nav = new Navigation(_("Standardwerte"), PluginEngine::getURL($this, array(), "globalprofile"));
                Navigation::addItem("/admin/evasys/globalprofile", clone $nav);
                $nav = new Navigation(_("Standardwerte der Institute"), PluginEngine::getURL($this, array(), "instituteprofile"));
                Navigation::addItem("/admin/evasys/instituteprofile", clone $nav);
                $nav = new Navigation(_("Fragebögen"), PluginEngine::getURL($this, array(), "forms/index"));
                Navigation::addItem("/admin/evasys/forms", clone $nav);
            }
            $nav = new Navigation(_("Matching Veranstaltungstypen"), PluginEngine::getURL($this, array(), "admin/matching_seminartypes"));
            Navigation::addItem("/admin/evasys/matchingtypes", clone $nav);
            $nav = new Navigation(_("Matching Einrichtungen"), PluginEngine::getURL($this, array(), "admin/matching_institutes"));
            Navigation::addItem("/admin/evasys/matchinginstitutes", clone $nav);
            $nav = new Navigation(_("Begrifflichkeiten"), PluginEngine::getURL($this, array(), "admin/wording"));
            Navigation::addItem("/admin/evasys/wording", clone $nav);
        }
    }


    public function getIconNavigation($course_id, $last_visit, $user_id = null) {
        $evasys_seminars = EvasysSeminar::findBySeminar($course_id);
        $activated = false;
        foreach ($evasys_seminars as $evasys_seminar) {
            if ($evasys_seminar['activated']) {
                $activated = true;
            }
        }
        if ($activated) {
            $tab = new AutoNavigation(_("Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
            $tab->setImage(Icon::create("evaluation", "inactive"), array('title' => _("Evaluationen")));
            $number = $evasys_seminar->getEvaluationStatus();
            if ($number > 0) {
                $tab->setImage(Icon::create("evaluation", "new"), array('title' => sprintf(_("%s neue Evaluation"), $number)));
            }
            return $tab;
        }
    }

    public function getTabNavigation($course_id) {
        $evasys_seminars = EvasysSeminar::findBySeminar($course_id);
        $activated = false;
        foreach ($evasys_seminars as $evasys_seminar) {
            if ($evasys_seminar['activated']) {
                $activated = true;
            }
        }
        if ($activated) {
            $tab = new AutoNavigation(_("Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
            $tab->setImage(Icon::create("evaluation", "info_alt"));
            return array('evasys' => $tab);
        }
    }

    public function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }

    public function getInfoTemplate($course_id) {
        return null;
    }

    public function getDisplayName() {
        if (Navigation::hasItem("/course") && Navigation::getItem("/course")->isActive()) {
            return $GLOBALS['SessSemName'][0].": "._("Evaluation");
        } else {
            return _("Evasys");
        }
    }

    public function getAdminActionURL()
    {
        return $GLOBALS['perm']->have_perm("admin") ? PluginEngine::getURL($this, array(), "admin/upload_courses") : null;
    }

    public function useMultimode() {
        return _("Evasys aktivieren");
    }

    public function getAdminCourseActionTemplate($course_id, $values = null, $semester = null) {
        $factory = new Flexi_TemplateFactory(__DIR__."/views");
        $template = $factory->open("admin/_admin_checkbox.php");
        $template->set_attribute("course_id", $course_id);
        $template->set_attribute("plugin", $this);
        return $template;
    }

    public function isRoot()
    {
        return $GLOBALS['perm']->have_perm("root");
    }
}