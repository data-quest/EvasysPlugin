<?php
class InitPlugin extends DBMigration
{
	function up(){
		DBManager::get()->exec("
        CREATE TABLE IF NOT EXISTS `evasys_seminar` (
            `Seminar_id` varchar(32) NOT NULL,
            `evasys_id` varchar(32) NOT NULL DEFAULT '',
            `activated` tinyint(4) NOT NULL DEFAULT '0',
            `publishing_allowed` tinyint(4) NOT NULL DEFAULT '0',
            PRIMARY KEY (`Seminar_id`, `evasys_id`)
        ) ENGINE=MyISAM
        ");
	}
}
