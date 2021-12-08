<?php

class GlobalprofileController extends PluginController
{
    public $profile_type = "global";

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!Config::get()->EVASYS_ENABLE_PROFILES) {
            throw new AccessDeniedException();
        }

        if ($this->profile_type === "global") {
            if (!EvasysPlugin::isRoot()) {
                throw new AccessDeniedException();
            }
            Navigation::activateItem("/admin/evasys/globalprofile");
        } else {
            if (!EvasysPlugin::isRoot() && !EvasysPlugin::isAdmin()) {
                throw new AccessDeniedException();
            }
            if (EvasysPlugin::isAdmin() && !Config::get()->EVASYS_ENABLE_PROFILES_FOR_ADMINS) {
                throw new AccessDeniedException();
            }
            if (Navigation::hasItem("/admin/evasys/instituteprofile")) {
                Navigation::activateItem("/admin/evasys/instituteprofile");
            } else {
                Navigation::activateItem("/admin/institute/instituteprofile");
            }
        }
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/evasys.js");
    }


    public function index_action()
    {
        PageLayout::setTitle($this->plugin->getDisplayName());
        if (Request::option("semester_id")) {
            $GLOBALS['user']->cfg->store('MY_COURSES_SELECTED_CYCLE', Request::option('semester_id'));
        }
        $this->semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all"
            ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE
            : Semester::findCurrent()->id;
        if ($this->profile_type === "global") {
            $this->profile = new EvasysGlobalProfile($this->semester_id);
        } elseif($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT && $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT !== "all") {
            $this->profile = EvasysInstituteProfile::findByInstitute(
                $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT,
                $this->semester_id
            );
        }
        $this->con = $this->profile_type."profile";

        if ($this->profile) {

            $statement = DBManager::get()->prepare("
                SELECT sem_type, form_id
                FROM evasys_profiles_semtype_forms
                WHERE profile_id = :semester_id
                    AND profile_type = :profile_type
                    AND standard = '1'
            ");
            $statement->execute(array(
                'semester_id' => $this->profile->getId(),
                'profile_type' => $this->profile_type
            ));
            $this->forms_by_type = $statement->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);

            $statement = DBManager::get()->prepare("
                SELECT sem_type, form_id
                FROM evasys_profiles_semtype_forms
                WHERE profile_id = :semester_id
                    AND profile_type = :profile_type
                    AND standard = '0'
            ");
            $statement->execute(array(
                'semester_id' => $this->profile->getId(),
                'profile_type' => $this->profile_type
            ));
            $this->available_forms_by_type = $statement->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
        }
        $statement = DBManager::get()->prepare("
            SELECT 1
            FROM semester_data
                LEFT JOIN evasys_global_profiles ON (evasys_global_profiles.semester_id = semester_data.semester_id)
            WHERE evasys_global_profiles.semester_id IS NULL
        ");
        $statement->execute();
        $this->addSemester = $statement->fetch();

        $this->render_template("globalprofile/index", $this->layout);
    }

    public function edit_action()
    {
        $this->semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all"
            ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE
            : Semester::findCurrent()->id;
        if ($this->profile_type === "global") {
            $this->profile = new EvasysGlobalProfile($this->semester_id);
        } elseif($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT && $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT !== "all") {
            $this->profile = EvasysInstituteProfile::findByInstitute(
                $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT,
                $this->semester_id
            );
            if (!$this->profile) {
                $this->profile = new EvasysInstituteProfile();
                $this->profile['institut_id'] = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;
                $this->profile['semester_id'] = $this->semester_id;
            }
        }
        if (Request::isPost()) {
            if (Request::submitted("delete")
                    && ($this->profile->semester['beginn'] > Semester::findCurrent()->beginn)) {
                $this->profile->delete();
                PageLayout::postSuccess(dgettext("evasys", "Einstellungen des Semesters wurden gelöscht."));
                $this->redirect($this->profile_type."profile/index");
                return;
            }
            $data = Request::getArray("data");
            $data['begin'] = $data['begin'] ? strtotime($data['begin']) : null;
            $data['end'] = $data['end'] ? strtotime($data['end']) : null;
            $data['adminedit_begin'] = $data['adminedit_begin'] ? strtotime($data['adminedit_begin']) : null;
            $data['adminedit_end'] = $data['adminedit_end'] ? strtotime($data['adminedit_end']) : null;
            $data['form_id'] = $data['form_id'] ?: null;
            $data['mode'] = $data['mode'] ?: null;
            $data['paper_mode'] = $data['paper_mode'] ?: ($this->profile_type === "global" ? "s" : null);
            $data['antrag_begin'] = $data['antrag_begin'] ? strtotime($data['antrag_begin']) : null;
            $data['antrag_end'] = $data['antrag_end'] ? strtotime($data['antrag_end']) : null;
            $data['antrag_info'] = $data['antrag_info'] ?: null;
            $data['send_report'] = $data['send_report'] ?: null;
            $data['send_report_delay'] = $data['send_report_delay'] ?: null;
            $data['lockaftertransferforrole'] = $data['lockaftertransferforrole'] ?: null;
            $data['enable_objection_to_publication'] = $data['enable_objection_to_publication'] ?: null;
            $data['objection_teilbereich'] = $data['objection_teilbereich'] ?: null;
            $data['user_id'] = $GLOBALS['user']->id;
            if ($this->profile_type === "institute") {
                if ($this->profile->global_profile['extended_report_offset'] == $data['extended_report_offset']) {
                    $data['extended_report_offset'] = null;
                }
            }
            $this->profile->setData($data);
            $this->profile->store();

            //now edit all the form-relations for the global profile:
            foreach (Request::getArray("forms_by_type") as $sem_type => $form_id) {
                $entry = EvasysProfileSemtypeForm::findOneBySQL("profile_id = :profile_id AND profile_type = :profile_type AND sem_type = :sem_type AND standard = '1' ", array(
                    'profile_id' => $this->profile->getId(),
                    'sem_type' => $sem_type,
                    'profile_type' => $this->profile_type
                ));
                if (!$entry) {
                    $entry = new EvasysProfileSemtypeForm();
                    $entry['profile_id'] = $this->profile->getId();
                    $entry['profile_type'] = $this->profile_type;
                    $entry['sem_type'] = $sem_type;
                    $entry['standard'] = 1;
                }
                if ($form_id) {
                    $entry['form_id'] = $form_id;
                    $entry->store();
                } else {
                    $entry->delete();
                }
                EvasysProfileSemtypeForm::deleteBySQL("profile_id = :profile_id AND profile_type = :profile_type AND sem_type = :sem_type AND standard = '1' AND form_id != :form_id", array(
                    'profile_id' => $this->profile->getId(),
                    'sem_type' => $sem_type,
                    'form_id' => $form_id,
                    'profile_type' => $this->profile_type
                ));
            }

            foreach (Request::getArray("available_forms_by_type") as $sem_type => $form_ids) {
                EvasysProfileSemtypeForm::deleteBySQL("profile_id = :profile_id AND profile_type = :profile_type AND sem_type = :sem_type AND standard = '0' AND form_id NOT IN (:form_ids)", array(
                    'profile_id' => $this->profile->getId(),
                    'sem_type' => $sem_type,
                    'form_ids' => $form_ids,
                    'profile_type' => $this->profile_type
                ));

                foreach ($form_ids as $i => $form_id) {
                    if ($form_id) {
                        $entry = EvasysProfileSemtypeForm::findOneBySQL("profile_id = :profile_id AND profile_type = :profile_type AND sem_type = :sem_type AND form_id = :form_id AND standard = '0' ", array(
                            'profile_id' => $this->profile->getId(),
                            'sem_type' => $sem_type,
                            'form_id' => $form_id,
                            'profile_type' => $this->profile_type
                        ));
                        if (!$entry) {
                            $entry = new EvasysProfileSemtypeForm();
                            $entry['profile_id'] = $this->profile->getId();
                            $entry['profile_type'] = $this->profile_type;
                            $entry['sem_type'] = $sem_type;
                            $entry['standard'] = 0;
                            $entry['position'] = 100 + $i;
                        }
                        $entry['form_id'] = $form_id;
                        $entry->store();
                    }
                }
            }
            EvasysProfileSemtypeForm::deleteBySQL("profile_id = :semester_id AND profile_type = :profile_type AND sem_type NOT IN (:sem_types) AND standard = '0'", array(
                'semester_id' => $this->profile->getId(),
                'sem_types' => array_keys(Request::getArray("available_forms_by_type")) ?: array(''),
                'profile_type' => $this->profile_type
            ));

            PageLayout::postSuccess(dgettext("evasys", "Einstellungen wurden gespeichert"));
        }
        $this->redirect($this->profile_type."profile/index");
    }

    public function add_action()
    {
        if ($this->profile_type === "institute") {
            throw new Exception("Not available.");
        }
        PageLayout::setTitle(dgettext("evasys", "Standardwerte für neues Semester erstellen"));
        if (Request::isPost()) {
            set_time_limit(60*60*2);
            $old_profile = Request::option("copy_from") ? EvasysGlobalProfile::find(Request::option("copy_from")) : null;
            EvasysGlobalProfile::copy(Request::option("semester_id"), $old_profile);
            PageLayout::postSuccess(dgettext("evasys", "Neues Semester angelegt"));
            $this->redirect(PluginEngine::getURL($this->plugin, array('semester_id' => Request::option("semester_id")), $this->profile_type."profile/index"));
        }
        $this->semesters = Semester::getAll();
    }




}
