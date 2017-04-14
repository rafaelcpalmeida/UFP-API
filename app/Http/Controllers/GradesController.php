<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class GradesController extends Controller {   
    private $soap;
    private $message;
    private $detailedGrades;
    private $apiToken;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, SOAPController $soap, MessagesController $message) {
        $this->apiToken = $request->input("token");
        $this->soap = $soap;
        $this->message = $message;
    }

    public function getFinalGrades() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            $gradesAux = $this->soap->getDataFromSOAPServer("grade", array("grade" => array("token" => $tokenData->token)));
            
            if(property_exists(json_decode($gradesAux->gradeResult), "Error"))
                return $this->message->encodeMessage(1, "Invalid token");
            
            $finalGrades = $this->parseFinalGrades(json_decode($gradesAux->gradeResult)->grade->definitivo);

            return (!empty($finalGrades)) ? $this->message->encodeMessage(0, $finalGrades) : $this->message->encodeMessage(1, "No final grades information found");
        }

        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }

    public function getDetailedGrades() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            $gradesAux = $this->soap->getDataFromSOAPServer("grade", array("grade" => array("token" => $tokenData->token)));

            if(property_exists(json_decode($gradesAux->gradeResult), "Error"))
                return $this->message->encodeMessage(1, "Invalid token");

            $detailedGrades = $this->parseDetailedGrades(json_decode($gradesAux->gradeResult)->grade->provisorio->parciais);
            
            return (!empty($detailedGrades)) ? $this->message->encodeMessage(0, $detailedGrades) : $this->message->encodeMessage(1, "No detailed grades information found");
        }

        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
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
            $gradesAux[$grade->AnoLectivo][$grade->Unidade][] = array("unidade" => $grade->Unidade, "elemento" => $grade->Elemento, "nota" => $grade->Nota);
        }

        return $gradesAux;
    }
}
