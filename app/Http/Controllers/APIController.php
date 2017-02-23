<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use App\User;
use App\Multibanco;
use SoapClient;

class APIController extends Controller {
    private $crypt;
    private $apiToken;
    private $username;
    private $password;
    private $user;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Encrypter $crypt, Request $request, User $user, Multibanco $mb) {
        $this->crypt = $crypt;
        $this->apiToken = $request->input("token");
        $this->username = $request->input("username");
        $this->password = $request->input("password");
        $this->user = $user;
        $this->mb = $mb;
    }

    public function index() {
        return json_encode(["Version" => "1.0"]);
    }

    public function login() {
        $authToken = $this->getDataFromSOAPServer("Encrypt", array("Encrypt" => array("phrase" => "$this->username,$this->password")));

        $sessionToken = $this->getDataFromSOAPServer("shakeHands", array("shakeHands" => array("input" => $authToken->EncryptResult)));

        if (isset($sessionToken->shakeHandsResult) && $sessionToken->shakeHandsResult != "") {
            if (!$this->hasUserDetails("number", $this->username)) {
                $newUser = new $this->user;

                $newUser->number = $this->username;
                $newUser->password = $this->crypt->encrypt($sessionToken->shakeHandsResult);

                $newUser->save();
            } else {
                $existingUser = $this->user->where("number", "=", $this->username)->first();

                $existingUser->password = $this->crypt->encrypt($sessionToken->shakeHandsResult);

                $existingUser->save();
            }

            return $this->encodeMessage(0, $this->crypt->encrypt(["number" => $this->username, "token" => $sessionToken->shakeHandsResult]));
        }

        return $this->encodeMessage(1, "Check your credentials");
    }

    public function checkToken() {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        return json_encode(["Valid" => $this->isValidToken($tokenData->token)]);
    }

    public function getMB() {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserMBDetails($tokenData->number)) {
                $mbDetails = $this->getDataFromSOAPServer("atm", array("atm" => array("token" => $tokenData->token)));
                
                if((isset(json_decode($mbDetails->atmResult)->atm[0]))) {
                    $userMB = new $this->mb;

                    $userMB->number = $tokenData->number;
                    $userMB->mbDetails = json_decode($mbDetails->atmResult)->atm[0];

                    $userMB->save();

                    return $this->encodeMessage(0, $userMB->mbDetails);
                }

                return $this->encodeMessage(1, "No payment information found");
            }

            return $this->encodeMessage(0, $this->mb->where("number", "=", $tokenData->number)->first()->mbDetails);
        }
        
        return $this->encodeMessage(1, "Couldn't decrypt sent token");
    }

    public function getAssiduity() {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        if($tokenData->token) {
            $assiduityAux = $this->getDataFromSOAPServer("assiduity", array("assiduity" => array("token" => $tokenData->token)));
            $assiduity = array();

            foreach(json_decode($assiduityAux->assiduityResult)->assiduity as $detail) {
                array_push($assiduity, array("unidade" => $detail->Unidade, "tipo" => $detail->Tipo, "assiduidade" => $detail->Assiduidade));
            }

            return (!empty($assiduity)) ? $this->encodeMessage(0, $assiduity) : $this->encodeMessage(1, "No payment information found");
        }

        return $this->encodeMessage(1, "Couldn't decrypt sent token");
    }

    public function getGrades($type) {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        if($tokenData->token) {
            $gradesAux = $this->getDataFromSOAPServer("grade", array("grade" => array("token" => $tokenData->token)));
            
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
        }

        return $this->encodeMessage(1, "Couldn't decrypt sent token");
    }

    public function getSchedule() {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        if($tokenData->token) {
            $scheduleAux = $this->getDataFromSOAPServer("schedule", array("schedule" => array("token" => $tokenData->token)));

            $parsedSchedule = $this->parseSchedule(json_decode($scheduleAux->scheduleResult)->schedule);
            
            return (!empty($parsedSchedule)) ? $this->encodeMessage(0, $parsedSchedule) : $this->encodeMessage(1, "No schedule information found");
        }
        
        return $this->encodeMessage(1, "Not a valid token");
    }

    private function decryptToken($encryptedToken) {
        try {
            return $this->crypt->decrypt($encryptedToken);
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
        return $this->crypt->encrypt($message);
    }

    private function isValidToken($token) {
        $mbDetails = $this->getDataFromSOAPServer("atm", array("atm" => array("token" => $token)));

        if(!property_exists(json_decode($mbDetails->atmResult), "Error"))
            return true;
        
        return false;
    }

    private function hasUserDetails($detail, $userNumber) {
        return $this->user->where($detail, "=", $userNumber)->exists();
    }

    private function hasUserMBDetails($userNumber) {
        return $this->mb->where("number", "=", $userNumber)->exists();
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
            $gradesAux[$grade->Unidade][$grade->AnoLectivo][] = array("unidade" => $grade->Unidade, "elemento" => $grade->Elemento, "nota" => $grade->Nota);
        }

        return $gradesAux;
    }

    private function parseSchedule($schedule) {
        $scheduleAux = array();

        foreach($schedule as $days) {
            $scheduleAux[$days->Data][] = array("inicio" => $days->Inicio, "termo" => $days->Termo, "sala" => $days->Sala, "unidade" => $days->Unidade, "tipo" => $days->Tipo);
        }

        return $scheduleAux;
    }
}
