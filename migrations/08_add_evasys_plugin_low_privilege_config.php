<?php

class AddEvasysPluginLowPrivilegeConfig extends Migration
{
    public function up()
    {
        $db = DBManager::get();
        
        $db->exec("
            INSERT IGNORE INTO `config` (
                `config_id`,
                `parent_id`,
                `field`,
                `value`,
                `is_default`,
                `type`,
                `range`,
                `section`,
                `position`,
                `mkdate`,
                `chdate`,
                `description`,
                `comment`,
                `message_template`
            ) 
            VALUES
                (
                    MD5('EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS'),
                    '',
                    'EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS',
                    '0',
                    0,
                    'bool',
                    'global',
                    'EVASYS_PLUGIN',
                    0,
                    UNIX_TIMESTAMP(),
                    UNIX_TIMESTAMP(),
                    'Allow users with \'user\' permissions to evaluate courses.',
                    '',
                    ''
                );
        ");
        
        $db = null;
    }
    
    public function down()
    {
        $db = DBManager::get();
        
        $db->exec(
            "DELETE FROM config WHERE field = 'EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS';"
        );
        
        $db = null;
    }
}
