<?php

class GlobalprofileController extends PluginController
{
    public $profile_type = "global";

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if ($this->profile_type === "global") {
            Navigation::activateItem("/admin/evasys/globalprofile");
        } else {
            if (Navigation::hasItem("/admin/evasys/instituteprofile")) {
                Navigation::activateItem("/admin/evasys/instituteprofile");
            } else {
                Navigation::activateItem("/admin/institute/evalprofile");
            }
        }
    }

    public function index_action()
    {
        PageLayout::setTitle($this->plugin->getDisplayName());
        if ($this->profile_type === "global") {
            $this->profile = EvasysGlobalProfile::findCurrent();
        } elseif($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT && $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT !== "all") {
            $this->profile = EvasysInstituteProfile::findByInstitute($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT);
            if (!$this->profile) {
                $this->profile = new EvasysInstituteProfile();
                $this->profile['institut_id'] = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;
                $this->profile['semester_id'] = Semester::findCurrent()->id;
            }
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

        $this->render_template("globalprofile/index", $this->layout);
    }

    public function edit_action()
    {
        if ($this->profile_type === "global") {
            $this->profile = EvasysGlobalProfile::findCurrent();
        } elseif($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT && $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT !== "all") {
            $this->profile = EvasysInstituteProfile::findByInstitute($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT);
            if (!$this->profile) {
                $this->profile = new EvasysInstituteProfile();
                $this->profile['institut_id'] = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;
                $this->profile['semester_id'] = Semester::findCurrent()->id;
            }
        }
        if (Request::isPost()) {
            $data = Request::getArray("data");
            $data['begin'] = $data['begin'] ? strtotime($data['begin']) : null;
            $data['end'] = $data['end'] ? strtotime($data['end']) : null;
            $data['form_id'] = $data['form_id'] ?: null;
            $data['mode'] = $data['mode'] ?: null;
            $data['antrag_begin'] = $data['antrag_begin'] ? strtotime($data['antrag_begin']) : null;
            $data['antrag_end'] = $data['antrag_end'] ? strtotime($data['antrag_end']) : null;
            $data['antrag_info'] = $data['antrag_info'] ?: null;
            $data['user_id'] = $GLOBALS['user']->id;
            $this->profile->setData($data);
            $this->profile->store();

            //now edit all the form-relations for the global profile:
            foreach (Request::getArray("forms_by_type") as $sem_type => $form_id) {
                $entry = EvasysProfileSemtypeForm::findOneBySQL("profile_id = :semester_id AND profile_type = :profile_type AND sem_type = :sem_type AND standard = '1' ", array(
                    'semester_id' => $this->profile->getId(),
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

            PageLayout::postSuccess(_("Einstellungen wurden gespeichert"));
        }
        $this->redirect($this->profile_type."profile/index");
    }


}