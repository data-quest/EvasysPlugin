<?php

class AddSelectFirstTeacherOption extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_SELECT_FIRST_TEACHER", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "When a course is applied for evaluation is only the first teacher in list selected (1) or all (0)?"
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_SELECT_FIRST_TEACHER");
    }
}
