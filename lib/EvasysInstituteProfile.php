<?php

class EvasysInstituteProfile extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_institute_profiles';
        $config['belongs_to']['institute'] = array(
            'class_name' => 'Institute',
            'foreign_key' => 'institut_id'
        );
        $config['belongs_to']['semester'] = array(
            'class_name' => 'Semester',
            'foreign_key' => 'semester_id'
        );
        parent::configure($config);
    }

    static public function findByInstitute($institut_id)
    {
        $semester = Semester::findCurrent(); //findNext ?
        $profile = self::findOneBySQL("institut_id = ? AND semester_id = ?", array(
            $institut_id,
            $semester->getId()
        ));
        return $profile;
    }
}