<?php

class AddLogDirSetting extends Migration
{
    public function up()
    {
        Config::get()->create('EVASYS_LOGPATH', array(
            'value' => '',
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => 'Path for evasys-plugin debug-log. If empty, it defaults to $GLOBALS[\'TMP_PATH\'].'
        ));
    }

    public function down()
    {
        Config::get()->delete('EVASYS_LOGPATH');
    }
}
