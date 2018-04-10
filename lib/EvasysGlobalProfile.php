<?php

class EvasysGlobalProfile extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_global_profiles';
        $config['belongs_to']['semester'] = array(
            'class_name' => 'Semester',
            'foreign_key' => 'semester_id'
        );
        parent::configure($config);
    }

    /**
     * Finds the current EvasysGlobalProfile object or creates it. Will fail only if we have no current semester.
     * @return EvasysGlobalProfile|NULL
     */
    static public function findCurrent()
    {
        $semester = Semester::findCurrent(); //findNext ?
        if (!$semester) {
            trigger_error("Kein aktuelles Semester, kann kein globales EvasysProfil erstellen.", E_USER_WARNING);
            return false;
        }
        $profile = self::find($semester->getId());
        if (!$profile) {
            $last_semester = Semester::findByTimestamp($semester['beginn'] - 1);
            $profile = new EvasysGlobalProfile();
            if ($last_semester) {
                $last_profile = self::find($last_semester->getId());
                if ($last_profile) {
                    $profile = new EvasysGlobalProfile();
                    $profile->setData($last_profile->toRawArray());
                    $profile['begin'] = null;
                    $profile['end'] = null;
                }
            }
            $profile->setId($semester->getId());
            $profile->store();

            //Taking over standard and available forms:
            $statement = DBManager::get()->prepare("
                INSERT INTO evasys_profiles_semtype_forms (profile_form_id, profile_id, profile_type, sem_type, form_id, standard, chdate, mkdate)
                SELECT MD5(CONCAT(profile_form_id, :new_semester, standard)), :new_semester, profile_type, sem_type, form_id, standard, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
                FROM evasys_profiles_semtype_forms
                WHERE profile_type = 'global'
                    AND profile_id = :old_semester
            ");
            $statement->execute(array(
                'new_semester' => $semester->getId(),
                'old_semester' => $last_semester->getId()
            ));

            //We should also take over the old institute_profiles:
        }
        return $profile;
    }
}