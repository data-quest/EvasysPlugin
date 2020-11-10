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
require_once __DIR__."/lib/EvasysAdditionalField.php";
require_once __DIR__."/lib/EvasysSoapLog.php";

if (!interface_exists("AdminCourseContents")) {
    interface AdminCourseContents
    {
        public function adminAvailableContents();
        public function adminAreaGetCourseContent($course, $index);
    }
}

class EvasysPlugin extends StudIPPlugin implements SystemPlugin, StandardPlugin, AdminCourseAction, Loggable, AdminCourseContents
{

    static protected $ruecklauf = null;

    public static function useLowerPermissionLevels()
    {
        return (bool) Config::get()->EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS;
    }

    public function __construct()
    {
        bindtextdomain("evasys", __DIR__."/locale");
        parent::__construct();

        //The user must be root
        if (self::isRoot()) {
            $nav = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), Config::get()->EVASYS_ENABLE_PROFILES ? "globalprofile" : "forms/index"));
            Navigation::addItem("/admin/evasys", $nav);
            if (Config::get()->EVASYS_ENABLE_PROFILES) {
                $nav = new Navigation(dgettext("evasys", "Standardwerte"), PluginEngine::getURL($this, array(), "globalprofile"));
                Navigation::addItem("/admin/evasys/globalprofile", clone $nav);
                $nav = new Navigation(sprintf(dgettext("evasys", "Standardwerte der %s"), EvasysMatching::wording("Einrichtungen")), PluginEngine::getURL($this, array(), "instituteprofile"));
                Navigation::addItem("/admin/evasys/instituteprofile", clone $nav);
                $nav = new Navigation(dgettext("evasys", "Freie Felder"), PluginEngine::getURL($this, array(), "config/additionalfields"));
                Navigation::addItem("/admin/evasys/additionalfields", clone $nav);
                $nav = new Navigation(dgettext("evasys", "Fragebögen"), PluginEngine::getURL($this, array(), "forms/index"));
                Navigation::addItem("/admin/evasys/forms", clone $nav);
                $nav = new Navigation(ucfirst(EvasysMatching::wording("freiwillige Evaluationen")), PluginEngine::getURL($this, array(), "individual/list"));
                Navigation::addItem("/admin/evasys/individual", clone $nav);
                $nav = new Navigation(dgettext("evasys", "Logs"), PluginEngine::getURL($this, array(), "logs/index"));
                Navigation::addItem("/admin/evasys/logs", clone $nav);
            }
            $nav = new Navigation(dgettext("evasys", "Matching Veranstaltungstypen"), PluginEngine::getURL($this, array(), "matching/seminartypes"));
            Navigation::addItem("/admin/evasys/matchingtypes", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Matching Einrichtungen"), PluginEngine::getURL($this, array(), "matching/institutes"));
            Navigation::addItem("/admin/evasys/matchinginstitutes", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Begrifflichkeiten"), PluginEngine::getURL($this, array(), "matching/wording"));
            Navigation::addItem("/admin/evasys/wording", clone $nav);
        } elseif (Config::get()->EVASYS_ENABLE_PROFILES && Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS && Navigation::hasItem("/admin/institute")) {
            $nav = new Navigation(dgettext("evasys", "Standard-Evaluationsprofil"), PluginEngine::getURL($this, array(), "instituteprofile"));
            if (!self::isAdmin()) {
                $nav->setEnabled(false);
            }
            Navigation::addItem("/admin/institute/instituteprofile", $nav);
        }
        if (!Navigation::hasItem("/admin/evasys/individual") && Config::get()->EVASYS_ENABLE_PROFILES && RolePersistence::isAssignedRole($GLOBALS['user']->id, "Evasys-Admin")) {
            if (!Navigation::hasItem("/admin/evasys")) {
                $nav = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "individual/list"));
                Navigation::addItem("/admin/evasys", $nav);
            }
            $nav = new Navigation(ucfirst(EvasysMatching::wording("freiwillige Evaluationen")), PluginEngine::getURL($this, array(), "individual/list"));
            Navigation::addItem("/admin/evasys/individual", clone $nav);
        }

        if (Config::get()->EVASYS_ENABLE_PROFILES
                && ((stripos($_SERVER['REQUEST_URI'], "dispatch.php/admin/courses") !== false) || (stripos($_SERVER['REQUEST_URI'], "plugins.php/evasysplugin/profile/bulkedit") !== false))
                ) {
            $this->addStylesheet("assets/evasys.less");
            if ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin") {
                if ($GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION) && ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all")) {
                    PageLayout::addScript($this->getPluginURL() . "/assets/insert_button.js");
                }
            }
            PageLayout::addScript($this->getPluginURL() . "/assets/admin_area.js");
            NotificationCenter::addObserver($this, "addTransferredFilterToSidebar", "SidebarWillRender");
            NotificationCenter::addObserver($this, "addNonfittingDatesFilterToSidebar", "SidebarWillRender");
            NotificationCenter::addObserver($this, "addRecentEvalCoursesFilterToSidebar", "SidebarWillRender");
            NotificationCenter::addObserver($this, "addFormFilterToSidebar", "SidebarWillRender");
            NotificationCenter::addObserver($this, "addPaperOnlineFilterToSidebar", "SidebarWillRender");
            NotificationCenter::addObserver($this, "addMainphaseFilterToSidebar", "SidebarWillRender");
        }
        if (Config::get()->EVASYS_ENABLE_PROFILES && Navigation::hasItem("/course/admin")) {
            if (Navigation::hasItem("/course/admin/evaluation")) {
                $nav = Navigation::getItem("/course/admin/evaluation");
                $nav->setTitle(dgettext("evasys", "Eigene Evaluationen"));
            }

            $nav = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getURL($this, array(), "profile/edit/".Context::get()->id));
            $nav->setImage(Icon::create("checkbox-checked", "clickable"));
            $nav->setDescription(dgettext("evasys", "Beantragen Sie für diese Veranstaltung eine Lehrevaluation oder sehen Sie, ob eine Lehrevaluation für diese Veranstaltung vorgesehen ist."));
            Navigation::addItem("/course/admin/evasys", $nav);
        }
        if (Config::get()->EVASYS_ENABLE_PASSIVE_ACCOUNT && $GLOBALS['perm']->get_perm() === "dozent") {
            $tab = new Navigation(dgettext("evasys", "EvaSys"), PluginEngine::getURL($this, array(), "passiveaccount/index"));
            Navigation::addItem("/profile/evasyspassiveaccount", $tab);
        }
        NotificationCenter::addObserver($this, "addNonfittingDatesFilter", "AdminCourseFilterWillQuery");
        NotificationCenter::addObserver($this, "addRecentEvalCoursesFilter", "AdminCourseFilterWillQuery");
        NotificationCenter::addObserver($this, "addTransferredFilter", "AdminCourseFilterWillQuery");
        NotificationCenter::addObserver($this, "addFormFilter", "AdminCourseFilterWillQuery");
        NotificationCenter::addObserver($this, "addPaperOnlineFilter", "AdminCourseFilterWillQuery");
        NotificationCenter::addObserver($this, "addMainphaseFilter", "AdminCourseFilterWillQuery");
        NotificationCenter::addObserver($this, "removeEvasysCourse", "CourseDidDelete");
    }

    //Transferred Filter:

    public function addTransferredFilterToSidebar()
    {
        if ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin"
                || $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            $widget = new SelectWidget(dgettext("evasys", "Transfer-Filter"), PluginEngine::getURL($this, array(), "change_transferred_filter"), "transferstatus", "post");
            $widget->addElement(new SelectElement(
                '',
                ""
            ));
            $widget->addElement(new SelectElement(
                'applied',
                dgettext("evasys", "Beantragt"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "applied"
            ));
            $widget->addElement(new SelectElement(
                'notapplied',
                dgettext("evasys", "Nicht beantragt"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "notapplied"
            ));
            $widget->addElement(new SelectElement(
                'nottransferred',
                dgettext("evasys", "Beantragt und noch nicht übertragen"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "nottransferred"
            ));
            $widget->addElement(new SelectElement(
                'transferred',
                dgettext("evasys", "Beantragt und nach EvaSys übetragen"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "transferred"
            ));
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_transferred");
        }
    }

    public function change_transferred_filter_action()
    {
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERRED", Request::option("transferstatus"));
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addTransferredFilter($event, $filter)
    {
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            if ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                    'join' => "LEFT JOIN",
                    'on' => "
                seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                "
                );
            } else {
                $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                    'join' => "LEFT JOIN",
                    'on' => "
                    seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                        AND evasys_course_profiles.semester_id = :evasys_semester_id
                "
                );
                $filter->settings['parameter']['evasys_semester_id'] = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;
            }
            if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "transferred") {
                $filter->settings['query']['where']['evasys_transferred']
                    = "evasys_course_profiles.transferred = '1' ";
            } elseif($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "applied") {
                $filter->settings['query']['where']['evasys_transferred']
                    = "evasys_course_profiles.applied = '1'";
            } elseif($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "notapplied") {
                $filter->settings['query']['where']['evasys_transferred']
                    = "(evasys_course_profiles.applied = '0' OR evasys_course_profiles.applied IS NULL)";
            } elseif($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "nottransferred") {
                $filter->settings['query']['where']['evasys_transferred']
                    = "(evasys_course_profiles.applied = '1' AND evasys_course_profiles.transferred = '0')";
            }
        }
    }

    //Nonfitting-dates filter

    public function addNonfittingDatesFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
                || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES"))) {
            $widget = new OptionsWidget();
            $widget->setTitle(dgettext("evasys","Zeiten im Evaluationszeitraum"));
            $widget->addCheckbox(
                (dgettext("evasys","Nur Veranstaltungen, die im Eval-Zeitraum keine Termine haben")),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES"),
                PluginEngine::getURL($this, array(), "toggle_nonfittingdates_filter")
            );
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_nonfittingdates");
        }
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
                'join' => "LEFT JOIN",
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

    //Recent evaluations filter

    public function addRecentEvalCoursesFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
            || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_RECENT_EVAL_COURSES"))) {
            $widget = new OptionsWidget();
            $widget->setTitle(dgettext("evasys","Ausreißer-Filter"));
            $widget->addCheckbox(
                (dgettext("evasys","Ausreißer-Veranstaltungen der nächsten 7 Tage anzeigen.")),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_RECENT_EVAL_COURSES"),
                PluginEngine::getURL($this, array(), "toggle_recentevalcourses_filter")
            );
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_recentevalcourses");
        }
    }

    public function toggle_recentevalcourses_filter_action()
    {
        $oldvalue = (bool) $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_RECENT_EVAL_COURSES");
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_RECENT_EVAL_COURSES", $oldvalue ? 0 : 1);
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addRecentEvalCoursesFilter($event, $filter)
    {
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_RECENT_EVAL_COURSES")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                'join' => "LEFT JOIN",
                'on' => "
                seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                    AND evasys_course_profiles.semester_id = :evasys_semester_id
                "
            );
            $filter->settings['query']['where']['eval_starts_next_days'] = "evasys_course_profiles.applied = '1' AND evasys_course_profiles.begin IS NOT NULL AND evasys_course_profiles.begin > UNIX_TIMESTAMP() AND evasys_course_profiles.begin < UNIX_TIMESTAMP() + 86400 * 7 ";
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
    }

    //Form filter:

    public function addFormFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
            || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID"))) {
            $widget = new SelectWidget(dgettext("evasys","Fragebogen-Filter"), PluginEngine::getURL($this, array(), "change_form_filter"), "form_id", "post");
            $widget->addElement(new SelectElement(
                '',
                ""
            ));
            foreach (EvasysForm::findBySQL("`active` = '1' ORDER BY name ASC") as $form) {
                $widget->addElement(new SelectElement(
                    $form->getId(),
                    $form['name'],
                    $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID") === $form->getId(),
                    $form['description']
                ));
            }
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_form");
        }
    }

    public function change_form_filter_action()
    {
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_FORM_ID", Request::get("form_id"));
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addFormFilter($event, $filter)
    {
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                'join' => "LEFT JOIN",
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

            $filter->settings['query']['where']['evasys_form_filter'] = "IFNULL(evasys_course_profiles.form_id, IFNULL(evasys_institute_profiles.form_id, IFNULL(evasys_fakultaet_profiles.form_id, evasys_global_profiles.form_id))) = :evasys_form_id";
            $filter->settings['parameter']['evasys_form_id'] = $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID");
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
    }

    //Filter for online/paper evaluation

    public function addPaperOnlineFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
            || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID"))) {
            $widget = new SelectWidget(dgettext("evasys","Evaluationsart"), PluginEngine::getURL($this, array(), "change_paperonline_filter"), "paperonline", "post");
            $widget->addElement(new SelectElement(
                '',
                ""
            ));
            $widget->addElement(new SelectElement(
                "online",
                (dgettext("evasys","Online-Evaluationen")),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE") === "online"
            ));
            $widget->addElement(new SelectElement(
                "paper",
                (dgettext("evasys","Papier-Evaluationen")),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE") === "paper"
            ));
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_paperonline");
        }
    }

    public function change_paperonline_filter_action()
    {
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_PAPER_ONLINE", Request::get("paperonline"));
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addPaperOnlineFilter($event, $filter)
    {
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                'join' => "LEFT JOIN",
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

            $filter->settings['query']['where']['evasys_paperonline_filter'] = "IFNULL(evasys_course_profiles.mode, IFNULL(evasys_institute_profiles.mode, IFNULL(evasys_fakultaet_profiles.mode, evasys_global_profiles.mode))) = :evasys_mode";
            $filter->settings['parameter']['evasys_mode'] = $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE");
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
    }

    //Filter for main-phase

    public function addMainphaseFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
            || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE"))) {
            $widget = new SelectWidget(dgettext("evasys","Hauptphasen-Filter"), PluginEngine::getURL($this, array(), "change_mainphase_filter"), "mainphase", "post");
            $widget->addElement(new SelectElement(
                '',
                ""
            ));
            $widget->addElement(new SelectElement(
                "mainphase",
                (dgettext("evasys","Veranstaltungen in der Hauptphase")),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "mainphase"
            ));
            $widget->addElement(new SelectElement(
                "nonmainphase",
                (dgettext("evasys","Veranstaltungen außerhalb der Hauptphase")),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "nonmainphase"
            ));
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_mainphase");
        }
    }

    public function change_mainphase_filter_action()
    {
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_MAINPHASE", Request::get("mainphase"));
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addMainphaseFilter($event, $filter)
    {
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                'join' => "LEFT JOIN",
                'on' => "
                seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                    AND evasys_course_profiles.semester_id = :evasys_semester_id
                "
            );
            if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "nonmainphase") {
                $filter->settings['query']['where']['evasys_mainphase_filter'] = "evasys_course_profiles.`begin` IS NOT NULL";
            } elseif($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "mainphase") {
                $filter->settings['query']['where']['evasys_mainphase_filter'] = "evasys_course_profiles.`begin` IS NULL";
            }
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
    }

    public function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        $activated = false;
        $evasys_seminars = EvasysSeminar::findBySeminar($course_id);
        if (Config::get()->EVASYS_ENABLE_PROFILES) {
            $profile = EvasysCourseProfile::findBySemester($course_id);
            if ($profile['applied']
                && $profile['transferred']
                && ($profile->getFinalBegin() <= time())
                && ($profile->getFinalEnd() > time())) {
                $activated = true;
            }
        } else {
            foreach ($evasys_seminars as $evasys_seminar) {
                if ($evasys_seminar['activated']) {
                    $activated = true;
                }
            }
        }

        if ($activated) {
            $tab = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
            if ($profile && $profile['split']) {
                $tab->setURL(PluginEngine::getLink($this, array(), "evaluation/split"));
            }
            $tab->setImage(Icon::create("evaluation", "inactive"), array('title' => dgettext("evasys", "Lehrveranstaltungsevaluationen")));
            if (!Config::get()->EVASYS_NO_RED_ICONS) {
                if (Config::get()->EVASYS_RED_ICONS_STOP_UNTIL > time()) {
                    $tab->setImage(Icon::create("evaluation", "new"), array('title' => dgettext("evasys", "Neue Evaluation")));
                } else {
                    $number = 0;
                    foreach ($evasys_seminars as $evasys_seminar) {
                        $number += $evasys_seminar->getEvaluationStatus();
                    }
                    if ($number > 0) {
                        $tab->setImage(Icon::create("evaluation", "new"), ['title' => sprintf(dgettext("evasys", "%s neue Evaluation"), $number)]);
                    }
                }
            }
            return $tab;
        } elseif($profile && $profile['applied'] && $GLOBALS['perm']->have_studip_perm("dozent", $course_id)) {
            $tab = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getURL($this, array(), "profile/edit/".$course_id));
            $tab->setImage(Icon::create("evaluation", "inactive"), array('title' => dgettext("evasys", "Evaluationen")));
            return $tab;
        }
    }

    public function getTabNavigation($course_id)
    {
        $activated = false;
        if (Config::get()->EVASYS_ENABLE_PROFILES) {
            $profiles = EvasysCourseProfile::findBySQL("seminar_id = ?", array($course_id));
            foreach ($profiles as $profile) {
                if ($profile['applied']
                    && $profile['transferred']
                    && ($profile->getFinalBegin() <= time())) {
                    $activated = true;
                    break;
                }
            }
        } else {
            $evasys_seminars = EvasysSeminar::findBySeminar($course_id);
            foreach ($evasys_seminars as $evasys_seminar) {
                if ($evasys_seminar['activated']) {
                    $activated = true;
                    break;
                }
            }
        }
        if ($activated) {
            $tab = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
            if ($profile && $profile['split']) {
                $tab->setURL(PluginEngine::getLink($this, array(), "evaluation/split"));
            }
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
            return Context::getHeaderLine().": ".dgettext("evasys", "Evaluation");
        } else {
            return dgettext("evasys", "EvaSys");
        }
    }

    public function getAdminActionURL()
    {
        return $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION) && ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all")
            ? PluginEngine::getURL($this, array(), "admin/upload_courses")
            : PluginEngine::getURL($this, array(), "profile/bulkedit");
    }

    public function useMultimode()
    {
        return $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION) && ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all")
            ? dgettext("evasys", "Übertragen")
            : dgettext("evasys", "Bearbeiten");
    }

    public function getAdminCourseActionTemplate($course_id, $values = null, $semester = null)
    {
        $factory = new Flexi_TemplateFactory(__DIR__."/views");
        $template = $factory->open("admin/_admin_checkbox.php");
        if (Request::option("semester_id")) {
            $semester_id = Request::option("semester_id");
        } elseif($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE && $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all") {
            $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;
        }
        if ($semester_id) {
            $profiles = array(EvasysCourseProfile::findBySemester(
                $course_id,
                $semester_id
            ));
        } else {
            $profiles = EvasysCourseProfile::findBySQL("seminar_id = :course_id", array(
                'course_id' => $course_id
            ));
            //sort
            usort($profiles, function ($a, $b) {
                return $a->semester->beginn > $a->semester->beginn ? -1 : 1;
            });
        }
        $get_semesters = DBManager::get()->prepare("
            SELECT semester_data.*
            FROM semester_data
            INNER JOIN seminare ON (semester_data.beginn >= seminare.start_time AND (
                              seminare.duration_time = -1
                              OR (seminare.duration_time = 0 AND semester_data.beginn = seminare.start_time)
                              OR (seminare.start_time + seminare.duration_time >= semester_data.beginn)
                  ))
            WHERE seminare.Seminar_id = :seminar_id
            ORDER BY semester_data.beginn ASC
        ");
        $get_semesters->execute(array('seminar_id' => $course_id));
        $semesters = array();
        foreach ($get_semesters->fetchAll(PDO::FETCH_ASSOC) as $semester_data) {
            $semesters[] = Semester::buildExisting($semester_data);
        }
        $template->set_attribute("semesters", $semesters);
        $template->set_attribute("profiles", $profiles);
        $template->set_attribute("course_id", $course_id);
        $template->set_attribute("plugin", $this);
        return $template;
    }

    static public function isRoot()
    {
        return $GLOBALS['perm']->have_perm("root");
    }

    static public function isAdmin($seminar_id = null)
    {
        if ($GLOBALS['perm']->have_perm("root")) {
            return false;
        } elseif ($GLOBALS['perm']->have_perm("admin") && !Config::get()->EVASYS_ENABLE_PROFILES) {
            //for the case that we have no profiles and are admin:
            return true;
        } elseif ($seminar_id && $GLOBALS['perm']->have_studip_perm("admin", $seminar_id) && Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS) {
            $global_profile = EvasysGlobalProfile::findCurrent();
            if ($global_profile['adminedit_begin']) {
                return (time() >= $global_profile['adminedit_begin']) && (!$global_profile['adminedit_end'] || time() <= $global_profile['adminedit_end']);
            }
        } elseif ($GLOBALS['perm']->have_perm("admin") && Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS) {
            $global_profile = EvasysGlobalProfile::findCurrent();
            if ($global_profile['adminedit_begin']) {
                return (time() >= $global_profile['adminedit_begin']) && (!$global_profile['adminedit_end'] || time() <= $global_profile['adminedit_end']);
            }
        } elseif ($GLOBALS['perm']->have_perm("dozent") && Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS) {
            $statement = DBManager::get()->prepare("
                SELECT *
                FROM roles
                    INNER JOIN roles_user ON (roles_user.roleid = roles.roleid)
                WHERE rolename = 'Evasys-Dozent-Admin'
                    AND roles_user.userid = ?
            ");
            $statement->execute(array($GLOBALS['user']->id));
            $roles = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (!$seminar_id && !empty($roles)) {
                return true;
            } else {

                foreach ($roles as $role) {
                    if (!$role['institut_id']
                            || $role['institut_id'] === $course['institut_id']
                            || $role['institut_id'] === $course->institut['fakultaet_id']) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function perform($unconsumed_path)
    {
        $this->addStylesheet("assets/evasys.less");
        parent::perform($unconsumed_path);
    }

    public function adminAvailableContents() {
        $array = array(
            'form' => dgettext("evasys", "Fragebogen"),
            'mode' => dgettext("evasys", "Evaluationsart"),
            'timespan' => dgettext("evasys", "Eval-Zeitraum"),
            'applied' => dgettext("evasys", "Evaluation beantragt"),
            'lehrende_emails' => dgettext("evasys", 'Eval: Beantragte Lehrende (Emails)'),
            'ruecklauf' => dgettext("evasys", "Rückläufer")
        );
        if (Config::get()->EVASYS_ENABLE_SPLITTING_COURSES) {
            $array['split'] = dgettext("evasys", "Teilevaluation");
        }
        return $array;
    }

    public function adminAreaGetCourseContent($course, $index)
    {
        if (Request::option("semester_id")) {
            $semester_id = Request::option("semester_id");
        } elseif($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE && $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all") {
            $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;
        } else {
            $semester_id = $course->start_semester->getId();
        }
        $profile = EvasysCourseProfile::findBySemester($course->getId(), $semester_id);

        switch ($index) {
            case "form":
                if (!$profile || !$profile['applied']) {
                    return "";
                }

                $form_id = $profile->getFinalFormId();
                if ($form_id) {
                    $form = EvasysForm::find($form_id);
                    return $form['name'];
                } else {
                    return "";
                }
            case "mode":
                if (!$profile || !$profile['applied']) {
                    return "";
                }
                if (Config::get()->EVASYS_FORCE_ONLINE) {
                    return dgettext("evasys", "Online");
                }
                return $profile->getFinalMode() === "online" ? dgettext("evasys", "Online") : dgettext("evasys", "Papier");
            case "timespan":
                if (!$profile || !$profile['applied']) {
                    return "";
                }
                $begin = $profile->getFinalBegin();
                $end = $profile->getFinalEnd();
                return date("d.m.Y H:i", $begin)." - ".date("d.m.Y H:i", $end);
            case "applied":
                return $profile && $profile['applied'] ? 1 : 0;
            case "lehrende_emails":
                if (!$profile || !$profile['applied']) {
                    return "";
                }
                $emails = array();
                if ($profile['teachers']) {
                    foreach ((array) $profile['teachers']->getArrayCopy() as $teacher_id) {
                        $teacher = User::find($teacher_id);
                        if ($teacher) {
                            $emails[] = $teacher->email;
                        }
                    }
                }
                return implode(";", $emails);
            case "split":
                return $profile && $profile['split'] ? 1 : 0;
            case "ruecklauf":

                $active_seminar_ids = array();

                $profile;
                if (!$profile['applied'] || !$profile['transferred'] || $profile->getFinalBegin() >= time()) {
                    return ""; //nothing to show for this course
                }

                if ($profile['split']) {
                    foreach ($profile->teachers as $teacher_id) {
                        $active_seminar_ids[$profile['seminar_id'].$teacher_id] = $profile['seminar_id'];
                    }
                } else {
                    $active_seminar_ids[$profile['seminar_id']] = $profile['seminar_id'];
                }

                $results = array();

                $soap = EvasysSoap::get();

                foreach (array_keys($active_seminar_ids) as $course_code) {
                    $evasys_surveys_object = $soap->__soapCall("GetCourse", array(
                            'CourseId' => $course_code,
                            'IdType' => "PUBLIC",
                            'IncludeSurveys' => true,
                            'IncludeParticipants' => false
                        )
                    );
                    if (is_a($evasys_surveys_object, "SoapFault")) {
                        if ($evasys_surveys_object->getMessage() === "ERR_312") {
                            PageLayout::postError(sprintf(dgettext("evasys", "Veranstaltung %s existiert nicht mehr in EvaSys"), htmlReady($course['name'])));
                        } else {
                            PageLayout::postError("SOAP-error: " . $evasys_surveys_object->getMessage());
                        }
                        return;
                    }
                    //var_dump($evasys_surveys_object->m_oSurveyHolder->m_aSurveys->Surveys);
                    foreach ((array) $evasys_surveys_object->m_oSurveyHolder->m_aSurveys->Surveys as $survey_data) {
                        $count_forms = $survey_data->m_nFormCount;
                        $tans = $survey_data->m_nPswdCount;
                        $open = $survey_data->m_nOpenState;
                        $return = round(100 * $count_forms / ($tans ?: 1));
                        $color = $return >= 80 ? '#8bbd40' : ($return >= 30 ? '#a1aec7' : '#d60000');
                        $results[] = '<div style="background-image: linear-gradient(0deg, '. $color .', '. $color . '); background-repeat: no-repeat; background-size: ' . (int) $return .'% 100%;">'.htmlReady($count_forms . " / ". $tans).'</div>';
                    }
                }

                return implode("\n", $results);
        }
    }

    public static function logFormat(LogEvent $event)
    {
        $tmpl = $event->action->info_template;

        if (strpos($event->action->name, 'EVASYS') !== false) {
            $course = Course::find($event->coaffected_range_id);
            $semester = Semester::find($event->info);
            if ($course) {
                $url = PluginEngine::getURL('EvasysPlugin', array(), '/profile/edit/'
                    . $event->coaffected_range_id, true);
                $name = sprintf('<a data-dialog href="%s">%s - %s (%s)</a>',
                    $url, $course->veranstaltungsnummer,
                    $course->name, $semester->name);
                $tmpl = str_replace('%coaffected(%info)', $name, $tmpl);
            }
        }

        return $tmpl;
    }

    public static function logSearch($needle, $action_name = null)
    {
        $result = [];

        if (strpos($action_name, 'EVASYS') !== false) {
            $stmt = DBManager::get()->prepare("
                SELECT s.VeranstaltungsNummer, s.Name as coursename,
                    aum.user_id, aum.username, sd.name as semester
                FROM evasys_course_profiles
                    LEFT JOIN seminare s ON evasys_course_profiles.seminar_id = s.Seminar_id
                    LEFT JOIN auth_user_md5 aum USING (user_id)
                    LEFT JOIN semester_data sd USING (semester_id)
                WHERE s.Name LIKE CONCAT('%', :needle, '%')
                    OR s.VeranstaltungsNummer LIKE CONCAT('%', :needle, '%')
                    OR CONCAT_WS(' ', aum.username, aum.Vorname, aum.Nachname) LIKE CONCAT('%', :needle, '%')
                    OR CONCAT_WS(' ', sd.name, sd.semester_token) LIKE CONCAT('%', :needle, '%')
            ");
            $stmt->execute([':needle' => $needle]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result[] = [
                    $row['user_id'],
                    sprintf('%s (%s), %s - %s (%s)',
                        get_fullname($result['user_id']),
                        $row['username'],
                        $row['VeranstaltungsNummer'],
                        $row['coursename'],
                        $row['semester'])
                ];
            }
        } else {
            $result = StudipLog::searchUser($needle);
        }

        return $result;
    }

    public function removeEvasysCourse($event, $course)
    {
        if (Config::get()->EVASYS_ENABLE_PROFILES) {
            $profiles = EvasysCourseProfile::findBySQL("seminar_id = ?", array($course->getId()));
            $seminar_ids = array();
            foreach ($profiles as $profile) {
                if ($profile['transferred']) {
                    if ($profile['split']) {
                        $seminar_ids = array_unique(array_merge($seminar_ids, array_keys($profile['surveys']->getArrayCopy())));
                    } else {
                        if (!in_array($course->getId(), $seminar_ids)) {
                            $seminar_ids[] = $course->getId();
                        }
                    }
                }
            }
        } else {
            $seminar_ids = array($course->getId());
        }
        foreach ($seminar_ids as $seminar_id) {
            $soap = EvasysSoap::get();
            $soap->__soapCall("DeleteCourse", array(
                'CourseId' => $seminar_id,
                'IdType' => "PUBLIC"
            ));
        }
    }
}
