<?php

class EvasysProfileSemtypeForm extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_profiles_semtype_forms';
        $config['belongs_to']['form'] = array(
            'class_name' => 'EvasysForm',
            'foreign_key' => 'form_id'
        );
        parent::configure($config);
    }
}