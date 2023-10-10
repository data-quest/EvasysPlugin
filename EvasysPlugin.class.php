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

if (!interface_exists('AdminCourseWidgetPlugin')) {
    interface AdminCourseWidgetPlugin {}
}

class EvasysPlugin extends StudIPPlugin implements SystemPlugin, StandardPlugin, AdminCourseAction, Loggable, AdminCourseContents, AdminCourseWidgetPlugin
{

    static protected $ruecklauf = null;

    public function __construct()
    {
        bindtextdomain("evasys", __DIR__."/locale");
        parent::__construct();

        //The user must be root
        if (self::isRoot()) {
            $nav = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, [], "globalprofile"));
            Navigation::addItem("/admin/evasys", $nav);
            $nav = new Navigation(dgettext("evasys", "Standardwerte"), PluginEngine::getURL($this, [], "globalprofile"));
            Navigation::addItem("/admin/evasys/globalprofile", clone $nav);
            $nav = new Navigation(sprintf(dgettext("evasys", "Standardwerte der %s"), EvasysMatching::wording("Einrichtungen")), PluginEngine::getURL($this, [], "instituteprofile"));
            Navigation::addItem("/admin/evasys/instituteprofile", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Freie Felder"), PluginEngine::getURL($this, [], "config/additionalfields"));
            Navigation::addItem("/admin/evasys/additionalfields", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Fragebögen"), PluginEngine::getURL($this, [], "forms/index"));
            Navigation::addItem("/admin/evasys/forms", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Logs"), PluginEngine::getURL($this, [], "logs/index"));
            Navigation::addItem("/admin/evasys/logs", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Matching Veranstaltungstypen"), PluginEngine::getURL($this, [], "matching/seminartypes"));
            Navigation::addItem("/admin/evasys/matchingtypes", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Matching Einrichtungen"), PluginEngine::getURL($this, [], "matching/institutes"));
            Navigation::addItem("/admin/evasys/matchinginstitutes", clone $nav);
            $nav = new Navigation(dgettext("evasys", "Begrifflichkeiten"), PluginEngine::getURL($this, [], "matching/wording"));
            Navigation::addItem("/admin/evasys/wording", clone $nav);
        } elseif (Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS && Navigation::hasItem("/admin/institute")) {
            $nav = new Navigation(dgettext("evasys", "Standard-Evaluationsprofil"), PluginEngine::getURL($this, [], "instituteprofile"));
            if (!self::isAdmin()) {
                $nav->setEnabled(false);
            }
            Navigation::addItem("/admin/institute/instituteprofile", $nav);
        }

        if (((stripos($_SERVER['REQUEST_URI'], "dispatch.php/admin/courses") !== false)
                || (stripos($_SERVER['REQUEST_URI'], "plugins.php/evasysplugin/profile/bulkedit") !== false))) {
            $this->addStylesheet("assets/evasys.less");
            if ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin" && StudipVersion::olderThan('5.4.0')) {
                if ($GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION) && ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all")) {
                    PageLayout::addScript($this->getPluginURL() . "/assets/insert_button.js");
                }
            }
            if (StudipVersion::olderThan('5.3.99')) {
                NotificationCenter::addObserver($this, "addTransferredFilterToSidebar", "SidebarWillRender");
                NotificationCenter::addObserver($this, "addTransferdateFilterToSidebar", "SidebarWillRender");
                NotificationCenter::addObserver($this, "addNonfittingDatesFilterToSidebar", "SidebarWillRender");
                NotificationCenter::addObserver($this, "addRecentEvalCoursesFilterToSidebar", "SidebarWillRender");
                NotificationCenter::addObserver($this, "addFormFilterToSidebar", "SidebarWillRender");
                NotificationCenter::addObserver($this, "addPaperOnlineFilterToSidebar", "SidebarWillRender");
                NotificationCenter::addObserver($this, "addMainphaseFilterToSidebar", "SidebarWillRender");
                NotificationCenter::addObserver($this, "addIndividualFilterToSidebar", "SidebarWillRender");
            }
        }
        if (Navigation::hasItem("/course/admin") && Context::isCourse()) {
            if (Navigation::hasItem("/course/admin/evaluation")) {
                $nav = Navigation::getItem("/course/admin/evaluation");
                $nav->setTitle(dgettext("evasys", "Eigene Evaluationen"));
            }

            $nav = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getURL($this, [], "profile/edit/".Context::get()->id));
            $nav->setImage(Icon::create("checkbox-checked", "clickable"));
            $nav->setDescription(dgettext("evasys", "Beantragen Sie für diese Veranstaltung eine Lehrevaluation oder sehen Sie, ob eine Lehrevaluation für diese Veranstaltung vorgesehen ist."));
            Navigation::addItem("/course/admin/evasys", $nav);
        }
        if (Config::get()->EVASYS_ENABLE_PASSIVE_ACCOUNT && $GLOBALS['perm']->get_perm() === "dozent") {
            $tab = new Navigation(dgettext("evasys", "EvaSys"), PluginEngine::getURL($this, [], "passiveaccount/index"));
            Navigation::addItem("/profile/evasyspassiveaccount", $tab);
        }
        if (StudipVersion::olderThan('5.3.99')) {
            NotificationCenter::addObserver($this, "addNonfittingDatesFilter", "AdminCourseFilterWillQuery");
            NotificationCenter::addObserver($this, "addRecentEvalCoursesFilter", "AdminCourseFilterWillQuery");
            NotificationCenter::addObserver($this, "addTransferredFilter", "AdminCourseFilterWillQuery");
            NotificationCenter::addObserver($this, "addTransferdateFilter", "AdminCourseFilterWillQuery");
            NotificationCenter::addObserver($this, "addFormFilter", "AdminCourseFilterWillQuery");
            NotificationCenter::addObserver($this, "addPaperOnlineFilter", "AdminCourseFilterWillQuery");
            NotificationCenter::addObserver($this, "addMainphaseFilter", "AdminCourseFilterWillQuery");
            NotificationCenter::addObserver($this, "addIndividualFilter", "AdminCourseFilterWillQuery");
        }
        NotificationCenter::addObserver($this, "removeEvasysCourse", "CourseDidDelete");
    }

