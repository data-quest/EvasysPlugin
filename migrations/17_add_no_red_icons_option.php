<?php

class AddNoRedIconsOption extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_NO_RED_ICONS", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Disables the red icon in the course summary for students. Probably for performance-issues."
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_NO_RED_ICONS");
    }
}
