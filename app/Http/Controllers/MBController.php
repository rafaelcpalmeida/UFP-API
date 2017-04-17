<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class MBController extends Controller {
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

    public function getMB() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if(isset($tokenData->token)) {
            $mbDetails = $this->soap->getDataFromSOAPServer("atm", array("atm" => array("token" => $tokenData->token)));

            if(property_exists(json_decode($mbDetails->atmResult), "Error"))
                return $this->message->encodeMessage(1, "Invalid token");

            return (isset(json_decode($mbDetails->atmResult)->atm[0])) ? $this->message->encodeMessage(0, json_decode($mbDetails->atmResult)->atm[0]) : $this->message->encodeMessage(1, "No payment information found");
        }
        
        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }
}
