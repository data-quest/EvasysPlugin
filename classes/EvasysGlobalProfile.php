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
        }
        return $profile;
    }
}