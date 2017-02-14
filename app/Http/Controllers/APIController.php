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

    public function getGrades(Request $request, $type) {
        $token = $this->decryptToken($request->input("token"));

        if($token) {
            $gradesAux = $this->getDataFromSOAPServer("grade", array("grade" => array("token" => $token)));

            switch ($type) {
                case "finals":
                    $finalGrades = $this->parseFinalGrades(json_decode($gradesAux->gradeResult)->grade->definitivo);
                    return (!empty($finalGrades)) ? $this->encodeMessage(0, $finalGrades) : $this->encodeMessage(1, "No final grades information found");
                    break;
                case "detailed":
                    $detailedGrades = $this->parseDetailedGrades(json_decode($gradesAux->gradeResult)->grade->provisorio->parciais);
                    return (!empty($detailedGrades)) ? $this->encodeMessage(0, $detailedGrades) : $this->encodeMessage(1, "No detailed grades information found");
                    break;
                default:
                    return $this->encodeMessage(1, "Option '$type' doesn't exist. Please refer to docs");
                    break;
            }
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

    private function encryptMessage($message) {
        return Crypt::encrypt($message);
    }

    private function parseFinalGrades($grades) {
        $gradesAux = array();

        foreach($grades as $grade) {
            $gradesAux[$grade->Grau][$grade->Curso][] = array("unidade" => $grade->Unidade, "nota" => $grade->Nota);
        }

        return $gradesAux;
    }

    private function parseDetailedGrades($grades) {
        $gradesAux = array();

        foreach($grades as $grade) {
            $gradesAux[$grade->Unidade][] = array("unidade" => $grade->Unidade, "elemento" => $grade->Elemento, "nota" => $grade->Nota);
        }

        return $gradesAux;
    }
}
