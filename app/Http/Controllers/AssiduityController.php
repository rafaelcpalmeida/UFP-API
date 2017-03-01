<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Assiduity;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class AssiduityController extends Controller {
    private $soap;
    private $message;
    private $user;    
    private $assiduity;
    private $apiToken;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, MessagesController $message, User $user, Assiduity $assiduity) {
        $this->apiToken = $request->input("token");
        $this->user = $user;
        $this->message = $message;
        $this->assiduity = $assiduity;
    }

    public function getAssiduity() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserAssiduityDetails($tokenData->number)) {
                $assiduityAux = $this->getDataFromSOAPServer("assiduity", array("assiduity" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($assiduityAux->assiduityResult), "Error"))
                    return $this->message->encodeMessage(1, "Invalid token");

                $assiduity = array();

                foreach(json_decode($assiduityAux->assiduityResult)->assiduity as $detail) {
                    array_push($assiduity, array("unidade" => $detail->Unidade, "tipo" => $detail->Tipo, "assiduidade" => $detail->Assiduidade));
                }

                if (!empty($assiduity)) {
                    $userAssiduity = new $this->assiduity;

                    $userAssiduity->number = $tokenData->number;
                    $userAssiduity->assiduity = $assiduity;

                    $userAssiduity->save();

                    return $this->message->encodeMessage(0, $userAssiduity->assiduity);
                }

                return $this->message->encodeMessage(1, "No assiduity information found");
            }

            return $this->message->encodeMessage(0, $this->assiduity->where("number", "=", $tokenData->number)->first()->assiduity);
        }

        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }

    private function hasUserAssiduityDetails($userNumber) {
        return $this->assiduity->where("number", "=", $userNumber)->exists();
    }
}
