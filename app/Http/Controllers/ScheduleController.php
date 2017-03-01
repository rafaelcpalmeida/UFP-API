<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Schedule;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class ScheduleController extends Controller {
    private $soap;
    private $message;
    private $user;    
    private $schedule;
    private $apiToken;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, SOAPController $soap, MessagesController $message, User $user, Schedule $schedule) {
        $this->apiToken = $request->input("token");
        $this->soap = $soap;
        $this->user = $user;
        $this->message = $message;
        $this->schedule = $schedule;
    }

    public function getSchedule() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserScheduleDetails($tokenData->number)) {
                $scheduleAux = $this->soap->getDataFromSOAPServer("schedule", array("schedule" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($scheduleAux->scheduleResult), "Error"))
                    return $this->message->encodeMessage(1, "Invalid token");

                $parsedSchedule = $this->parseSchedule(json_decode($scheduleAux->scheduleResult)->schedule);

                if (!empty($parsedSchedule)) {
                    $userParsedSchedule = new $this->schedule;

                    $userParsedSchedule->number = $tokenData->number;
                    $userParsedSchedule->schedule = $parsedSchedule;

                    $userParsedSchedule->save();

                    return $this->message->encodeMessage(0, $userParsedSchedule->schedule);
                }

                return $this->message->encodeMessage(1, "No schedule information found");   
            }
            
            return $this->message->encodeMessage(0, $this->schedule->where("number", "=", $tokenData->number)->first()->schedule);
        }
        
        return $this->message->encodeMessage(1, "Not a valid token");
    }

    private function hasUserScheduleDetails($userNumber) {
        return $this->schedule->where("number", "=", $userNumber)->exists();
    }

    private function parseSchedule($schedule) {
        $scheduleAux = array();

        foreach($schedule as $days) {
            $scheduleAux[$days->Data][] = array("inicio" => $days->Inicio, "termo" => $days->Termo, "sala" => $days->Sala, "unidade" => $days->Unidade, "tipo" => $days->Tipo);
        }

        return $scheduleAux;
    }
}
