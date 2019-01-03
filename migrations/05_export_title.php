<?php
class ExportTitle extends Migration
{
	function up()
    {
        Config::get()->create("EVASYS_EXPORT_TITLES", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Should the title of teachers be exported to EvaSys or not."
        ));
	}

    public function down()
    {
        Config::get()->delete("EVASYS_EXPORT_TITLES");
    }
}