<?php

class EvasysSoapClient extends SoapClient {

    public function __soapCall ($function_name,  $arguments,  $options = null, $input_headers = null,  &$output_headers = null) {
        $starttime = microtime(true);
        $result = parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
        $soapcalltime = microtime(true) - $starttime;
        if (class_exists("Log")) {
            Log::set("evasys", Config::get()->EVASYS_LOGPATH . '/studipevasys.log');
            $log = Log::set("evasys");
            $log->setLogLevel(Log::DEBUG);
            $log->log("EvaSys-SOAP-Call ".$function_name.": ".json_encode($arguments). " request-time: ".$soapcalltime, Log::DEBUG);
        }
        return $result;
    }
}
