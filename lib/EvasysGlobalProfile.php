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
                    $data = $last_profile->toRawArray();
                    unset($profile['begin']);
                    unset($profile['end']);
                    unset($profile['mkdate']);
                    unset($profile['chdate']);
                    $profile->setData($data);
                }
            }
            $profile->setId($semester->getId());
            $profile->store();

            //Taking over standard and available forms:
            $statement = DBManager::get()->prepare("
                INSERT INTO evasys_profiles_semtype_forms (profile_form_id, profile_id, profile_type, sem_type, form_id, standard, chdate, mkdate)
                SELECT MD5(CONCAT(profile_form_id, :new_semester, standard)), :new_semester, profile_type, sem_type, form_id, standard, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
                FROM evasys_profiles_semtype_forms
                WHERE profile_id = :old_semester
            ");
            $statement->execute(array(
                'new_semester' => $semester->getId(),
                'old_semester' => $last_semester->getId()
            ));

            //We should also take over the old institute_profiles:
            $statement = DBManager::get()->prepare("
                INSERT INTO evasys_institute_profiles (institute_profile_id, institut_id, semester, form_id, `mode`, address, antrag_info, chdate, mkdate)
                SELECT MD5(CONCAT(institute_profile_id, :new_semester), institut_id, :new_semester, form_id, `mode`, address, antrag_info, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
                FROM evasys_institute_profiles
                WHERE semester_id = :old_semester
            ");
            $statement->execute(array(
                'new_semester' => $semester->getId(),
                'old_semester' => $last_semester->getId()
            ));
        }
        return $profile;
    }
}