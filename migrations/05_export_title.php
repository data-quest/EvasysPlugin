<?php
class ExportTitle extends Migration
{
	function up(){
		DBManager::get()->exec("
        INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
        VALUES
            (MD5('EVASYS_EXPORT_TITLES'), '', 'EVASYS_EXPORT_TITLES', '', 0, 'string', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Should the title of teachers be exported to EvaSys or not.', '', '')
        ");
	}
}