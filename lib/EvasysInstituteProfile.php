<?php

class EvasysInstituteProfile extends SimpleORMap
{

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_institute_profiles';
        $config['belongs_to']['institute'] = array(
            'class_name'  => 'Institute',
            'foreign_key' => 'institut_id'
        );
        $config['belongs_to']['global_profile'] = array(
            'class_name' => 'EvasysGlobalProfile',
            'foreign_key' => 'semester_id'
        );
        $config['belongs_to']['semester'] = array(
            'class_name' => 'Semester',
            'foreign_key' => 'semester_id'
        );
        $config['has_many']['semtype_forms'] = array(
            'class_name' => 'EvasysProfileSemtypeForm',
            'foreign_key' => 'profile_id',
            'foreign_key' => function($profile) {
                return [$profile->getId(), "institute"];
            },
            'assoc_func' => 'findByProfileAndType',
            'on_delete'  => 'delete',
            'on_store'  => 'store'
        );
        parent::configure($config);
    }

    static public function findByInstitute($institut_id, $semester_id = null)
    {
        $semester = $semester_id ? Semester::find($semester_id) : Semester::findCurrent(); //findNext ?
        if (!$semester) {
            return null;
        }
        $profile = self::findOneBySQL("institut_id = ? AND semester_id = ?", array(
            $institut_id,
            $semester->getId()
        ));
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
            $profile = self::findByInstitute($this->institute['fakultaets_id']);
            return $profile[$field] ?: $profile->getParentsDefaultValue($field);
        } else {
            $profile = EvasysGlobalProfile::findCurrent();
            return $profile[$field];
        }
    }

    public function getParentsAvailableForms($sem_type_id)
    {
        if ($this->institute && !$this->institute->isFaculty()) {
            $profile = self::findByInstitute($this->institute['fakultaets_id']);

            $forms = EvasysProfileSemtypeForm::findBySQL("profile_id = :profile_id AND sem_type = :sem_type_id AND profile_type = 'institute' ORDER BY `standard` DESC, position ASC", array(
                'profile_id' => $profile->getId(),
                'sem_type_id' => $sem_type_id
            ));
            if (count($forms)) {
                return $forms;
            } else {
                return $profile->getParentsAvailableForms($sem_type_id);
            }
        } else {
            $profile = EvasysGlobalProfile::findCurrent();
            return EvasysProfileSemtypeForm::findBySQL("profile_id = :profile_id AND sem_type = :sem_type_id AND profile_type = 'global' ORDER BY `standard` DESC, position ASC", array(
                'profile_id' => $profile->getId(),
                'sem_type_id' => $sem_type_id
            ));
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

        $semtypeforms = EvasysProfileSemtypeForm::findBySQL("profile_id = ? AND profile_type = 'institute'", array($this->getId()));
        foreach ($semtypeforms as $semtypeform) {
            $new_semtypeform = new EvasysProfileSemtypeForm();
            $new_semtypeform->setData($semtypeform->toArray());
            $new_semtypeform->setId($new_semtypeform->getNewId());
            $new_semtypeform['profile_id'] = $new_profile->getId();
            $new_semtypeform['mkdate'] = time();
            $new_semtypeform['chdate'] = time();
            $new_semtypeform->store();
        }

        return $new_profile;
    }
}