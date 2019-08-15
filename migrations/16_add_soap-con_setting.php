<?php

class AddSOAPConTimeoutConfigOption extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_SOAP_CON_TIMEOUT", array(
            'value' => 1,
            'type' => "integer",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Timeout (in seconds) for initial SOAP connection establishment. See: https://www.php.net/manual/en/soapclient.soapclient.php"
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_SOAP_CON_TIMEOUT");
    }
}
