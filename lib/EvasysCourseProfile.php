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
        $config['serialized_fields']['teachers'] = "JSONArrayObject";
        $config['serialized_fields']['surveys'] = "JSONArrayObject";

        $config['registered_callbacks']['before_store'] = ['profileUpdated'];
        $config['registered_callbacks']['after_delete'] = ['profileDeleted'];
        parent::configure($config);
    }

    public function profileUpdated()
    {
        $old_values = $this->content_db;
        $applied = !$old_values['applied'] && $this['applied'];
        $is_dozent = DBManager::get()->prepare("
            SELECT 1 
            FROM seminar_user
            WHERE seminar_user.Seminar_id = :seminar_id
                AND user_id = :user_id
                AND status = 'dozent'
        ");
        $is_dozent->execute(array(
            'user_id' => $GLOBALS['user']->id,
            'seminar_id' => $this['seminar_id']
        ));
        $is_dozent = (bool) $is_dozent->fetch(PDO::FETCH_COLUMN, 0);
        if ($applied && $is_dozent) {
            //Nachricht an zentrale QM und an Mitdozenten:
            $statement = DBManager::get()->prepare("
                SELECT roles_user.userid
                FROM roles_user
                    INNER JOIN roles ON (roles_user.roleid = roles.roleid)
                WHERE roles.rolename = :role
                UNION DISTINCT SELECT auth_user_md5.user_id
                FROM auth_user_md5
                    INNER JOIN seminar_user ON (seminar_user.user_id = auth_user_md5.user_id)
                WHERE seminar_user.status = 'dozent'
                    AND seminar_user.Seminar_id = :seminar_id
            ");
            $statement->execute(array(
                "role" => "Evasys-Admin",
                'seminar_id' => $this['seminar_id']
            ));
            $user_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            $oldbase = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
            $messaging = new messaging();
            foreach ($user_ids as $user_id) {
                if ($user_id !== $GLOBALS['user']->id) {
                    $link = URLHelper::getURL("plugins.php/evasysplugin/profile/edit/" . $this['seminar_id'], array('cid' => $this['seminar_id']));
                    $message = sprintf(
                            _("%s hat eine Lehrevaluation für die Veranstaltung %s beantragt. Sie können die Evaluationsdaten hier einsehen:"),
                            get_fullname($GLOBALS['user']->id),
                            $this->course->name
                        ) . "\n\n" . $link;
                    $messaging->insert_message(
                        $message,
                        get_username($user_id),
                        "____%system%____",
                        "",
                        "",
                        "",
                        "",
                        sprintf(
                            _("Lehrevaluation für Veranstaltung %s wurde von %s beantragt"),
                            $this->course->name,
                            get_fullname($user_id)
                        ),
                        true,
                        "normal",
                        array("Lehrevaluation")
                    );
                }
            }
            URLHelper::setBaseURL($oldbase);
        }

        $new_values = $this->toArray(array_keys($old_values));
        foreach ($old_values as $key => $value) {
            if ($value == $new_values) {
                unset($old_values[$key]);
                unset($new_values[$key]);
            } else {
                $old_values[$key] = (string) $old_values[$key];
                $new_values[$key] = (string) $new_values[$key];
            }
        }

        StudipLog::log(
            $applied ? 'EVASYS_EVAL_APPLIED' : 'EVASYS_EVAL_UPDATE',
            $GLOBALS['user']->id,
            $this['seminar_id'],
            Semester::findCurrent()->id,
            json_encode([
                'old' => $old_values,
                'new' => $new_values
            ])
        );
        if ($this['by_dozent'] || $old_values['by_dozent']) {
            //Nachricht an Dozenten, wenn ihre Evaluation verändert wird vom Admin:
            if (EvasysPlugin::isRoot()
                || EvasysPlugin::isAdmin($this['seminar_id'])
                || Config::get()->EVASYS_ENABLE_MESSAGE_FOR_ADMINS
            ) {
                //Nur Dozenten einsammeln
                $statement = DBManager::get()->prepare("
                    SELECT username
                    FROM auth_user_md5
                        INNER JOIN seminar_user ON (seminar_user.user_id = auth_user_md5.user_id)
                    WHERE seminar_user.status = 'dozent'
                        AND seminar_user.Seminar_id = :seminar_id
                ");
                $statement->execute(array(
                    'seminar_id' => $this['seminar_id']
                ));
                $dozenten = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

                foreach ($dozenten as $dozent_username) {
                    if ($dozent_username !== $GLOBALS['user']->username) {
                        $messaging = new messaging();
                        $oldbase = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
                        $message = sprintf(
                            _("%s hat gerade die Lehrevaluationsdaten der Veranstaltung %s verändert. Die geänderten Daten können Sie hier einsehen und gegebenenfalls bearbeiten: \n\n %s"),
                            get_fullname($GLOBALS['user']->id),
                            $this->course['name'],
                            URLHelper::getURL("plugins.php/evasysplugin/profile/edit/" . $this['seminar_id'], array('cid' => $profile['seminar_id']), true)
                        );
                        $messaging->insert_message(
                            $message,
                            $dozent_username,
                            "____%system%____",
                            "",
                            "",
                            "",
                            "",
                            _("Bearbeitung der Evaluationsdaten"),
                            true,
                            "normal",
                            array("Lehrevaluation")
                        );
                        URLHelper::setBaseURL($oldbase);
                    }
                }
            }

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
            $form_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            if (count($form_ids)) {
                return $form_ids;
            }
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
                $form_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
                if (count($form_ids)) {
                    return $form_ids;
                }
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
            $form_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            if (count($form_ids)) {
                return $form_ids;
            }
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

    public function getPresetAttribute($attribute)
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
        if ($GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'])
                && !(EvasysPlugin::isAdmin($this['seminar_id']) || EvasysPlugin::isRoot())) {
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
        } elseif(EvasysPlugin::isAdmin($this['seminar_id'])) {
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

        $inst_profile = EvasysInstituteProfile::findByInstitute($this->course->home_institut->getId());
        if ($inst_profile) {
            $emails .= " " . $inst_profile['results_email'];
        }
        $fakultaet_id = $this->course->home_institut->fakultaets_id;

        if ($fakultaet_id !== $institut_id) {
            $inst_profile = EvasysInstituteProfile::findByInstitute($fakultaet_id);

            if ($inst_profile['results_email']) {
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