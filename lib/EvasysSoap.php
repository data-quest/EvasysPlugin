<?php

class EvasysSoap
{

    static protected $instance = null;

    static public function get()
    {
        if (!self::$instance) {

            $evasys_wsdl = Config::get()->EVASYS_WSDL;
            $evasys_user = Config::get()->EVASYS_USER;
            $evasys_password = Config::get()->EVASYS_PASSWORD;

            if (!$evasys_wsdl || !$evasys_user || !$evasys_password) {
                throw new Exception("EVASYS_* Konfiguration unvollständig!");
            }
            self::$instance = new EvasysSoapClient($evasys_wsdl, [
                'connection_timeout' => Config::get()->EVASYS_SOAP_CON_TIMEOUT, //Zeit für den Verbindungsaufbau
                'trace' => 0, //anschalten nur im Entwicklungsbetrieb
                'exceptions' => 0,
                'cache_wsdl' => ($GLOBALS['CACHING_ENABLE'] || !isset($GLOBALS['CACHING_ENABLE']))
                    ? WSDL_CACHE_DISK
                    : WSDL_CACHE_NONE,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS/*,
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]*/
            ]);
            $file = strtolower(substr($evasys_wsdl, strrpos($evasys_wsdl, "/") + 1));
            $soapHeaders = new SoapHeader($file, 'Header', [
                'Login' => $evasys_user,
                'Password' => $evasys_password
            ]);
            self::$instance->__setSoapHeaders($soapHeaders);
            if (is_soap_fault(self::$instance)) {
                throw new Exception("SOAP-Error: " . self::$instance);
            }
        }
        return self::$instance;
    }
}
