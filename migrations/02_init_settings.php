<?php
class InitSettings extends DBMigration
{
	function up(){
		DBManager::get()->exec("
        INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
        VALUES
            ('fe9a7eb19863a1af11533b17b3743d8f', '', 'EVASYS_WSDL', '', 0, 'string', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'path to the wsdl-file on the evasys-server', '', ''),
            ('a127ccff79d0e47ae41c0c38bbc681a1', '', 'EVASYS_URI', '', 0, 'string', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'path to evasys-server like http://yourdomain/evasys/ reachable by studip', '', ''),
            ('b2652a20a7469f8039178e28708469c5', '', 'EVASYS_USER', '', 0, 'string', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'user to authenticate at the evasys-server', '', ''),
            ('f7f1f00c42b7486b6c4b5e431f76f064', '', 'EVASYS_PASSWORD', '', 0, 'string', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'password to authenticate at the evasys-server', '', ''),
            ('4bd6bd27ec01ef3c09d6d28f9c25bc26', '', 'EVASYS_CACHE', '', 0, 'integer', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'minutes to cache all results coming from evasys. 15 might be a good value.', '', ''),
            ('cfd00f0e92fde9f42f88171cba158d26', '', 'EVASYS_PUBLISH_RESULTS', '', 1, 'boolean', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'should students see the results of an evaluation?', '', '')
        ");
	}
}