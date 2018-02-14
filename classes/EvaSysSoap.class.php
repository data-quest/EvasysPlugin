<?php

/*
 *  Copyright (c) 2011  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */


class EvaSysSoap
{

    static protected $instance = null;

    static public function get()
    {
        if (!self::$instance) {

            $evasys_wsdl = get_config("EVASYS_WSDL");
            $evasys_user = get_config("EVASYS_USER");
            $evasys_password = get_config("EVASYS_PASSWORD");

            if (!$evasys_wsdl || !$evasys_user || !$evasys_password) {
                throw new Exception("EVASYS_* Konfiguration unvollständig!");
            }
            self::$instance = new EvaSysSoapClient($evasys_wsdl, array(
                'trace' => 1,
                'exceptions' => 0,
                'cache_wsdl' => $GLOBALS['CACHING_ENABLE'] || !isset($GLOBALS['CACHING_ENABLE'])
                    ? WSDL_CACHE_BOTH
                    : WSDL_CACHE_NONE,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS
            ));
            $file = strtolower(substr($evasys_wsdl, strrpos($evasys_wsdl, "/") + 1));
            $soapHeaders = new SoapHeader($file, 'Header', array(
                'Login' => $evasys_user,
                'Password' => $evasys_password
            ));
            self::$instance->__setSoapHeaders($soapHeaders);
            if (is_soap_fault(self::$instance)) {
                throw new Exception("SOAP-Error: " . self::$instance);
            }
        }
        return self::$instance;
    }
}