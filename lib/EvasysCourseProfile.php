<?php

class EvasysCourseProfile extends SimpleORMap {

    static public function findBySemester($seminar_id, $semester_id = null)
    {
        $semester_id || $semester_id = Semester::findCurrent()->id;
        $profile = self::findOneBySQL("seminar_id = :course_id AND semester_id = :semester_id", array(
            'course_id' => $seminar_id,
            'semester_id' => $semester_id
        ));
        if (!$profile) {
            $profile = new EvasysCourseProfile();
            $profile['seminar_id'] = $seminar_id;
            $profile['semester_id'] = $semester_id;
        }
        return $profile;
    }

    static public function findManyBySemester($course_ids, $semester_id = null)
    {
        $semester_id || $semester_id = Semester::findCurrent()->id;
        $profiles = array();
        foreach ($course_ids as $course_id) {
            $profiles[] = self::findBySemester($course_id, $semester_id);
        }
        return $profiles;
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_course_profiles';
        $config['belongs_to']['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'seminar_id'
        );
        $config['belongs_to']['semester'] = array(
            'class_name' => 'Semester',
            'foreign_key' => 'semester_id'
        );
        $config['additional_fields']['final_form_id'] = array(
            'get' => 'getFinalFormId'
        );
        $config['additional_fields']['final_begin'] = array(
            'get' => 'getFinalBegin'
        );
        $config['additional_fields']['final_end'] = array(
            'get' => 'getFinalEnd'
        );
        $config['additional_fields']['final_mode'] = array(
            'get' => 'getFinalmode'
        );
        $config['additional_fields']['final_address'] = array(
            'get' => 'getFinalAddress'
        );
        $config['serialized_fields']['teachers'] = "JSONArrayObject";
        $config['serialized_fields']['surveys'] = "JSONArrayObject";

        $config['registered_callbacks']['before_create'] = ['profileCreated'];
        $config['registered_callbacks']['before_store'] = ['profileUpdated'];
        $config['registered_callbacks']['after_delete'] = ['profileDeleted'];
        parent::configure($config);
    }

    public function profileCreated()
    {
        StudipLog::log(
            'EVASYS_EVAL_APPLIED',
            $this->user_id,
            $this->seminar_id,
            Semester::findCurrent()->id,
            json_encode($this->getLoginfo())
        );
        return true;
    }

    public function profileUpdated()
    {
        $delta_lehrauftrag = $this->getLoginfo($this);
        if (count($delta_lehrauftrag) || count($delta_user) || count($delta_userdata)) {
            ## Lehrauftrag isNew.. weil user und userdata fast immer existieren
            StudipLog::log(
                $this->isNew() ? 'EVASYS_EVAL_APPLIED' : 'EVASYS_EVAL_UPDATE',
                $this->user_id,
                $this->seminar_id,
                Semester::findCurrent()->id,
                json_encode($this->getLoginfo())
            );
        }
        return true;
    }

    public function profileDeleted()
    {
        StudipLog::log(
            'EVASYS_EVAL_DELETE',
            $this->user_id,
            $this->seminar_id,
            Semester::findCurrent()->id
        );
        return true;
    }

    protected function getLoginfo()
    {
        $updated_fields = array();
        if ($lave_object) {
            $meta = $this->getTableMetadata();
            foreach ($meta['fields'] as $field) {
                if ($this->isFieldDirty($field['name'])) {
                    $updated_fields[$field['name']] = $this->getValue($field['name']);
                }
            }
        }
        return $updated_fields;
    }

    /**
     * This method looks for the finalized form_id which is dependend on the settings of the institute-profiles
     * and global profile
     * @return null|string
     */
    public function getFinalFormId()
    {
        $form_id = null;
        if ($this['form_id']) {
            $form_id = $this['form_id'];
        }
        return $this->getPresetFormId($form_id);
    }

