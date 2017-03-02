<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FinalGrades;
use App\DetailedGrades;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class GradesController extends Controller {   
    private $soap;
    private $message;
    private $finalGrades;
    private $detailedGrades;
    private $apiToken;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, SOAPController $soap, MessagesController $message, FinalGrades $finalGrades, DetailedGrades $detailedGrades) {
        $this->apiToken = $request->input("token");
        $this->soap = $soap;
        $this->message = $message;
        $this->finalGrades = $finalGrades;
        $this->detailedGrades = $detailedGrades;
    }

    public function getFinalGrades() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserFinalGradesDetails($tokenData->number)) {
                $gradesAux = $this->soap->getDataFromSOAPServer("grade", array("grade" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($gradesAux->gradeResult), "Error"))
                    return $this->message->encodeMessage(1, "Invalid token");

                $finalGrades = $this->parseFinalGrades(json_decode($gradesAux->gradeResult)->grade->definitivo);

                if (!empty($finalGrades)) {
                    $userFinalGrades = new $this->finalGrades;

                    $userFinalGrades->number = $tokenData->number;
                    $userFinalGrades->grades = $finalGrades;

                    $userFinalGrades->save();

                    return $this->message->encodeMessage(0, $userFinalGrades->grades);
                }

                return $this->message->encodeMessage(1, "No final grades information found");
            }

            return $this->message->encodeMessage(0, $this->finalGrades->where("number", "=", $tokenData->number)->first()->grades);
        }

        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }

    public function getDetailedGrades() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserDetailedGradesDetails($tokenData->number)) {
                $gradesAux = $this->soap->getDataFromSOAPServer("grade", array("grade" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($gradesAux->gradeResult), "Error"))
                    return $this->message->encodeMessage(1, "Invalid token");

                $detailedGrades = $this->parseDetailedGrades(json_decode($gradesAux->gradeResult)->grade->provisorio->parciais);

                if (!empty($detailedGrades)) {
                    $userDetailedGrades = new $this->detailedGrades;

                    $userDetailedGrades->number = $tokenData->number;
                    $userDetailedGrades->grades = $detailedGrades;

                    $userDetailedGrades->save();

                    return $this->message->encodeMessage(0, $userDetailedGrades->grades);
                }

                return $this->message->encodeMessage(1, "No detailed grades information found");
            }

            return $this->message->encodeMessage(0, $this->detailedGrades->where("number", "=", $tokenData->number)->first()->grades);
        }

        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }

    private function hasUserFinalGradesDetails($userNumber) {
        return $this->finalGrades->where("number", "=", $userNumber)->exists();
    }

    private function hasUserDetailedGradesDetails($userNumber) {
        return $this->detailedGrades->where("number", "=", $userNumber)->exists();
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
}
