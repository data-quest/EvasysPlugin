<?php

class AddIndividualTimeOffset extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_INDIVIDUAL_TIME_OFFSETS", array(
            'value' => '',
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "The first line is the minimum minutes the evaluation should start in the future. The second line is the minium minutes for the ending of the evaluation."
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_INDIVIDUAL_TIME_OFFSETS");
    }
}
