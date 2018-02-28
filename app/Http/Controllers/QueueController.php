<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CURLController;
use App\Http\Controllers\MessagesController;

class QueueController extends Controller
{
    private $curl;
    private $message;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CURLController $curl, MessagesController $message)
    {
        $this->curl = $curl;
        $this->message = $message;
    }

    public function getQueue()
    {
        $queue = $this->curl->getDataFromcURLRequest("http://senha.ufp.pt/Home/getFilaMin");

        if ($queue["status"] != 200) {
            return $this->message->encodeMessage(500, "An error has occurred! Please try again.");
        }

        $queueDetails = [];

        foreach (json_decode($queue["message"]) as $queueDetail) {
            $queueDetails[$queueDetail->SERVICE_CODE]["desc"] = $queueDetail->SERVICO;
            $queueDetails[$queueDetail->SERVICE_CODE]["last_update"] = $queueDetail->START_HOUR;
            $queueDetails[$queueDetail->SERVICE_CODE]["number"] = $queueDetail->TICK_NUMBER;
            $queueDetails[$queueDetail->SERVICE_CODE]["waiting"] = $queueDetail->WAIT;
        }

        return $this->message->encodeMessage(200, $queueDetails);
    }
}
