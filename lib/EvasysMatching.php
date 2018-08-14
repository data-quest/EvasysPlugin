<?php

class EvasysMatching extends SimpleORMap {

    static public function instituteName($institut_id)
    {
        $matching = self::findOneBySQL("item_id = ? AND item_type = 'institute' ", array($institut_id));
        if ($matching) {
            return $matching['name'];
        } else {
            return Institute::find($institut_id)->name;
        }
    }

    static public function semtypeName($sem_type_id)
    {
        $matching = self::findOneBySQL("item_id = ? AND item_type = 'semtype' ", array($sem_type_id));
        if ($matching) {
            return $matching['name'];
        } else {
            return $GLOBALS['SEM_TYPE'][$sem_type_id]['name'];
        }
    }

    static public function wording($text)
    {
        $matching = self::findOneBySQL("item_id = ? AND item_type = 'wording' ", array(md5($text)));
        if ($matching) {
            return $matching['name'];
        } else {
            return gettext($text);
        }
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_matchings';
        $config['i18n_fields']['name'] = true;
        parent::configure($config);
    }

}