<?php

class MatchInnerPeriod extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_MATCH_INNER_PERIOD", array(
            'value' => "0",
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Should INSERTCOURSES use INNER_ACTIVE_PERIOD_ON_DATE instead of PERIODDATE. Works from v91 patch may 2025."
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_MATCH_INNER_PERIOD");
    }
}
