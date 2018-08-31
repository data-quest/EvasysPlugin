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

if (!interface_exists("AdminCourseContents")) {
    interface AdminCourseContents
    {
        public function adminAvailableContents();
        public function adminAreaGetCourseContent($course, $index);
    }
}

class EvasysPlugin extends StudIPPlugin implements SystemPlugin, StandardPlugin, AdminCourseAction, Loggable, AdminCourseContents
{

    public function useLowerPermissionLevels()
    {
        return (bool) Config::get()->EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS;
    }

    public function __construct()
    {
        parent::__construct();
        
        //The user must be root
        if (self::isRoot()) {
            $nav = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), Config::get()->EVASYS_ENABLE_PROFILES ? "globalprofile" : "forms/index"));
            Navigation::addItem("/admin/evasys", $nav);
            if (Config::get()->EVASYS_ENABLE_PROFILES) {
                $nav = new Navigation(_("Standardwerte"), PluginEngine::getURL($this, array(), "globalprofile"));
                Navigation::addItem("/admin/evasys/globalprofile", clone $nav);
                $nav = new Navigation(sprintf(_("Standardwerte der %s"), EvasysMatching::wording("Einrichtungen")), PluginEngine::getURL($this, array(), "instituteprofile"));
                Navigation::addItem("/admin/evasys/instituteprofile", clone $nav);
                $nav = new Navigation(_("Fragebögen"), PluginEngine::getURL($this, array(), "forms/index"));
                Navigation::addItem("/admin/evasys/forms", clone $nav);

                $nav = new Navigation(ucfirst(EvasysMatching::wording("freiwillige Evaluationen")), PluginEngine::getURL($this, array(), "individual/list"));
                Navigation::addItem("/admin/evasys/individual", clone $nav);
            }
            $nav = new Navigation(_("Matching Veranstaltungstypen"), PluginEngine::getURL($this, array(), "matching/seminartypes"));
            Navigation::addItem("/admin/evasys/matchingtypes", clone $nav);
            $nav = new Navigation(_("Matching Einrichtungen"), PluginEngine::getURL($this, array(), "matching/institutes"));
            Navigation::addItem("/admin/evasys/matchinginstitutes", clone $nav);
            $nav = new Navigation(_("Begrifflichkeiten"), PluginEngine::getURL($this, array(), "matching/wording"));
            Navigation::addItem("/admin/evasys/wording", clone $nav);
        } elseif (self::isAdmin() && Config::get()->EVASYS_ENABLE_PROFILES && Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS && Navigation::hasItem("/admin/institute")) {
            $nav = new Navigation(_("Standard-Evaluationsprofil"), PluginEngine::getURL($this, array(), "instituteprofile"));
            Navigation::addItem("/admin/institute/instituteprofile", $nav);
        }

        if (Config::get()->EVASYS_ENABLE_PROFILES
                && ((stripos($_SERVER['REQUEST_URI'], "dispatch.php/admin/courses") !== false) || (stripos($_SERVER['REQUEST_URI'], "plugins.php/evasysplugin/profile/bulkedit") !== false))
                ) {
            $this->addStylesheet("assets/evasys.less");
            if ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin") {
                if ($GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION)) {
                    PageLayout::addScript($this->getPluginURL() . "/assets/insert_button.js");
                }
            }
            PageLayout::addScript($this->getPluginURL() . "/assets/admin_area.js");
            NotificationCenter::addObserver($this, "addTransferredFilterToSidebar", "SidebarWillRender");
            NotificationCenter::addObserver($this, "addNonfittingDatesFilterToSidebar", "SidebarWillRender");
        }
        if (Config::get()->EVASYS_ENABLE_PROFILES && Navigation::hasItem("/course/admin")) {
            $nav = new Navigation(_("Lehrveranstaltungsevaluation"), PluginEngine::getURL($this, array(), "profile/edit/".Context::get()->id));
            $nav->setImage(Icon::create("checkbox-checked", "clickable"));
            $nav->setDescription(_("Beantragen Sie für diese Veranstaltung eine Lehrevaluation oder sehen Sie, ob eine Lehrevaluation für diese Veranstaltung vorgesehen ist."));
            Navigation::addItem("/course/admin/evasys", $nav);
        }
        NotificationCenter::addObserver($this, "addNonfittingDatesFilter", "AdminCourseFilterWillQuery");
        NotificationCenter::addObserver($this, "addTransferredFilter", "AdminCourseFilterWillQuery");
    }

    public function addTransferredFilterToSidebar()
    {
        if ($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin"
                || $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            $widget = new SelectWidget(_("Transfer-Filter"), PluginEngine::getURL($this, array(), "change_transferred_filter"), "transferstatus", "post");
            $widget->addElement(new SelectElement(
                '',
                ""
            ));
            $widget->addElement(new SelectElement(
                'transferred',
                _("Nach Evasys übertragen"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "transferred"
            ));
            $widget->addElement(new SelectElement(
                'nottransferred',
                _("Nach Evasys nicht übertragen"),
                $GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "nottransferred"
            ));
            Sidebar::Get()->insertWidget($widget, "editmode", "filter_transferred");
        }
    }

    public function addNonfittingDatesFilterToSidebar()
    {
        if (($GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA === "EvasysPlugin")
                || ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_NONFITTING_DATES"))) {
            $widget = new OptionsWidget();
            $widget->setTitle(_("Zeiten im Evaluationszeitraum"));
            $widget->addCheckbox(
                _("Nur Veranstaltungen, die im Eval-Zeitraum keine Termine haben"),
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

    public function change_transferred_filter_action()
    {
        $GLOBALS['user']->cfg->store("EVASYS_FILTER_TRANSFERRED", Request::option("transferstatus"));
        header("Location: ".URLHelper::getURL("dispatch.php/admin/courses"));
    }

    public function addTransferredFilter($event, $filter)
    {
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== 'all' ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE : Semester::findCurrent()->id;
        if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED")) {
            $filter->settings['query']['joins']['evasys_course_profiles'] = array(
                'join' => "LEFT JOIN",
                'on' => "
                seminare.Seminar_id = evasys_course_profiles.seminar_id AND evasys_course_profiles.applied = '1'
                    AND evasys_course_profiles.semester_id = :evasys_semester_id
                "
            );
            if ($GLOBALS['user']->cfg->getValue("EVASYS_FILTER_TRANSFERRED") === "transferred") {
                $filter->settings['query']['where']['evasys_transferred'] = "evasys_course_profiles.transferred = '1'";
            } else {
                $filter->settings['query']['where']['evasys_transferred'] = "(evasys_course_profiles.applied = '1' && evasys_course_profiles.transferred = '0')";
            }
            $filter->settings['parameter']['evasys_semester_id'] = $semester_id;
        }
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
            $filter->settings['query']['where']['date_not_in_timespan'] = "termine.termin_id IS NULL AND evasys_course_profiles.applied = '1'";
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
                    && ($profile['begin'] <= time())
                    && ($profile['end'] > time())) {
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
            $tab = new Navigation(_("Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
            if ($profile && $profile['split']) {
                $tab->setURL(PluginEngine::getLink($this, array(), "evaluation/split"));
            }
            $tab->setImage(Icon::create("evaluation", "inactive"), array('title' => _("Evaluationen")));
            $number = 0;
            foreach ($evasys_seminars as $evasys_seminar) {
                $number += $evasys_seminar->getEvaluationStatus();
            }
            if ($number > 0) {
                $tab->setImage(Icon::create("evaluation", "new"), array('title' => sprintf(_("%s neue Evaluation"), $number)));
            }
            return $tab;
        }
    }

    public function getTabNavigation($course_id)
    {
        $activated = false;
        if (Config::get()->EVASYS_ENABLE_PROFILES) {
            $profile = EvasysCourseProfile::findBySemester($course_id);
            if ($profile['applied']
                    && $profile['transferred']
                    && ($profile->getFinalBegin() <= time())
                    && ($profile->getFinalEnd() > time())) {
                $activated = true;
            }
        } else {
            $evasys_seminars = EvasysSeminar::findBySeminar($course_id);
            foreach ($evasys_seminars as $evasys_seminar) {
                if ($evasys_seminar['activated']) {
                    $activated = true;
                }
            }
        }
        if ($activated) {
            $tab = new Navigation(_("Evaluation"), PluginEngine::getLink($this, array(), "evaluation/show"));
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
            return Context::get()->getHeaderLine().": "._("Evaluation");
        } else {
            return _("Evasys");
        }
    }

    public function getAdminActionURL()
    {
        return $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION)
            ? PluginEngine::getURL($this, array(), "admin/upload_courses")
            : PluginEngine::getURL($this, array(), "profile/bulkedit");
    }

    public function useMultimode()
    {
        return $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION)
            ? _("Übertragen")
            : _("Bearbeiten");
    }

    public function getAdminCourseActionTemplate($course_id, $values = null, $semester = null)
    {
        $factory = new Flexi_TemplateFactory(__DIR__."/views");
        $template = $factory->open("admin/_admin_checkbox.php");
        $profile = EvasysCourseProfile::findBySemester($course_id, Semester::findCurrent()->id);
        $template->set_attribute("profile", $profile);
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
            $statement->execute(array($GLOBALS['user']->id));
            $roles = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (!$seminar_id && count($roles)) {
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
        return array(
            'form' => _("Fragebogen"),
            'mode' => _("Evaluationsart"),
            'timespan' => _("Eval-Zeitraum")
        );
    }

    public function adminAreaGetCourseContent($course, $index)
    {
        $profile = EvasysCourseProfile::findBySemester($course->getId());
        if (!$profile || !$profile['applied']) {
            return "";
        }
        switch ($index) {
            case "form":
                $form_id = $profile->getFinalFormId();
                if ($form_id) {
                    $form = EvasysForm::find($form_id);
                    return $form['name'];
                } else {
                    return "";
                }
            case "mode":
                if (Config::get()->EVASYS_FORCE_ONLINE) {
                    return _("Online");
                }
                return $profile->getFinalMode() === "online" ? _("Online") : _("Papier");
            case "timespan":
                $begin = $profile->getFinalBegin();
                $end = $profile->getFinalEnd();
                return date("d.m.Y H:i", $begin)." - ".date("d.m.Y H:i", $end);
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
}