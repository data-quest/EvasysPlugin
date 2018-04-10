<?php

class EvasysSoapClient extends SoapClient {

    public function __soapCall ($function_name,  $arguments,  $options = null, $input_headers = null,  &$output_headers = null) {
        $result = parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
        if (class_exists("Log")) {
            Log::set("evasys", $GLOBALS['TMP_PATH'] . '/studipevasys.log');
            $log = Log::set("evasys");
            $log->setLogLevel(Log::DEBUG);
            $log->log("EvaSys-SOAP-Call ".$function_name.": ".json_encode($arguments), Log::DEBUG);
        }
        return $result;
    }
}