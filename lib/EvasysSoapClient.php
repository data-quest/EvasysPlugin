<?php

class EvasysSoapClient extends SoapClient
{

    public function soapCall ($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null, $socket_timeout = null) {
        $orginal_default_socket_timeout = ini_get("default_socket_timeout");
        ini_set(
            "default_socket_timeout",
            is_int($socket_timeout)
                ? $socket_timeout
                : Config::get()->EVASYS_SOAP_SOCKET_TIMEOUT
        );

        $starttime = microtime(true);
        $result = parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
        $soapcalltime = microtime(true) - $starttime;

        ini_set("default_socket_timeout", $orginal_default_socket_timeout);

        $maxsize = 10 * 1024 * 1024;
        if (strlen(json_encode((array) $result)) > $maxsize) {
            $result2 = [substr(json_encode($result), 0, $maxsize)];
        }

        $soaplog = new EvasysSoapLog();
        $soaplog['function'] = $function_name;
        $soaplog['arguments'] = (array) $arguments;
        $soaplog['result'] = $result2 ?? (array) $result;
        $soaplog['time'] = $soapcalltime;
        $soaplog['user_id'] = $GLOBALS['user']->id;
        $soaplog->store();

        return $result;
    }
}
