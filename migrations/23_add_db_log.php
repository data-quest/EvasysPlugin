<?php

class AddDbLog extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            CREATE TABLE `evasys_soap_logs` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `function` varchar(64) NOT NULL DEFAULT '',
                `arguments` text,
                `result` text,
                `user_id` varchar(32) NOT NULL DEFAULT '',
                `time` float DEFAULT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `function` (`function`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down()
    {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `evasys_soap_logs`;
        ");
    }
}
