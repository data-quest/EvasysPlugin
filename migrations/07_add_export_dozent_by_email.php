<?php
class AddExportDozentByEmail extends Migration
{
	function up(){
		DBManager::get()->exec("
        INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
        VALUES
            (MD5('EVASYS_EXPORT_DOZENT_BY_FIELD'), '', 'EVASYS_EXPORT_DOZENT_BY_FIELD', 'user_id', 0, 'string', 'global', 'EVASYS_PLUGIN', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Which field should be used to export the dozent (user_id, email, datafield_id).', '', '')
        ");
	}
}