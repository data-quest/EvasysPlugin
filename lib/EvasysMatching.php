<?php

class EvasysMatching extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_matchings';
        parent::configure($config);
    }

}