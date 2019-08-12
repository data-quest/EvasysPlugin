<?php
class InitSettings extends Migration
{
	function up(){
        Config::get()->create("EVASYS_WSDL", array(
            'value' => "",
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Path to the wsdl-file on the evasys-server."
        ));
        Config::get()->create("EVASYS_URI", array(
            'value' => "",
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Path to evasys-server like http://yourdomain/evasys/ reachable by studip."
        ));
        Config::get()->create("EVASYS_USER", array(
            'value' => "",
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "User to authenticate at the evasys-server."
        ));
        Config::get()->create("EVASYS_PASSWORD", array(
            'value' => "",
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Password to authenticate at the evasys-server."
        ));
        Config::get()->create("EVASYS_CACHE", array(
            'value' => 1,
            'type' => "integer",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Minutes to cache all results coming from evasys. 15 might be a good value."
        ));
        Config::get()->create("EVASYS_PUBLISH_RESULTS", array(
            'value' => 1,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Should students see the results of an evaluation?"
        ));
	}

    function down() {
        Config::get()->delete("EVASYS_WSDL");
        Config::get()->delete("EVASYS_URI");
        Config::get()->delete("EVASYS_USER");
        Config::get()->delete("EVASYS_PASSWORD");
        Config::get()->delete("EVASYS_CACHE");
        Config::get()->delete("EVASYS_PUBLISH_RESULTS");
    }
}
