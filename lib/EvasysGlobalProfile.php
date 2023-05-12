<?php

class EvasysGlobalProfile extends SimpleORMap
{

    static protected $singleton = null;

    protected static function configure($config = [])
    {
        $config['db_table'] = 'evasys_global_profiles';
        $config['belongs_to']['semester'] = [
            'class_name' => 'Semester',
            'foreign_key' => 'semester_id'
        ];
        $config['has_many']['institute_profiles'] = [
            'class_name' => 'EvasysInstituteProfile',
            'foreign_key' => 'semester_id',
            'on_delete'  => 'delete',
            'on_store'  => 'store'
        ];
        $config['has_many']['semtype_forms'] = [
            'class_name' => 'EvasysProfileSemtypeForm',
            'foreign_key' => 'profile_id',
            'foreign_key' => function($profile) {
                return [$profile->getId(), "global"];
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

    /**
     * Finds the current EvasysGlobalProfile object or creates it. Will fail only if we have no current semester.
     * @return EvasysGlobalProfile|NULL
     */
    static public function findCurrent()
    {
        if (self::$singleton) {
            return self::$singleton;
        }

        if (method_exists('Semester', 'findDefault')) {
            $semester = Semester::findDefault();
        } else {
            $semester = Semester::findCurrent();
            if (time() > $semester['ende'] - 86400 * 7 * Config::get()->SEMESTER_TIME_SWITCH) {
                $semester = Semester::findNext();
            }
        }

        if (!$semester) {
            trigger_error("Kein aktuelles Semester, kann kein globales EvaSysProfil erstellen.", E_USER_WARNING);
            return false;
        }
        $profile = self::find($semester->getId());
        if (!$profile) {
            $last_semester = Semester::findByTimestamp($semester['beginn'] - 1);
            $last_profile = self::find($last_semester->getId());
            $profile = self::copy($semester->getId(), $last_profile ?: null);
        }
        self::$singleton = $profile;
        return $profile;
    }

    static public function copy($new_semester_id, $old_profile = null)
    {
        $profile = new EvasysGlobalProfile();

        if ($old_profile) {
            $data = $old_profile->toRawArray();
            unset($data['begin']);
            unset($data['end']);
            unset($data['adminedit_begin']);
            unset($data['adminedit_end']);
            unset($data['mkdate']);
            unset($data['chdate']);
            $profile->setData($data);
        }

        $profile->setId($new_semester_id);
        $profile->store();

        if ($old_profile) {
            //Taking over standard and available forms:
            $statement = DBManager::get()->prepare("
                INSERT IGNORE INTO evasys_profiles_semtype_forms (profile_form_id, profile_id, profile_type, sem_type, form_id, standard, chdate, mkdate)
                SELECT MD5(CONCAT(profile_form_id, :new_semester, standard)), :new_semester, profile_type, sem_type, form_id, standard, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
                FROM evasys_profiles_semtype_forms
                WHERE profile_id = :old_semester
                    AND profile_type = 'global'
            ");
            $statement->execute([
                'new_semester' => $new_semester_id,
                'old_semester' => $old_profile['semester_id']
            ]);

            $institute_profiles = EvasysInstituteProfile::findBySQL("semester_id = ?", [
                $old_profile['semester_id']
            ]);
            foreach ($institute_profiles as $institute_profile) {
                $institute_profile->copyToNewSemester($new_semester_id);
            }
        }
        return $profile;
    }
}
