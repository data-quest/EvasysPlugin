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
                $nav = new Navigation(sprintf(_("Standardwerte der %s"), EvasysMatching::wording("Einrichtungen")), PluginEngine::getURL($this, array(), "instituteprofile"));
                Navigation::addItem("/admin/evasys/instituteprofile", clone $nav);
                $nav = new Navigation(_("Fragebögen"), PluginEngine::getURL($this, array(), "forms/index"));
                Navigation::addItem("/admin/evasys/forms", clone $nav);
            }
            $nav = new Navigation(_("Matching Veranstaltungstypen"), PluginEngine::getURL($this, array(), "matching/seminartypes"));
            Navigation::addItem("/admin/evasys/matchingtypes", clone $nav);
            $nav = new Navigation(_("Matching Einrichtungen"), PluginEngine::getURL($this, array(), "matching/institutes"));
            Navigation::addItem("/admin/evasys/matchinginstitutes", clone $nav);
            $nav = new Navigation(_("Begrifflichkeiten"), PluginEngine::getURL($this, array(), "matching/wording"));
            Navigation::addItem("/admin/evasys/wording", clone $nav);
        } elseif ($this->isAdmin() && Config::get()->EVASYS_ENABLE_PROFILES && Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS) {
            $nav = new Navigation(_("Standard-Evaluationsprofil"), PluginEngine::getURL($this, array(), "instituteprofile"));
            Navigation::addItem("/admin/institute/instituteprofile", $nav);
        }

        if (Config::get()->EVASYS_ENABLE_PROFILES
                && ((stripos($_SERVER['REQUEST_URI'], "dispatch.php/admin/courses") !== false) || (stripos($_SERVER['REQUEST_URI'], "plugins.php/evasysplugin/profile/bulkedit") !== false))
                && ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")) {
            $this->addStylesheet("assets/evasys.less");
            PageLayout::addScript($this->getPluginURL()."/assets/insert_button.js");
            NotificationCenter::addObserver($this, "addNonfittingDatesFilterToSidebar", "SidebarWillRender");
        }
        NotificationCenter::addObserver($this, "addNonfittingDatesFilter", "AdminCourseFilterWillQuery");
    }

    public function addNonfittingDatesFilterToSidebar()
    {
        $widget = new OptionsWidget();
        $widget->setTitle(_("Zeiten im Evaluationszeitraum"));
        $widget->addCheckbox(
            _("Nur Veranstaltungen, die im Eval-Zeitraum keine Termine haben"),
            $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES"),
            PluginEngine::getURL($this, array(), "toggle_nonfittingdates_filter")
        );
        Sidebar::Get()->insertWidget($widget, "editmode", "filter_nonfittingdates");
    }

    /**
     * Toggle the filter in the sidebar of the admin-page and redirect there
     */
    public function toggle_nonfittingdates_filter_action()
    {
        $oldvalue = (bool) $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES");
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_NONFITTING_DATES", $oldvalue ? 0 : 1);
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addNonfittingDatesFilter($event, $filter)
    {
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                'join' => "INNER JOIN",
                'on' => "
                seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                    AND evasys_course_profiles.semester_id = :evasys_semester_id
                "
            );
            $filter->settings['query']['joins']['evasys_institute_profiles'] = array(
                'join' => "LEFT JOIN",
                'on' => "evasys_institute_profiles.institut_id = seminare.Institut_id 
                    AND evasys_institute_profiles.semester_id = :evasys_semester_id"
            );
            $filter->settings['query']['joins']['evasys_fakultaet_profiles'] = array(
                'join' => "LEFT JOIN",
                'table' => "evasys_institute_profiles",
                'on' => "evasys_fakultaet_profiles.institut_id = Institute.fakultaets_id 
                    AND evasys_fakultaet_profiles.semester_id = :evasys_semester_id"
            );
            $filter->settings['query']['joins']['evasys_global_profiles'] = array(
                'join' => "LEFT JOIN",
                'on' => "evasys_global_profiles.semester_id = :evasys_semester_id"
            );
            $filter->settings['query']['joins']['termine'] = array(
                'join' => "LEFT JOIN",
                'on' => "
                    seminare.Seminar_id = termine.range_id
                    AND (
                        (termine.date >= IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.date < IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                        OR (termine.end_time > IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.end_time <= IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                        OR (termine.date < IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.end_time > IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                    )
                "
            );
            $filter->settings['query']['where']['date_not_in_timespan'] = "termine.termin_id IS NULL";
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
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
            return Context::get()->getHeaderLine().": "._("Evaluation");
        } else {
            return _("Evasys");
        }
    }

    public function getAdminActionURL()
    {
        return $this->isRoot() || $this->isAdmin()
            ? PluginEngine::getURL($this, array(), "admin/upload_courses")
            : null;
    }

    public function useMultimode() {
        return _("Übertragen");
    }

    public function getAdminCourseActionTemplate($course_id, $values = null, $semester = null) {
        $factory = new Flexi_TemplateFactory(__DIR__."/views");
        $template = $factory->open("admin/_admin_checkbox.php");
        $template->set_attribute("profile", EvasysCourseProfile::findOneBySQL("seminar_id = :seminar_id AND semester_id = :semester_id", array(
            'seminar_id' => $course_id,
            'semester_id' => Semester::findCurrent()->id
        )));
        $template->set_attribute("course_id", $course_id);
        $template->set_attribute("plugin", $this);
        return $template;
    }

    public function isRoot()
    {
        return $GLOBALS['perm']->have_perm("root");
    }

    public function isAdmin()
    {
        return $GLOBALS['perm']->have_perm("admin") && !$GLOBALS['perm']->have_perm("root");
    }
}