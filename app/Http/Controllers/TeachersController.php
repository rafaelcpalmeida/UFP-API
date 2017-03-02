<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Multibanco;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class TeachersController extends Controller {
    private $message;
    private $mb;
    private $apiToken;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, SOAPController $soap, MessagesController $message, Multibanco $mb) {
        $this->apiToken = $request->input("token");
        $this->soap = $soap;
        $this->message = $message;
        $this->mb = $mb;
    }

    public function getMB() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserMBDetails($tokenData->number)) {
                $mbDetails = $this->soap->getDataFromSOAPServer("atm", array("atm" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($mbDetails->atmResult), "Error"))
                    return $this->message->encodeMessage(1, "Invalid token");
                
                if((isset(json_decode($mbDetails->atmResult)->atm[0]))) {
                    $userMB = new $this->mb;

                    $userMB->number = $tokenData->number;
                    $userMB->mbDetails = json_decode($mbDetails->atmResult)->atm[0];

                    $userMB->save();

                    return $this->message->encodeMessage(0, $userMB->mbDetails);
                }

                return $this->message->encodeMessage(1, "No payment information found");
            }

            return $this->message->encodeMessage(0, $this->mb->where("number", "=", $tokenData->number)->first()->mbDetails);
        }
        
        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }

    private function hasUserMBDetails($userNumber) {
        return $this->mb->where("number", "=", $userNumber)->exists();
    }
}
