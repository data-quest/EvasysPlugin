<?php

class LeaveOutMentioningOption extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_LEAVE_OUT_MENTIONING", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "This option hides the message behind the professores that are selected first to be shown on the evaluations."
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_LEAVE_OUT_MENTIONING");
    }
}
