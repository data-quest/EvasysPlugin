<?php

class AddEvasysPluginLowPrivilegeConfig extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Allow users with 'user' permissions to evaluate courses."
        ));
    }
    
    public function down()
    {
        Config::get()->delete("EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS");
    }
}
