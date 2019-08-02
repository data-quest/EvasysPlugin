<?php
class AddExportDozentByEmail extends Migration
{
	function up(){
        Config::get()->create("EVASYS_EXPORT_DOZENT_BY_FIELD", array(
            'value' => "user_id",
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Which field should be used to export the dozent (user_id, email, datafield_id)."
        ));
	}

    function down() {
        Config::get()->delete("EVASYS_EXPORT_DOZENT_BY_FIELD");
    }
}
