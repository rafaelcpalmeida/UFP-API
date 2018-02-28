<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class ExamController extends Controller {
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

    public function getExams() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if(isset($tokenData->token)) {
            $examAux = $this->soap->getDataFromSOAPServer("exame", array("exame" => array("token" => $tokenData->token)));

            if (json_decode($examAux->exameResult) == NULL)
                return $this->message->encodeMessage(1, "No exam information found");
            
            if(property_exists(json_decode($examAux->exameResult), "Error"))
                return $this->message->encodeMessage(1, "Invalid token");

            $exams = array();
            
            foreach(json_decode($examAux->exameResult)->Exames as $exam) {
                preg_match('/(?:(\d){4}-(\d){2}-(\d){2})/', $exam->Data, $date);
                preg_match('/(?:(\d){2}:(\d){2})/', $exam->Data, $hour);
                $exams[$date[0]][] = array("subject" => $exam->Disciplina, "time" => $hour[0], "course" => $exam->Curso, "typology" => $exam->Tipologia, "room" => explode("<br>", $exam->Sala), "assignee" => explode("<br>", $exam->Responsavel));
            }

            // In order to maintain the order when the endpoint is called we must create an array of objects
            $json = [];
            foreach($exams as $key => $value) {
                $json[] = [$key => $value];
            }

            $exams = $json;

            return (!empty($exams)) ? $this->message->encodeMessage(0, $exams) : $this->message->encodeMessage(1, "No exam information found");
        }

        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }
}
