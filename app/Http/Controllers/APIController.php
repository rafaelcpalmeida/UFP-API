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
        $authToken = $this->getDataFromSOAPServer("Encrypt", array("Encrypt" => array("phrase" => $request->input("username").",".$request->input("password"))));

        $sessionToken = $this->getDataFromSOAPServer("shakeHands", array("shakeHands" => array("input" => $authToken->EncryptResult)));

        return (isset($sessionToken->shakeHandsResult) && $sessionToken->shakeHandsResult != "") ? $this->encodeMessage(0, Crypt::encrypt($sessionToken->shakeHandsResult)) : $this->encodeMessage(1, "Check your credentials");
    }

    public function getMB(Request $request) {
        $token = $this->decryptToken($request->input("token"));

        if($token) {
            $mbDetails = $this->getDataFromSOAPServer("atm", array("atm" => array("token" => $token)));

            return (isset(json_decode($mbDetails->atmResult)->atm[0])) ? $this->encodeMessage(0, json_decode($mbDetails->atmResult)->atm[0]) : $this->encodeMessage(1, "No payment information found");
        } else {
            return $this->encodeMessage(1, "Couldn't decrypt sent token");
        }
    }

    public function getAssiduity(Request $request) {
        $token = $this->decryptToken($request->input("token"));

        if($token) {
            $assiduityAux = $this->getDataFromSOAPServer("assiduity", array("assiduity" => array("token" => $token)));
            $assiduity = array();

            foreach(json_decode($assiduityAux->assiduityResult)->assiduity as $detail) {
                array_push($assiduity, array("unidade" => $detail->Unidade, "tipo" => $detail->Tipo, "assiduidade" => $detail->Assiduidade));
            }

            return (!empty($assiduity)) ? $this->encodeMessage(0, $assiduity) : $this->encodeMessage(1, "No payment information found");
        } else {
            return $this->encodeMessage(1, "Couldn't decrypt sent token");
        }
    }

    private function decryptToken($encryptedToken) {
        try {
            return Crypt::decrypt($encryptedToken);
        } catch (DecryptException $e) {
            return false;
        }
    }

    private function getDataFromSOAPServer($function, $arguments) {
        $client = new SoapClient("https://portal.ufp.pt/hi5.asmx?WSDL");

        $result = $client->__soapCall($function, $arguments);
        
        return $result;
    }

    private function encodeMessage($status, $message) {
        return json_encode(["status" => ($status == 0) ? "Ok" : "Error", "message" => $message]);
    }
}
