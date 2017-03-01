<?php

namespace App\Http\Controllers;

use SoapClient;

class SOAPController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}
    
    public function getDataFromSOAPServer($function, $arguments) {
        $client = new SoapClient("https://portal.ufp.pt/hi5.asmx?WSDL");

        $result = $client->__soapCall($function, $arguments);
        
        return $result;
    }
}
