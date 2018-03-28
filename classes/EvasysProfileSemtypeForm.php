<?php

class EvasysProfileSemtypeForm extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_profiles_semtype_forms';
        parent::configure($config);
    }
}