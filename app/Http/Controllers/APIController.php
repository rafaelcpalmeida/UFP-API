<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use App\User;
use App\Multibanco;
use App\Assiduity;
use App\FinalGrades;
use App\DetailedGrades;
use App\Schedule;
use SoapClient;

class APIController extends Controller {
    private $crypt;
    private $apiToken;
    private $username;
    private $password;
    private $user;
    private $mb;
    private $assiduity;
    private $finalGrades;
    private $detailedGrades;
    private $schedule;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Encrypter $crypt, Request $request, User $user, Multibanco $mb, Assiduity $assiduity, FinalGrades $finalGrades, DetailedGrades $detailedGrades, Schedule $schedule) {
        $this->crypt = $crypt;
        $this->apiToken = $request->input("token");
        $this->username = $request->input("username");
        $this->password = $request->input("password");
        $this->user = $user;
        $this->mb = $mb;
        $this->assiduity = $assiduity;
        $this->finalGrades = $finalGrades;
        $this->detailedGrades = $detailedGrades;
        $this->schedule = $schedule;
    }

    public function index() {
        return json_encode(["Version" => "1.0"]);
    }

    public function getMB() {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserMBDetails($tokenData->number)) {
                $mbDetails = $this->getDataFromSOAPServer("atm", array("atm" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($mbDetails->atmResult), "Error"))
                    return $this->encodeMessage(1, "Invalid token");
                
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
            if(!$this->hasUserAssiduityDetails($tokenData->number)) {
                $assiduityAux = $this->getDataFromSOAPServer("assiduity", array("assiduity" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($assiduityAux->assiduityResult), "Error"))
                    return $this->encodeMessage(1, "Invalid token");

                $assiduity = array();

                foreach(json_decode($assiduityAux->assiduityResult)->assiduity as $detail) {
                    array_push($assiduity, array("unidade" => $detail->Unidade, "tipo" => $detail->Tipo, "assiduidade" => $detail->Assiduidade));
                }

                if (!empty($assiduity)) {
                    $userAssiduity = new $this->assiduity;

                    $userAssiduity->number = $tokenData->number;
                    $userAssiduity->assiduity = $assiduity;

                    $userAssiduity->save();

                    return $this->encodeMessage(0, $userAssiduity->assiduity);
                }

                return $this->encodeMessage(1, "No assiduity information found");
            }

            return $this->encodeMessage(0, $this->assiduity->where("number", "=", $tokenData->number)->first()->assiduity);
        }

        return $this->encodeMessage(1, "Couldn't decrypt sent token");
    }

    public function getGrades($type) {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        if($tokenData->token) {
            switch ($type) {
                case "finals":
                    if(!$this->hasUserFinalGradesDetails($tokenData->number)) {
                        $gradesAux = $this->getDataFromSOAPServer("grade", array("grade" => array("token" => $tokenData->token)));

                        if(property_exists(json_decode($gradesAux->gradeResult), "Error"))
                            return $this->encodeMessage(1, "Invalid token");

                        $finalGrades = $this->parseFinalGrades(json_decode($gradesAux->gradeResult)->grade->definitivo);

                        if (!empty($finalGrades)) {
                            $userFinalGrades = new $this->finalGrades;

                            $userFinalGrades->number = $tokenData->number;
                            $userFinalGrades->grades = $finalGrades;

                            $userFinalGrades->save();

                            return $this->encodeMessage(0, $userFinalGrades->grades);
                        }

                        return $this->encodeMessage(1, "No final grades information found");
                    }

                    return $this->encodeMessage(0, $this->finalGrades->where("number", "=", $tokenData->number)->first()->grades);
                    break;
                case "detailed":
                    if(!$this->hasUserDetailedGradesDetails($tokenData->number)) {
                        $gradesAux = $this->getDataFromSOAPServer("grade", array("grade" => array("token" => $tokenData->token)));

                        if(property_exists(json_decode($gradesAux->gradeResult), "Error"))
                            return $this->encodeMessage(1, "Invalid token");

                        $detailedGrades = $this->parseDetailedGrades(json_decode($gradesAux->gradeResult)->grade->provisorio->parciais);

                        if (!empty($detailedGrades)) {
                            $userDetailedGrades = new $this->detailedGrades;

                            $userDetailedGrades->number = $tokenData->number;
                            $userDetailedGrades->grades = $detailedGrades;

                            $userDetailedGrades->save();

                            return $this->encodeMessage(0, $userDetailedGrades->grades);
                        }

                        return $this->encodeMessage(1, "No detailed grades information found");
                    }

                    return $this->encodeMessage(0, $this->detailedGrades->where("number", "=", $tokenData->number)->first()->grades);
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
            if(!$this->hasUserScheduleDetails($tokenData->number)) {
                $scheduleAux = $this->getDataFromSOAPServer("schedule", array("schedule" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($scheduleAux->scheduleResult), "Error"))
                    return $this->encodeMessage(1, "Invalid token");

                $parsedSchedule = $this->parseSchedule(json_decode($scheduleAux->scheduleResult)->schedule);

                if (!empty($parsedSchedule)) {
                    $userParsedSchedule = new $this->schedule;

                    $userParsedSchedule->number = $tokenData->number;
                    $userParsedSchedule->schedule = $parsedSchedule;

                    $userParsedSchedule->save();

                    return $this->encodeMessage(0, $userParsedSchedule->schedule);
                }

                return $this->encodeMessage(1, "No schedule information found");   
            }
            
            return $this->encodeMessage(0, $this->schedule->where("number", "=", $tokenData->number)->first()->schedule);
        }
        
        return $this->encodeMessage(1, "Not a valid token");
    }

    private function hasUserMBDetails($userNumber) {
        return $this->mb->where("number", "=", $userNumber)->exists();
    }

    private function hasUserAssiduityDetails($userNumber) {
        return $this->assiduity->where("number", "=", $userNumber)->exists();
    }

    private function hasUserFinalGradesDetails($userNumber) {
        return $this->finalGrades->where("number", "=", $userNumber)->exists();
    }

    private function hasUserDetailedGradesDetails($userNumber) {
        return $this->detailedGrades->where("number", "=", $userNumber)->exists();
    }

    private function hasUserScheduleDetails($userNumber) {
        return $this->schedule->where("number", "=", $userNumber)->exists();
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
