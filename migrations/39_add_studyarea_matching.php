<?php

class AddStudyareaMatching extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_STUDYAREA_MATCHING", array(
            'value' => 'path',
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "How should studyareas be transferred to evasys? a) as 'path' with the whole path of names, b) as 'name' with the name of the last element of the tree or c) as 'info' of the last element of the tree?"
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_STUDYAREA_MATCHING");
    }
}
