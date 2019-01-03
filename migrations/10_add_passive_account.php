<?php

class AddPassiveAccount extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_ENABLE_PASSIVE_ACCOUNT", array(
            'value' => 1,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Is the feature to edit the evaluation profiles on in Stud.IP?"
        ));
        Config::get()->create("EVASYS_INTERNAL_USER_ID", array(
            'value' => "",
            'type' => "string",
            'range' => "user",
            'section' => "EVASYS_PLUGIN",
            'description' => "UserId of EvaSys."
        ));
    }
    
    public function down()
    {
        Config::get()->delete("EVASYS_ENABLE_PASSIVE_ACCOUNT");
        Config::get()->delete("EVASYS_INTERNAL_USER_ID");
    }
}
