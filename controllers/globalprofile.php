<?php

class GlobalprofileController extends PluginController
{

    public function index_action()
    {
        Navigation::activateItem("/admin/evasys/globalprofile");
        PageLayout::setTitle($this->plugin->getDisplayName());
        $this->profile = EvasysGlobalProfile::findCurrent();
        $this->con = "globalprofile";

        $statement = DBManager::get()->prepare("
            SELECT sem_type, form_id
            FROM evasys_profiles_semtype_forms
            WHERE profile_id = :semester_id
                AND profile_type = 'global'
                AND standard = '1'
        ");
        $statement->execute(array(
            'semester_id' => $this->profile->getId()
        ));
        $this->forms_by_type = $statement->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);

        $statement = DBManager::get()->prepare("
            SELECT sem_type, form_id
            FROM evasys_profiles_semtype_forms
            WHERE profile_id = :semester_id
                AND profile_type = 'global'
                AND standard = '0'
        ");
        $statement->execute(array(
            'semester_id' => $this->profile->getId()
        ));
        $this->available_forms_by_type = $statement->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);

        //var_dump($this->forms_by_type); die();
    }

    public function edit_action()
    {
        $this->profile = EvasysGlobalProfile::findCurrent();
        if (Request::isPost()) {
            $data = Request::getArray("data");
            $data['begin'] = $data['begin'] ? strtotime($data['begin']) : null;
            $data['end'] = $data['end'] ? strtotime($data['end']) : null;
            $data['mode'] = $data['mode'] ?: null;
            $this->profile->setData($data);
            $this->profile->store();

            //now edit all the form-relations for the global profile:
            foreach (Request::getArray("forms_by_type") as $sem_type => $form_id) {
                $entry = EvasysProfileSemtypeForm::findOneBySQL("profile_id = :semester_id AND profile_type = 'global' AND sem_type = :sem_type AND standard = '1' ", array(
                    'semester_id' => $this->profile->getId(),
                    'sem_type' => $sem_type
                ));
                if (!$entry) {
                    $entry = new EvasysProfileSemtypeForm();
                    $entry['profile_id'] = $this->profile->getId();
                    $entry['profile_type'] = "global";
                    $entry['sem_type'] = $sem_type;
                    $entry['standard'] = 1;
                }
                if ($form_id) {
                    $entry['form_id'] = $form_id;
                    $entry->store();
                } else {
                    $entry->delete();
                }
                EvasysProfileSemtypeForm::deleteBySQL("profile_id = :semester_id AND profile_type = 'global' AND sem_type = :sem_type AND standard = '1' AND form_id != :form_id", array(
                    'semester_id' => $this->profile->getId(),
                    'sem_type' => $sem_type,
                    'form_id' => $form_id
                ));
            }

            foreach (Request::getArray("available_forms_by_type") as $sem_type => $form_ids) {
                EvasysProfileSemtypeForm::deleteBySQL("profile_id = :semester_id AND profile_type = 'global' AND sem_type = :sem_type AND standard = '0' AND form_id NOT IN (:form_ids)", array(
                    'semester_id' => $this->profile->getId(),
                    'sem_type' => $sem_type,
                    'form_ids' => $form_ids
                ));

                foreach ($form_ids as $form_id) {
                    if ($form_id) {
                        $entry = EvasysProfileSemtypeForm::findOneBySQL("profile_id = :semester_id AND profile_type = 'global' AND sem_type = :sem_type AND form_id = :form_id AND standard = '0' ", array(
                            'semester_id' => $this->profile->getId(),
                            'sem_type' => $sem_type,
                            'form_id' => $form_id
                        ));
                        if (!$entry) {
                            $entry = new EvasysProfileSemtypeForm();
                            $entry['profile_id'] = $this->profile->getId();
                            $entry['profile_type'] = "global";
                            $entry['sem_type'] = $sem_type;
                            $entry['standard'] = 0;
                        }
                        $entry['form_id'] = $form_id;
                        $entry->store();
                    }
                }
            }
            EvasysProfileSemtypeForm::deleteBySQL("profile_id = :semester_id AND profile_type = 'global' AND sem_type NOT IN (:sem_types) AND standard = '0'", array(
                'semester_id' => $this->profile->getId(),
                'sem_types' => array_keys(Request::getArray("available_forms_by_type")) ?: array('')
            ));

            PageLayout::postSuccess(_("Einstellungen wurden gespeichert"));
        }
        $this->redirect("globalprofile/index");
    }


}