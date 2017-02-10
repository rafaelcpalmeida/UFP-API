<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
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

        return (isset($sessionToken->shakeHandsResult) && $sessionToken->shakeHandsResult != "") ? $this->encodeMessage(0, Crypt::encrypt($sessionToken->shakeHandsResult)) : $this->encodeMessage(1, "Check your credentials");
    }

    public function getMB(Request $request) {
        $encryptedToken = $request->input("token");

        try {
            $token = Crypt::decrypt($encryptedToken);
        } catch (DecryptException $e) {
            return $this->encodeMessage(1, "Couldn't decrypt sent token");
        }

        $mbDetails = $this->getDataFromSOAPServer("atm", array('atm' => array('token' => $token)));

        return (isset(json_decode($mbDetails->atmResult)->atm[0])) ? $this->encodeMessage(0, json_decode($mbDetails->atmResult)->atm[0]) : $this->encodeMessage(1, "No payment information found");
    }

    private function getDataFromSOAPServer($function, $arguments) {
        $client = new SoapClient('https://portal.ufp.pt/hi5.asmx?WSDL');

        $result = $client->__soapCall($function, $arguments);
        
        return $result;
    }

    private function encodeMessage($status, $message) {
        return json_encode(["status" => ($status == 0) ? "Ok" : "Error", "message" => $message]);
    }
}
