<?php

class AddAllFormsAvailable extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_ALL_FORMS_AVAILABLE", array(
            'value' => "0",
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "If no other restrictions are made should be all forms be available (1) or no forms except the default-form (default: 0)?"
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_ALL_FORMS_AVAILABLE");
    }
}
