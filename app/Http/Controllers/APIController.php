<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;

class APIController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
    }

    public function index() {
        return json_encode(["Version" => "1.0"]);
    }

    public function login(Request $request) {
        $authToken = $this->getDataFromSOAPServer("Encrypt", array('Encrypt' => array('phrase' => $request->input("username").",".$request->input("password"))));

        $sessionToken = $this->getDataFromSOAPServer("shakeHands", array('shakeHands' => array('input' => $authToken->EncryptResult)));

        return (isset($sessionToken->shakeHandsResult) && $sessionToken->shakeHandsResult != "") ? json_encode(["status" => "Ok", "message" => $sessionToken->shakeHandsResult]) : json_encode(["status" => "Error", "message" => "Check your credentials"]);
    }

    private function getDataFromSOAPServer($function, $arguments) {
        $client = new SoapClient('https://portal.ufp.pt/hi5.asmx?WSDL');

        $result = $client->__soapCall($function, $arguments);
        
        return $result;
    }
}
