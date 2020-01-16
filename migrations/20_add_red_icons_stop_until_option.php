<?php

class AddRedIconsStopUntilOption extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_RED_ICONS_STOP_UNTIL", array(
            'value' => 0,
            'type' => "integer",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Until what timestamp should the red icons be stopped? This is meant to provide a pause for EvaSys when it has too much to do. 0 means that red icons are not stopped at all."
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_RED_ICONS_STOP_UNTIL");
    }
}
