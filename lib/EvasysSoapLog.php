<?php

class EvasysSoapLog extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_soap_logs';
        $config['serialized_fields']['arguments']        = 'JSONArrayObject';
        $config['serialized_fields']['result']           = 'JSONArrayObject';
        $config['registered_callbacks']['after_store'][] = 'cbCleanUp';
        parent::configure($config);
    }

    public function cbCleanUp()
    {
        if (mt_rand(1,00) < 5) {
             DbManager::get()->exec("DELETE FROM evasys_soap_logs WHERE mkdate < UNIX_TIMESTAMP() - 86400 * 10");
        }
    }
}
