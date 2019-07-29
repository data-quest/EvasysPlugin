<?php

class AddLogDirSetting extends Migration
{
    public function up()
    {
        Config::get()->create('EVASYS_LOGPATH', array(
            'value' => $GLOBALS['TMP_PATH'],
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Path for evasys-plugin debug-log."
        ));
    }

    public function down()
    {
        Config::get()->delete('EVASYS_LOGPATH');
    }
}
