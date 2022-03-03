<?php

class EvasysProfileSemtypeForm extends SimpleORMap {

    protected static function configure($config = [])
    {
        $config['db_table'] = 'evasys_profiles_semtype_forms';
        $config['belongs_to']['form'] = [
            'class_name' => 'EvasysForm',
            'foreign_key' => 'form_id'
        ];
        parent::configure($config);
    }

    public static function findByProfileAndType($profile_id, $profile_type = null) {
        if (!$profile_type && is_array($profile_id)) {
            $profile_type = $profile_id[1];
            $profile_id = $profile_id[0];
        }
        return self::findBySQL("profile_id = ? AND profile_type = ?", [$profile_id, $profile_type]);
    }
}
