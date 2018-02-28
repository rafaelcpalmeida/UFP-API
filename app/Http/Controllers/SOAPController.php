<?php

namespace App\Http\Controllers;

use SoapClient;

class SOAPController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }
    
    public function getDataFromSOAPServer($function, $arguments = array())
    {
        $client = new SoapClient("https://portal.ufp.pt/hi5.asmx?WSDL");

        return $client->__soapCall($function, $arguments);
    }
}