    public function getPresetFormId($form_id = null) {
        $institut_id = $this->course['institut_id'];
        $inst_profile = EvasysInstituteProfile::findByInstitute($institut_id);
        $sem_type = $this->course->status;
        if ($inst_profile) {

            $standardform = EvasysProfileSemtypeForm::findOneBySQL("profile_type = :profile_type AND profile_id = :profile_id AND sem_type = :sem_type AND standard = '1'", array(
                'profile_type' => "institute",
                'sem_type' => $sem_type,
                'profile_id' => $inst_profile->getId()
            ));

            $statement = DBManager::get()->prepare("
                SELECT form_id 
                FROM evasys_profiles_semtype_forms
                WHERE profile_type = :profile_type 
                    AND profile_id = :profile_id 
                    AND sem_type = :sem_type 
                    AND standard = '0'
            ");
            $statement->execute(array(
                'profile_type' => "institute",
                'sem_type' => $sem_type,
                'profile_id' => $inst_profile->getId()
            ));
            $available_form_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

            if ($form_id && in_array($form_id, $available_form_ids)) {
                return $form_id;
            } else {
                //now the form_id in database is technically illegal, so reset it to null:
                $form_id = null;
            }
            if ($standardform && !$form_id) {
                return $standardform['form_id'];
            }
            if (!$form_id) {
                $form_id = $inst_profile['form_id'];
            }
        }
        $fakultaet_id = $this->course->home_institut->fakultaets_id;
        if ($fakultaet_id !== $institut_id) {
            $inst_profile = EvasysInstituteProfile::findByInstitute($fakultaet_id);
            if ($inst_profile) { //Do the same thing with this profile:
                $standardform = EvasysProfileSemtypeForm::findOneBySQL("profile_type = :profile_type AND profile_id = :profile_id AND sem_type = :sem_type AND standard = '1'", array(
                    'profile_type' => "institute",
                    'sem_type' => $sem_type,
                    'profile_id' => $inst_profile->getId()
                ));

                $statement = DBManager::get()->prepare("
                    SELECT form_id 
                    FROM evasys_profiles_semtype_forms
                    WHERE profile_type = :profile_type 
                        AND profile_id = :profile_id 
                        AND sem_type = :sem_type 
                        AND standard = '0'
                ");
                $statement->execute(array(
                    'profile_type' => "institute",
                    'sem_type' => $sem_type,
                    'profile_id' => $inst_profile->getId()
                ));
                $available_form_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

                if ($form_id && in_array($form_id, $available_form_ids)) {
                    return $form_id;
                } else {
                    //now the form_id in database is technically illegal, so reset it to null:
                    $form_id = null;
                }
                if ($standardform && !$form_id) {
                    return $standardform['form_id'];
                }
                if (!$form_id) {
                    $form_id = $inst_profile['form_id'];
                }
            }
        }
        $global_profile = EvasysGlobalProfile::findCurrent();
        if ($global_profile) {
            $standardform = EvasysProfileSemtypeForm::findOneBySQL("profile_type = :profile_type AND profile_id = :profile_id AND sem_type = :sem_type AND standard = '1'", array(
                'profile_type' => "global",
                'sem_type' => $sem_type,
                'profile_id' => $global_profile->getId()
            ));

            $statement = DBManager::get()->prepare("
                SELECT form_id 
                FROM evasys_profiles_semtype_forms
                WHERE profile_type = :profile_type 
                    AND profile_id = :profile_id 
                    AND sem_type = :sem_type 
                    AND standard = '0'
            ");
            $statement->execute(array(
                'profile_type' => "global",
                'sem_type' => $sem_type,
                'profile_id' => $global_profile->getId()
            ));
            $available_form_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

            if ($form_id && in_array($form_id, $available_form_ids)) {
                return $form_id;
            } else {
                //now the form_id in database is technically illegal, so reset it to null:
                $form_id = null;
            }
            if ($standardform && !$form_id) {
                return $standardform['form_id'];
            }
            if (!$form_id) {
                $form_id = $global_profile['form_id'];
            }
        }
        return $form_id;
    }

    public function getAvailableFormIds()
    {
        $institut_id = $this->course['institut_id'];
        $inst_profile = EvasysInstituteProfile::findByInstitute($institut_id);
        $sem_type = $this->course->status;
        if ($inst_profile) {
            $statement = DBManager::get()->prepare("
                SELECT form_id 
                FROM evasys_profiles_semtype_forms
                WHERE profile_type = :profile_type 
                    AND profile_id = :profile_id 
                    AND sem_type = :sem_type 
                    AND standard = '0'
                ORDER BY position ASC
            ");
            $statement->execute(array(
                'profile_type' => "institute",
                'sem_type' => $sem_type,
                'profile_id' => $inst_profile->getId()
            ));
            return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        $fakultaet_id = $this->course->home_institut->fakultaets_id;
        if ($fakultaet_id !== $institut_id) {
            $inst_profile = EvasysInstituteProfile::findByInstitute($fakultaet_id);
            if ($inst_profile) { //Do the same thing with this profile:
                $statement = DBManager::get()->prepare("
                    SELECT form_id 
                    FROM evasys_profiles_semtype_forms
                    WHERE profile_type = :profile_type 
                        AND profile_id = :profile_id 
                        AND sem_type = :sem_type 
                        AND standard = '0'
                    ORDER BY position ASC
                ");
                $statement->execute(array(
                    'profile_type' => "institute",
                    'sem_type' => $sem_type,
                    'profile_id' => $inst_profile->getId()
                ));
                return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        }
        $global_profile = EvasysGlobalProfile::findCurrent();
        if ($global_profile) {

            $statement = DBManager::get()->prepare("
                SELECT form_id 
                FROM evasys_profiles_semtype_forms
                WHERE profile_type = :profile_type 
                    AND profile_id = :profile_id 
                    AND sem_type = :sem_type 
                    AND standard = '0'
                ORDER BY position ASC
            ");
            $statement->execute(array(
                'profile_type' => "global",
                'sem_type' => $sem_type,
                'profile_id' => $global_profile->getId()
            ));
            return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        $statement = DBManager::get()->prepare("
            SELECT form_id 
            FROM evasys_forms
            WHERE active = '1' 
            ORDER BY name ASC
        ");
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getFinalBegin()
    {
        return $this->getFinalAttribute("begin");
    }

    public function getPresetBegin()
    {
        return $this->getPresetAttribute("begin");
    }

    public function getFinalEnd()
    {
        return $this->getFinalAttribute("end");
    }

    public function getPresetEnd()
    {
        return $this->getPresetAttribute("end");
    }

    public function getFinalMode()
    {
        return $this->getFinalAttribute("mode");
    }

    public function getPresetMode()
    {
        return $this->getPresetAttribute("mode");
    }

    public function getFinalAddress()
    {
        return $this->getFinalAttribute("address");
    }

    public function getPresetAddress()
    {
        return $this->getPresetAttribute("address");
    }

    /**
     * Returns the given attribute finalized, which means that if the course-profile doesn't have this attribute set
     * we try to look at the institute-profile or the faculty-profile or the global profile and return the attribute
     * from there.
     * @param string $attribute
     * @return mixed|null
     */
    protected function getFinalAttribute($attribute)
    {
        if ($this[$attribute]) {
            return $this[$attribute];
        } else {
            return $this->getPresetAttribute($attribute);
        }
    }

    protected function getPresetAttribute($attribute)
    {
        $institut_id = $this->course['institut_id'];
        $inst_profile = EvasysInstituteProfile::findByInstitute($institut_id);
        if ($inst_profile[$attribute]) {
            return $inst_profile[$attribute];
        }
        $fakultaet_id = $this->course->home_institut->fakultaets_id;
        if ($fakultaet_id !== $institut_id) {
            $inst_profile = EvasysInstituteProfile::findByInstitute($fakultaet_id);
            if ($inst_profile[$attribute]) {
                return $inst_profile[$attribute];
            }
        }
        $global_profile = EvasysGlobalProfile::findCurrent();
        if ($global_profile[$attribute]) {
            return $global_profile[$attribute];
        }
        // else ...
        return null;
    }

    public function isEditable()
    {
        if ($GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id']) && !$GLOBALS['perm']->have_studip_perm("admin", $this['seminar_id'])) {
            if ($this['applied'] && !$this['by_dozent']) {
                return false;
            }
            $begin = $this->getPresetAttribute("antrag_begin");
            $end = $this->getPresetAttribute("antrag_end");
            if ($begin && (time() >= $begin) && ((time() <= $end) || !$end)) {
                return true;
            } else {
                return false;
            }
        } elseif(EvasysPlugin::isRoot()) {
            return true;
        } elseif(EvasysPlugin::isAdmin() && $GLOBALS['perm']->have_studip_perm("admin", $this['seminar_id'])) {
            $global_profile = EvasysGlobalProfile::findCurrent();
            return (
                $global_profile['adminedit_begin']
                && ($global_profile['adminedit_begin'] <= time())
                && (($global_profile['adminedit_end'] > time()) || !$global_profile['adminedit_end'])
            );
        } else {
            //TODO: check for new role of EVAL_ADMIN
        }
        return false;
    }

    public function getAntragInfo()
    {
        return $this->getPresetAttribute("antrag_info");
    }

    public function hasDatesInEvalTimespan()
    {
        $profile = EvasysCourseProfile::findBySemester($this['seminar_id']);
        $begin = $profile->getFinalBegin();
        $end = $profile->getFinalEnd();
        if (!$begin || !$end) {
            $semester = Semester::findCurrent();
            if (!$begin) {
                $begin = $semester['beginn'];
            }
            if (!$end) {
                $end = $semester['ende'];
            }
        }
        $statement = DBManager::get()->prepare("
            SELECT 1
            FROM termine
            WHERE range_id = :course_id
                AND (
                    (date >= :begin AND date < :end)
                    OR (end_time > :begin AND end_time <= :end)
                    OR (date < :begin AND end_time > :end)
                )
        ");
        $statement->execute(array(
            'course_id' => $this['Seminar_id'],
            'begin' => $begin,
            'end' => $end
        ));
        return (bool) $statement->fetch(PDO::FETCH_COLUMN, 0);
    }

    public function getFinalResultsEmails()
    {
        $emails = $profile['results_email'];

        $inst_profile = EvasysInstituteProfile::findByInstitute($institut_id);
        if ($inst_profile) {
            $emails .= " " . $inst_profile['results_email'];
        }
        $fakultaet_id = $this->course->home_institut->fakultaets_id;
        if ($fakultaet_id !== $institut_id) {
            $inst_profile = EvasysInstituteProfile::findByInstitute($fakultaet_id);
            if ($inst_profile[$attribute]) {
                $emails .= " ".$inst_profile['results_email'];
            }
        }
        if (!trim($emails)) {
            return array();
        } else {
            $emails = preg_split("/[\s,;]+/", strtolower($emails), -1, PREG_SPLIT_NO_EMPTY);
            return array_unique($emails);
        }
    }

    public function getFinalTeilnehmer()
    {
        return count($this->course->members->filter(function ($member) {
            return in_array($member['status'], array("autor", "user", "tutor"));
        }));
    }
}