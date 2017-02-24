<?php

/*
 *  Copyright (c) 2011  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */


class EvaSysSoapClient extends SoapClient {

    public function __soapCall ($function_name,  $arguments,  $options = null, $input_headers = null,  &$output_headers = null) {
        $result = parent::__soapCall($function_name, studip_utf8encode($arguments), $options, $input_headers, $output_headers);
        if (class_exists("Log")) {
            Log::set("evasys", $GLOBALS['TMP_PATH'] . '/studipevasys.log');
            $log = Log::set("evasys");
            $log->setLogLevel(Log::DEBUG);
            $log->log("EvaSys-SOAP-Call ".$function_name.": ".json_encode(studip_utf8encode($arguments)), Log::DEBUG);
        }
        return $result;
    }
}