<?php

/**
 *
 * Configuration option to set default_socket_timeout especially for EvaSys soap calls.
 *
 * SoapClient doesn't provide such an option (https://www.php.net/manual/de/soapclient.construct.php).
 * 
 * @author Jan Eberhardt
 * @see https://www.php.net/manual/de/soapclient.construct.php
 */
class AddSocketTimeoutConfiguration extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_SOAP_SOCKET_TIMEOUT", array(
            'value' => 60,
            'type' => "integer",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "A seperate socket-timeout (in seconds) for EvaSys SOAP calls. See: https://www.php.net/manual/de/filesystem.configuration.php#ini.default-socket-timeout"
        ));
    }

    public function down()
    {
        Config::get()->delete("EVASYS_SOAP_SOCKET_TIMEOUT");
    }
}