    public function isActivatableForContext(Range $context)
    {
        return $context->getRangeType() === 'course';
    }

    //Transferred Filter:

    public function addTransferredFilterToSidebar()
    {
        if ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin"
                || $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            $widget = new SelectWidget(dgettext("evasys", "Transfer-Filter"), PluginEngine::getURL($this, [], "change_transferred_filter"), "transferstatus", "post");
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
                dgettext("evasys", "Beantragt und nach EvaSys übertragen"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "transferred"
            ));
            $widget->addElement(new SelectElement(
                'changedtransferred',
                dgettext("evasys", "Nach Übertragung verändert"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "changedtransferred"
            ));
            $widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({transferstatus: $(this).find('select').val()}); return false;");

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
        if (StudipVersion::newerThan('5.3.99')) {
            $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERRED", Request::get('transferstatus'));
        }
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            //old usage:
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
            } elseif($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "changedtransferred") {
                $filter->settings['query']['where']['evasys_transferred']
                    = "(evasys_course_profiles.transferred = '1' AND evasys_course_profiles.transferdate < evasys_course_profiles.chdate)";
            }
        }
    }

    //add transferdate filter:

    public function addTransferdateFilterToSidebar()
    {
        if ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin"
            || $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            $widget = new OptionsWidget(dgettext("evasys", "Post-Transfer-Filter"));

            $widget->addCheckbox(
                dgettext("evasys", "Nach Transfer veränderte Veranstaltungen"),
                (bool) $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERDATE"),
                PluginEngine::getURL($this, ['transferdate' => 1], "change_transferdate_filter"),
                PluginEngine::getURL($this, ['transferdate' => 0], "change_transferdate_filter")
            );
            //Todo:
            //$widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({transferdate: $(this).find('input').val()}); return false;");
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_transferdate");
        }
    }

    public function change_transferdate_filter_action()
    {
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERDATE", Request::option("transferdate"));
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addTransferdateFilter($event, $filter)
    {
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERDATE")) {
            if ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->settings['query']['joins']['evasys_course_profiles'] = [
                    'join' => "LEFT JOIN",
                    'on' => "
                        seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                    "
                ];
            } else {
                $filter->settings['query']['joins']['evasys_course_profiles'] = [
                    'join' => "LEFT JOIN",
                    'on' => "
                        seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                            AND evasys_course_profiles.semester_id = :evasys_semester_id
                    "
                ];
                $filter->settings['parameter']['evasys_semester_id'] = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;
            }
            $filter->settings['query']['where']['evasys_transferdate']
                = "evasys_course_profiles.transferdate < evasys_course_profiles.chdate ";
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
                PluginEngine::getURL($this, [], "toggle_nonfittingdates_filter")
            );
            //Todo:
            //$widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({nonfittingdates: $(this).find('input').val()}); return false;");
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
        if (StudipVersion::newerThan('5.3.99')) {
            $GLOBALS['user']->cfg->store("EVASYS_FILTER_NONFITTING_DATES", Request::get('nonfittingdates'));
        }
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "
            seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                AND evasys_course_profiles.semester_id = :evasys_semester_id
            "
            ];
            $filter->settings['query']['joins']['evasys_institute_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "evasys_institute_profiles.institut_id = seminare.Institut_id
                AND evasys_institute_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['evasys_fakultaet_profiles'] = [
                'join' => "LEFT JOIN",
                'table' => "evasys_institute_profiles",
                'on' => "evasys_fakultaet_profiles.institut_id = Institute.fakultaets_id
                AND evasys_fakultaet_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['evasys_global_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "evasys_global_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['termine'] = [
                'join' => "LEFT JOIN",
                'on' => "
                seminare.Seminar_id = termine.range_id
                AND (
                    (termine.date >= IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.date < IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                    OR (termine.end_time > IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.end_time <= IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                    OR (termine.date < IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.end_time > IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                )
            "
            ];
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
                PluginEngine::getURL($this, [], "toggle_recentevalcourses_filter")
            );
            //Todo:
            //$widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({recentevalcourses: $(this).find('input').val()}); return false;");
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
            $filter->settings['query']['joins']['evasys_course_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "
            seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                AND evasys_course_profiles.semester_id = :evasys_semester_id
            "
            ];
            $filter->settings['query']['where']['eval_starts_next_days'] = "evasys_course_profiles.applied = '1' AND evasys_course_profiles.begin IS NOT NULL AND evasys_course_profiles.begin > UNIX_TIMESTAMP() AND evasys_course_profiles.begin < UNIX_TIMESTAMP() + 86400 * 7 ";
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
    }

    //Form filter:

    public function addFormFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
            || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID"))) {
            $widget = new SelectWidget(dgettext("evasys","Fragebogen-Filter"), PluginEngine::getURL($this, [], "change_form_filter"), "form_id", "post");
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
            $widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({form_id: $(this).find('select').val()}); return false;");

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
            $filter->settings['query']['joins']['evasys_course_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "
            seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                AND evasys_course_profiles.semester_id = :evasys_semester_id
            "
            ];
            $filter->settings['query']['joins']['evasys_institute_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "evasys_institute_profiles.institut_id = seminare.Institut_id
                AND evasys_institute_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['evasys_profiles_semtype_forms'] = [
                'join' => "LEFT JOIN",
                'on' => "evasys_profiles_semtype_forms.profile_id = evasys_institute_profiles.institute_profile_id
                AND evasys_profiles_semtype_forms.profile_type = 'institute'
                AND evasys_profiles_semtype_forms.sem_type = seminare.status
                AND evasys_profiles_semtype_forms.`standard` = '1'"
            ];
            $filter->settings['query']['joins']['evasys_fakultaet_profiles'] = [
                'join' => "LEFT JOIN",
                'table' => "evasys_institute_profiles",
                'on' => "evasys_fakultaet_profiles.institut_id = Institute.fakultaets_id
                AND evasys_fakultaet_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['evasys_profiles_semtype_forms_fakultaet'] = [
                'table' => "evasys_profiles_semtype_forms",
                'join' => "LEFT JOIN",
                'on' => "evasys_profiles_semtype_forms_fakultaet.profile_id = evasys_fakultaet_profiles.institute_profile_id
                AND evasys_profiles_semtype_forms_fakultaet.profile_type = 'institute'
                AND evasys_profiles_semtype_forms_fakultaet.sem_type = seminare.status
                AND evasys_profiles_semtype_forms_fakultaet.`standard` = '1'"
            ];
            $filter->settings['query']['joins']['evasys_global_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "evasys_global_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['evasys_profiles_semtype_forms_global'] = [
                'table' => "evasys_profiles_semtype_forms",
                'join' => "LEFT JOIN",
                'on' => "evasys_profiles_semtype_forms_global.profile_id = evasys_global_profiles.semester_id
                AND evasys_profiles_semtype_forms_global.profile_type = 'global'
                AND evasys_profiles_semtype_forms_global.sem_type = seminare.status
                AND evasys_profiles_semtype_forms_global.`standard` = '1'"
            ];


            $filter->settings['query']['where']['evasys_form_filter'] = "IFNULL(evasys_course_profiles.form_id, IFNULL(evasys_profiles_semtype_forms.form_id, IFNULL(evasys_profiles_semtype_forms_fakultaet.form_id, IFNULL(evasys_profiles_semtype_forms_global.form_id, IFNULL(evasys_institute_profiles.form_id, IFNULL(evasys_fakultaet_profiles.form_id, evasys_global_profiles.form_id)))))) = :evasys_form_id";
            $filter->settings['parameter']['evasys_form_id'] = $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID");
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;

        }
    }

    //Filter for online/paper evaluation

    public function addPaperOnlineFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
            || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID"))) {
            $widget = new SelectWidget(dgettext("evasys","Modus der Evaluation"), PluginEngine::getURL($this, [], "change_paperonline_filter"), "paperonline", "post");
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
            $widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({paperonline: $(this).find('select').val()}); return false;");
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
            $filter->settings['query']['joins']['evasys_course_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "
            seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                AND evasys_course_profiles.semester_id = :evasys_semester_id
            "
            ];
            $filter->settings['query']['joins']['evasys_institute_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "evasys_institute_profiles.institut_id = seminare.Institut_id
                AND evasys_institute_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['evasys_fakultaet_profiles'] = [
                'join' => "LEFT JOIN",
                'table' => "evasys_institute_profiles",
                'on' => "evasys_fakultaet_profiles.institut_id = Institute.fakultaets_id
                AND evasys_fakultaet_profiles.semester_id = :evasys_semester_id"
            ];
            $filter->settings['query']['joins']['evasys_global_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "evasys_global_profiles.semester_id = :evasys_semester_id"
            ];

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
            $widget = new SelectWidget(dgettext("evasys","Hauptphasen-Filter"), PluginEngine::getURL($this, [], "change_mainphase_filter"), "mainphase", "post");
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
            $widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({mainphase: $(this).find('select').val()}); return false;");
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
            $filter->settings['query']['joins']['evasys_course_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "
            seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                AND evasys_course_profiles.semester_id = :evasys_semester_id
            "
            ];
            if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "nonmainphase") {
                $filter->settings['query']['where']['evasys_mainphase_filter'] = "evasys_course_profiles.`begin` IS NOT NULL";
            } elseif($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "mainphase") {
                $filter->settings['query']['where']['evasys_mainphase_filter'] = "evasys_course_profiles.`begin` IS NULL";
            }
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
    }

    public function addIndividualFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
            || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL"))) {
            $widget = new SelectWidget(dgettext("evasys", ucfirst(EvasysMatching::wording('freiwillige Evaluationen'))), PluginEngine::getURL($this, [], "change_individual_filter"), "individual", "post");
            $widget->addElement(new SelectElement(
                '',
                ""
            ));
            $widget->addElement(new SelectElement(
                "individual",
                (sprintf(dgettext("evasys","Nur %s"), EvasysMatching::wording('freiwillige Evaluationen'))),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL") === "individual"
            ));
            $widget->addElement(new SelectElement(
                "nonindividual",
                (sprintf(dgettext("evasys","Keine %s"), EvasysMatching::wording('freiwillige Evaluation'))),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL") === "nonindividual"
            ));
            $widget->setOnSubmitHandler("STUDIP.AdminCourses.App.changeFilter({individual: $(this).find('select').val()}); return false;");
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_individual");
        }
    }

    public function change_individual_filter_action()
    {
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_INDIVIDUAL", Request::get("individual"));
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addIndividualFilter($event, $filter)
    {
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = [
                'join' => "LEFT JOIN",
                'on' => "
            seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                AND evasys_course_profiles.semester_id = :evasys_semester_id
            "
            ];
            $filter->settings['query']['where']['evasys_individual_filter'] = "evasys_course_profiles.by_dozent = :evasys_individual";
            $filter->settings['parameter']['evasys_individual'] = ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL") === 'individual') ? 1 : 0;
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
    }

    public function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        $activated = false;
        $evasys_seminar = EvasysSeminar::findBySeminar($course_id);
        $profile = EvasysCourseProfile::findBySemester($course_id);
        if ($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) {
            if ($profile['applied']) {
                $activated = true;
            }
        } else {
            if ($profile['applied']
                && $profile['transferred']
                && ($profile->getFinalBegin() <= time())
                && ($profile->getFinalEnd() > time())) {
                $activated = true;
            }
        }

        if ($activated) {
            $tab = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getLink($this, [], "evaluation/show"));
            $tab->setImage(Icon::create("evaluation", "inactive"), ['title' => dgettext("evasys", "Lehrveranstaltungsevaluationen")]);
            if (!Config::get()->EVASYS_NO_RED_ICONS) {
                if (Config::get()->EVASYS_RED_ICONS_STOP_UNTIL > time()) {
                    $tab->setImage(Icon::create("evaluation", "new"), ['title' => dgettext("evasys", "Neue Evaluation")]);
                } else {
                    $number = 0;
                    if ($evasys_seminar) {
                        $number += $evasys_seminar->getEvaluationStatus();
                    }
                    if ($number > 0) {
                        $tab->setImage(Icon::create("evaluation", "new"), ['title' => sprintf(dgettext("evasys", "%s neue Evaluation"), $number)]);
                    }
                }
            }
            return $tab;
        } elseif($profile && $profile['applied'] && $GLOBALS['perm']->have_studip_perm("dozent", $course_id)) {
            $tab = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getURL($this, [], "profile/edit/".$course_id));
            $tab->setImage(Icon::create("evaluation", "inactive"), ['title' => dgettext("evasys", "Evaluationen")]);
            return $tab;
        }
    }

    public function getTabNavigation($course_id)
    {
        $activated = false;
        $profiles = EvasysCourseProfile::findBySQL("seminar_id = ?", [$course_id]);
        foreach ($profiles as $profile) {
            if ($GLOBALS['perm']->have_studip_perm('dozent', $course_id) && $profile['applied']) {
                $activated = true;
                break;
            } else {
                if ($profile['applied']
                    && $profile['transferred']
                    && ($profile->getFinalBegin() <= time())) {
                    if ($profile->getFinalAttribute('mode') === 'online' || $profile->evasys_seminar['publishing_allowed']) {
                        $activated = true;
                        break;
                    } elseif($profile['split']) {
                        foreach ($profile->course->members->findBy('status','dozent') as $dozent) {
                            if ($profile->evasys_seminar['publishing_allowed_by_dozent'] == $dozent['user_id']) {
                                $activated = true;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        if ($activated) {
            $tab = new Navigation(dgettext("evasys", "Lehrveranst.-Evaluation"), PluginEngine::getLink($this, [], "evaluation/show"));
            $tab->setImage(Icon::create("evaluation", "info_alt"));
            return ['evasys' => $tab];
        }
    }

    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return null;
    }

    public function getInfoTemplate($course_id)
    {
        return null;
    }

    public function getDisplayName()
    {
        if (Navigation::hasItem("/course") && Navigation::getItem("/course")->isActive()) {
            return Context::getHeaderLine().": ".dgettext("evasys", "Evaluation");
        } else {
            return dgettext("evasys", "EvaSys");
        }
    }

    public function getAdminActionURL()
    {
        return $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION) && ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all")
            ? PluginEngine::getURL($this, [], "admin/upload_courses")
            : PluginEngine::getURL($this, [], "profile/bulkedit");
    }

    public function useMultimode()
    {
        if (StudipVersion::newerThan('5.3.99')) {
            $factory = new Flexi_TemplateFactory(__DIR__."/views");
            $template = $factory->open("admin/bottom_buttons.php");
            $template->plugin = $this;
            return $template;
        } else {
            return $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION) && ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all")
                ? dgettext("evasys", "Übertragen")
                : dgettext("evasys", "Bearbeiten");
        }
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
            $profiles = [EvasysCourseProfile::findBySemester(
                $course_id,
                $semester_id
            )];
        } else {
            $profiles = EvasysCourseProfile::findBySQL("seminar_id = :course_id", [
                'course_id' => $course_id
            ]);
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
        $get_semesters->execute(['seminar_id' => $course_id]);
        $semesters = [];
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
            $statement->execute([$GLOBALS['user']->id]);
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

    public function adminAvailableContents()
    {
        $array = [
            'form' => dgettext("evasys", "Fragebogen"),
            'mode' => dgettext("evasys", "Modus der Evalation"),
            'timespan' => dgettext("evasys", "Eval-Zeitraum"),
            'applied' => dgettext("evasys", "Evaluation beantragt"),
            'lehrende_emails' => dgettext("evasys", 'Eval: Beantragte Lehrende (Emails)'),
            'ruecklauf' => dgettext("evasys", "Rückläufer"),
            'individual' => ucfirst(EvasysMatching::wording('freiwillige Evaluation'))
        ];
        if (Config::get()->EVASYS_ENABLE_SPLITTING_COURSES) {
            $array['split'] = dgettext("evasys", "Teilevaluation");
        }
        if (EvasysGlobalProfile::findOneBySQL("`enable_objection_to_publication` = 'yes'")) {
            $array['objection_to_publication'] = dgettext("evasys", "Widerspruch");
            $array['objection_reason'] = dgettext("evasys", "Begründung des Widerspruchs");
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
                $emails = [];
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
            case "individual":
                return $profile && $profile['by_dozent'] ? 1 : 0;
            case "ruecklauf":

                $active_seminar_ids = [];

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

                $results = [];

                $soap = EvasysSoap::get();

                foreach (array_keys($active_seminar_ids) as $course_code) {
                    $evasys_surveys_object = $soap->soapCall("GetCourse", [
                            'CourseId' => $course_code,
                            'IdType' => "PUBLIC",
                            'IncludeSurveys' => true,
                            'IncludeParticipants' => false
                        ]
                    );
                    if (is_a($evasys_surveys_object, "SoapFault")) {
                        if ($evasys_surveys_object->getMessage() === "ERR_312") {
                            PageLayout::postError(sprintf(dgettext("evasys", "Veranstaltung %s existiert nicht mehr in EvaSys"), htmlReady($course['name'])));
                        } else {
                            PageLayout::postError("SOAP-error: " . $evasys_surveys_object->getMessage());
                        }
                        return;
                    }
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
            case 'objection_to_publication':
                return $profile->getPresetAttribute('enable_objection_to_publication') === 'yes' && $profile['objection_to_publication']
                    ? 1
                    : 0;
            case 'objection_reason':
                return $profile->getPresetAttribute('enable_objection_to_publication') === 'yes' && $profile['objection_to_publication']
                    ? $profile['objection_reason']
                    : '';
        }
    }

    public static function logFormat(LogEvent $event)
    {
        $tmpl = $event->action->info_template;

        if (strpos($event->action->name, 'EVASYS') !== false) {
            $course = Course::find($event->coaffected_range_id);
            $semester = Semester::find($event->info);
            if ($course) {
                $url = PluginEngine::getURL('EvasysPlugin', [], '/profile/edit/'
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
        $profiles = EvasysCourseProfile::findBySQL("seminar_id = ?", [$course->getId()]);
        $seminar_ids = [];
        foreach ($profiles as $profile) {
            if ($profile['transferred']) {
                if ($profile['split']) {
                    if ($profile['surveys']) {
                        $seminar_ids = array_unique(array_merge($seminar_ids, array_keys($profile['surveys']->getArrayCopy())));
                    } else {
                        $seminar_ids = [];
                    }
                } else {
                    if (!in_array($course->getId(), $seminar_ids)) {
                        $seminar_ids[] = $course->getId();
                    }
                }
            }
        }
        foreach ($seminar_ids as $seminar_id) {
            $soap = EvasysSoap::get();
            $soap->soapCall("DeleteCourse", [
                'CourseId' => $seminar_id,
                'IdType' => "PUBLIC"
            ]);
        }
    }


    public function getWidgets() : array
    {
        $widgets = [];

        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", "Transfer-Filter")
        );
        $widget->addSelect(
            _("Transfer-Filter"),
            'transferstatus',
            [
                '' => "",
                'applied' => dgettext("evasys", "Beantragt"),
                'notapplied' => dgettext("evasys", "Nicht beantragt"),
                'nottransferred' => dgettext("evasys", "Beantragt und noch nicht übertragen"),
                'transferred' => dgettext("evasys", "Beantragt und nach EvaSys übertragen"),
                'changedtransferred' => dgettext("evasys", "Nach Übertragung verändert")
            ],
            $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")
        );
        $widgets['transferstatus'] = $widget;


        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", "Post-Transfer-Filter")
        );
        $widget->addCheckbox(
            dgettext("evasys", "Nach Transfer veränderte Veranstaltungen"),
            'transferdate',
            (bool) $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERDATE")
        );
        $widgets['transferdate'] = $widget;


        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", "Zeiten im Evaluationszeitraum")
        );
        $widget->addCheckbox(
            (dgettext("evasys","Nur Veranstaltungen, die im Eval-Zeitraum keine Termine haben")),
            'nonfittingdates',
            (bool) $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES")
        );
        $widgets['nonfittingdates'] = $widget;


        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", "Ausreißer-Filter")
        );
        $widget->addCheckbox(
            (dgettext("evasys","Ausreißer-Veranstaltungen der nächsten 7 Tage anzeigen.")),
            'recentevalcourses',
            (bool) $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_RECENT_EVAL_COURSES")
        );
        $widgets['recentevalcourses'] = $widget;


        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", "Fragebogen-Filter")
        );
        $options = ['' => ''];
        foreach (EvasysForm::findBySQL("`active` = '1' ORDER BY name ASC") as $form) {
            $options[(int) $form->getId()] = $form['name'];
        }
        $widget->addSelect(
            _("Fragebogen"),
            'form_id',
            $options,
            (int) $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID")
        );
        $widgets['form_id'] = $widget;


        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", "Modus der Evaluation")
        );
        $widget->addSelect(
            _("Modus"),
            'paperonline',
            [
                '' => "",
                'online' => dgettext("evasys", "Online-Evaluationen"),
                'paper' => dgettext("evasys", "Papier-Evaluationen")
            ],
            $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE")
        );
        $widgets['paperonline'] = $widget;


        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", "Hauptphasen-Filter")
        );
        $widget->addSelect(
            _("Modus"),
            'mainphase',
            [
                '' => "",
                'mainphase' => dgettext("evasys", "Veranstaltungen in der Hauptphase"),
                'nonmainphase' => dgettext("evasys", "Veranstaltungen außerhalb der Hauptphase")
            ],
            $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE")
        );
        $widgets['mainphase'] = $widget;


        $widget = new AdminCourseOptionsWidget(
            dgettext("evasys", ucfirst(EvasysMatching::wording('freiwillige Evaluationen')))
        );
        $widget->addSelect(
            _("Beantragung"),
            'mainphase',
            [
                '' => "",
                'individual' => sprintf(dgettext("evasys","Nur %s"), EvasysMatching::wording('freiwillige Evaluationen')),
                'nonindividual' => sprintf(dgettext("evasys","Keine %s"), EvasysMatching::wording('freiwillige Evaluation'))
            ],
            $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL")
        );

        $widgets['individual'] = $widget;

        return $widgets;
    }

    public function getFilters(): array
    {
        return [
            'transferstatus' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED"),
            'transferdate' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERDATE"),
            'nonfittingdates' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES"),
            'recentevalcourses' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_RECENT_EVAL_COURSES"),
            'form_id' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID"),
            'paperonline' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE"),
            'mainphase' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE"),
            'individual' => $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL")
        ];
    }

    public function applyFilters(AdminCourseFilter $filter): void
    {
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "transferred") {
                $filter->query->where('evasys_transferred', "evasys_course_profiles.transferred = '1'");
            } elseif ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "applied") {
                $filter->query->where('evasys_transferred', "evasys_course_profiles.applied = '1'");
            } elseif ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "notapplied") {
                $filter->query->where('evasys_transferred', "(evasys_course_profiles.applied = '0' OR evasys_course_profiles.applied IS NULL)");
            } elseif ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "nottransferred") {
                $filter->query->where("(evasys_course_profiles.applied = '1' AND evasys_course_profiles.transferred = '0')");
            } elseif ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "changedtransferred") {
                $filter->query->where('evasys_transferred', "(evasys_course_profiles.transferred = '1' AND evasys_course_profiles.transferdate < evasys_course_profiles.chdate)");
            }
        }

        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERDATE")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            $filter->query->where(
                'evasys_transferdate',
                "evasys_course_profiles.transferdate < evasys_course_profiles.chdate "
            );
        }


        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            $filter->query->join(
                'evasys_institute_profiles',
                'evasys_institute_profiles',
                "evasys_institute_profiles.institut_id = seminare.Institut_id
                    AND evasys_institute_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->join(
                'inst_evasys',
                'Institute',
                "seminare.Institut_id = inst_evasys.Institut_id"
            );
            $filter->query->join(
                'evasys_fakultaet_profiles',
                'evasys_institute_profiles',
                "evasys_fakultaet_profiles.institut_id = inst_evasys.fakultaets_id
                    AND evasys_fakultaet_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->join(
                'evasys_global_profiles',
                'evasys_global_profiles',
                "evasys_global_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->join(
                'termine',
                'termine',
                "seminare.Seminar_id = termine.range_id
                AND (
                    (termine.date >= IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.date < IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                    OR (termine.end_time > IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.end_time <= IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                    OR (termine.date < IFNULL(evasys_course_profiles.begin, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) AND termine.end_time > IFNULL(evasys_course_profiles.end, IFNULL(evasys_institute_profiles.end, IFNULL(evasys_fakultaet_profiles.end, evasys_global_profiles.end))))
                )",
                'LEFT JOIN'
            );
            $filter->query->where(
                'date_not_in_timespan',
                "termine.termin_id IS NULL"
            );
        }

        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_RECENT_EVAL_COURSES")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            $filter->query->where(
                'eval_starts_next_days',
                "evasys_course_profiles.applied = '1' AND evasys_course_profiles.begin IS NOT NULL AND evasys_course_profiles.begin > UNIX_TIMESTAMP() AND evasys_course_profiles.begin < UNIX_TIMESTAMP() + 86400 * 7 "
            );
        }


        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            $filter->query->join(
                'evasys_institute_profiles',
                'evasys_institute_profiles',
                "evasys_institute_profiles.institut_id = seminare.Institut_id
                    AND evasys_institute_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->join(
                'inst_evasys',
                'Institute',
                "seminare.Institut_id = inst_evasys.Institut_id"
            );
            $filter->query->join(
                'evasys_fakultaet_profiles',
                'evasys_institute_profiles',
                "evasys_fakultaet_profiles.institut_id = inst_evasys.fakultaets_id
                    AND evasys_fakultaet_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->join(
                'evasys_global_profiles',
                'evasys_global_profiles',
                "evasys_global_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );

            $filter->query->join(
                'evasys_profiles_semtype_forms',
                'evasys_profiles_semtype_forms',
                "evasys_profiles_semtype_forms.profile_id = evasys_institute_profiles.institute_profile_id
                AND evasys_profiles_semtype_forms.profile_type = 'institute'
                AND evasys_profiles_semtype_forms.sem_type = seminare.status
                AND evasys_profiles_semtype_forms.`standard` = '1'",
                'LEFT JOIN'
            );
            $filter->query->join(
                'evasys_profiles_semtype_forms_fakultaet',
                'evasys_profiles_semtype_forms',
                "evasys_profiles_semtype_forms_fakultaet.profile_id = evasys_fakultaet_profiles.institute_profile_id
                AND evasys_profiles_semtype_forms_fakultaet.profile_type = 'institute'
                AND evasys_profiles_semtype_forms_fakultaet.sem_type = seminare.status
                AND evasys_profiles_semtype_forms_fakultaet.`standard` = '1'",
                'LEFT JOIN'
            );
            $filter->query->join(
                'evasys_profiles_semtype_forms_global',
                'evasys_profiles_semtype_forms',
                "evasys_profiles_semtype_forms_global.profile_id = evasys_global_profiles.semester_id
                AND evasys_profiles_semtype_forms_global.profile_type = 'global'
                AND evasys_profiles_semtype_forms_global.sem_type = seminare.status
                AND evasys_profiles_semtype_forms_global.`standard` = '1'",
                'LEFT JOIN'
            );
            $filter->query->where('evasys_form_filter', "IFNULL(evasys_course_profiles.form_id, IFNULL(evasys_profiles_semtype_forms.form_id, IFNULL(evasys_profiles_semtype_forms_fakultaet.form_id, IFNULL(evasys_profiles_semtype_forms_global.form_id, IFNULL(evasys_institute_profiles.form_id, IFNULL(evasys_fakultaet_profiles.form_id, evasys_global_profiles.form_id)))))) = :evasys_form_id");
            $filter->query->parameter('evasys_form_id', $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_FORM_ID"));
        }


        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            $filter->query->join(
                'evasys_institute_profiles',
                'evasys_institute_profiles',
                "evasys_institute_profiles.institut_id = seminare.Institut_id
                    AND evasys_institute_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->join(
                'inst_evasys',
                'Institute',
                "seminare.Institut_id = inst_evasys.Institut_id"
            );
            $filter->query->join(
                'evasys_fakultaet_profiles',
                'evasys_institute_profiles',
                "evasys_fakultaet_profiles.institut_id = inst_evasys.fakultaets_id
                    AND evasys_fakultaet_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->join(
                'evasys_global_profiles',
                'evasys_global_profiles',
                "evasys_global_profiles.semester_id = evasys_course_profiles.semester_id",
                'LEFT JOIN'
            );
            $filter->query->where('evasys_paperonline_filter', "IFNULL(evasys_course_profiles.mode, IFNULL(evasys_institute_profiles.mode, IFNULL(evasys_fakultaet_profiles.mode, evasys_global_profiles.mode))) = :evasys_mode");
            $filter->query->parameter('evasys_mode', $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_PAPER_ONLINE"));
        }


        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "nonmainphase") {
                $filter->query->where('evasys_mainphase_filter', "evasys_course_profiles.`begin` IS NOT NULL");
            } elseif($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_MAINPHASE") === "mainphase") {
                $filter->query->where('evasys_mainphase_filter', "evasys_course_profiles.`begin` IS NULL");
            }
        }


        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL")) {
            if (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === 'all') {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id",
                    'LEFT JOIN'
                );
            } else {
                $filter->query->join(
                    'evasys_course_profiles',
                    'evasys_course_profiles',
                    "seminare.Seminar_id = evasys_course_profiles.seminar_id
                        AND evasys_course_profiles.semester_id = :evasys_semester_id",
                    'LEFT JOIN'
                );
                $filter->query->parameter('evasys_semester_id', $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
            }
            $filter->query->where('evasys_individual_filter', "evasys_course_profiles.by_dozent = :evasys_individual");
            $filter->query->parameter('evasys_individual', ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_INDIVIDUAL") === 'individual') ? 1 : 0);
        }
    }

    public function setFilters(array $filters): void
    {
        foreach ($filters as $name => $value) {
            switch ($name) {
                case 'transferstatus':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERRED", $value);
                    break;
                case 'transferdate':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERDATE", $value);
                    break;
                case 'nonfittingdates':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_NONFITTING_DATES", $value);
                    break;
                case 'recentevalcourses':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_RECENT_EVAL_COURSES", $value);
                    break;
                case 'form_id':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_FORM_ID", $value);
                    break;
                case 'paperonline':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_PAPER_ONLINE", $value);
                    break;
                case 'mainphase':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_MAINPHASE", $value);
                    break;
                case 'individual':
                    $GLOBALS['user']->cfg->store("EVASYS_FILTER_INDIVIDUAL", $value);
                    break;
            }
        }
    }

    public function setFilter(string $name, $value): void
    {
        switch ($name) {
            case 'transferstatus':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERRED", $value);
                break;
            case 'transferdate':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERDATE", $value);
                break;
            case 'nonfittingdates':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_NONFITTING_DATES", $value);
                break;
            case 'recentevalcourses':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_RECENT_EVAL_COURSES", $value);
                break;
            case 'form_id':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_FORM_ID", $value);
                break;
            case 'paperonline':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_PAPER_ONLINE", $value);
                break;
            case 'mainphase':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_MAINPHASE", $value);
                break;
            case 'individual':
                $GLOBALS['user']->cfg->store("EVASYS_FILTER_INDIVIDUAL", $value);
                break;
        }
    }

    public function getPositionInSidebar($name): ?string
    {
        switch ($name) {
            case 'transferstatus';
            case 'transferdate':
            case 'nonfittingdates':
            case 'recentevalcourses':
            case 'form_id':
            case 'paperonline':
            case 'mainphase':
            case 'individual':
                return 'editmode';
        }
    }
}
