<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class ScheduleController extends Controller {
    private $soap;
    private $message;
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

    public function getSchedule() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            $scheduleAux = $this->soap->getDataFromSOAPServer("schedule", array("schedule" => array("token" => $tokenData->token)));

            if(property_exists(json_decode($scheduleAux->scheduleResult), "Error"))
                    return $this->message->encodeMessage(1, "Invalid token");

            return (!empty($this->parseSchedule(json_decode($scheduleAux->scheduleResult)->schedule))) ? $this->message->encodeMessage(0, $this->parseSchedule(json_decode($scheduleAux->scheduleResult)->schedule)) : $this->message->encodeMessage(1, "No schedule information found");
        }
        
        return $this->message->encodeMessage(1, "Not a valid token");
    }

    private function parseSchedule($schedule) {
        $scheduleAux = array();

        foreach($schedule as $days) {
            $scheduleAux[$days->Data][] = array("inicio" => $days->Inicio, "termo" => $days->Termo, "sala" => $days->Sala, "unidade" => $days->Unidade, "tipo" => $days->Tipo);
        }

        return $scheduleAux;
    }
}
