<?php

class AddCourseIdentifier extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_COURSE_IDENTIFIER", array(
            'value' => 'seminar_id',
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "What is the identifier of a course? seminar_id, number or any course datafield_id possible."
        ));

    }
    
    public function down()
    {
        Config::get()->delete("EVASYS_COURSE_IDENTIFIER");
    }
}
