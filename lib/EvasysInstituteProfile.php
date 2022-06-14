<?php

class EvasysInstituteProfile extends SimpleORMap
{

    protected static function configure($config = [])
    {
        $config['db_table'] = 'evasys_institute_profiles';
        $config['belongs_to']['institute'] = [
            'class_name'  => 'Institute',
            'foreign_key' => 'institut_id'
        ];
        $config['belongs_to']['global_profile'] = [
            'class_name' => 'EvasysGlobalProfile',
            'foreign_key' => 'semester_id'
        ];
        $config['belongs_to']['semester'] = [
            'class_name' => 'Semester',
            'foreign_key' => 'semester_id'
        ];
        $config['has_many']['semtype_forms'] = [
            'class_name' => 'EvasysProfileSemtypeForm',
            'foreign_key' => 'profile_id',
            'foreign_key' => function($profile) {
                return [$profile->getId(), "institute"];
            },
            'assoc_func' => 'findByProfileAndType',
            'on_delete'  => 'delete',
            'on_store'  => 'store'
        ];
        $config['i18n_fields']['mail_reminder_subject'] = true;
        $config['i18n_fields']['mail_reminder_body'] = true;
        $config['i18n_fields']['mail_begin_subject'] = true;
        $config['i18n_fields']['mail_begin_body'] = true;
        $config['i18n_fields']['mail_apply_subject'] = true;
        $config['i18n_fields']['mail_apply_body'] = true;
        $config['i18n_fields']['mail_changed_subject'] = true;
        $config['i18n_fields']['mail_changed_body'] = true;
        parent::configure($config);
    }

    static public function findByInstitute($institut_id, $semester_id = null)
    {
        $semester = $semester_id ? Semester::find($semester_id) : Semester::findCurrent(); //findNext ?
        if (!$semester) {
            return null;
        }
        $profile = self::findOneBySQL("institut_id = ? AND semester_id = ?", [
            $institut_id,
            $semester->getId()
        ]);
        if (!$profile) {
            $profile = new EvasysInstituteProfile();
            $profile['institut_id'] = $institut_id;
            $profile['semester_id'] = $semester->getId();
        }
        return $profile;
    }

    public function getParentsDefaultValue($field)
    {
        if ($this->institute && !$this->institute->isFaculty()) {
            $profile = self::findByInstitute($this->institute['fakultaets_id'], $this['semester_id']);
            return $profile[$field] ?: $profile->getParentsDefaultValue($field);
        } else {
            $profile = EvasysGlobalProfile::find($this['semester_id']);
            return $profile[$field];
        }
    }

    public function getParentsAvailableForms($sem_type_id)
    {
        if ($this->institute && !$this->institute->isFaculty()) {
            $profile = self::findByInstitute($this->institute['fakultaets_id'], $this['semester_id']);

            $forms = EvasysProfileSemtypeForm::findBySQL("profile_id = :profile_id AND sem_type = :sem_type_id AND profile_type = 'institute' ORDER BY `standard` DESC, position ASC", [
                'profile_id' => $profile->getId(),
                'sem_type_id' => $sem_type_id
            ]);
            if (!empty($forms)) {
                return $forms;
            } else {
                return $profile->getParentsAvailableForms($sem_type_id);
            }
        } else {
            $profile = new EvasysGlobalProfile($this['semester_id']);
            return EvasysProfileSemtypeForm::findBySQL("profile_id = :profile_id AND sem_type = :sem_type_id AND profile_type = 'global' ORDER BY `standard` DESC, position ASC", [
                'profile_id' => $profile->getId(),
                'sem_type_id' => $sem_type_id
            ]);
        }
    }

    public function copyToNewSemester($semester_id)
    {
        $new_profile = new EvasysInstituteProfile();
        $data = $this->toArray();
        unset($data['begin']);
        unset($data['end']);
        unset($data['adminedit_begin']);
        unset($data['adminedit_end']);
        unset($data['mkdate']);
        unset($data['chdate']);
        $new_profile->setData($data);
        $new_profile->setId($new_profile->getNewId());
        $new_profile['semester_id'] = $semester_id;
        $new_profile['user_id'] = $GLOBALS['user']->id;
        $new_profile->store();

        $statement = DBManager::get()->prepare("
            INSERT IGNORE INTO evasys_profiles_semtype_forms (profile_form_id, profile_id, profile_type, sem_type, form_id, standard, chdate, mkdate)
            SELECT MD5(CONCAT(profile_form_id, :new_profile, standard)), :new_profile, profile_type, sem_type, form_id, standard, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
            FROM evasys_profiles_semtype_forms
            WHERE profile_id = :old_profile
                AND profile_type = 'institute'
        ");
        $statement->execute([
            'new_profile' => $new_profile->getId(),
            'old_profile' => $this->getId()
        ]);

        return $new_profile;
    }
}
