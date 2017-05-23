<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class AssiduityController extends Controller {
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

    public function getAssiduity() {
        $tokenData = (object) $this->message->decryptToken($this->apiToken);

        if(isset($tokenData->token)) {
            $assiduityAux = $this->soap->getDataFromSOAPServer("assiduity", array("assiduity" => array("token" => $tokenData->token)));
            
            if(property_exists(json_decode($assiduityAux->assiduityResult), "Error"))
                return $this->message->encodeMessage(1, "Invalid token");

            $assiduity = array();
            
            foreach(json_decode($assiduityAux->assiduityResult)->assiduity as $detail) {
                $assiduity[$detail->Unidade][] = array("tipo" => $detail->Tipo, "assiduidade" => $detail->Assiduidade);
            }

            // In order to maintain the order when the endpoint is called we must create an array of objects
            $json = [];
            foreach($assiduity as $key => $value) {
                $json[] = [$key => $value];
            }

            $assiduity = $json;

            return (!empty($assiduity)) ? $this->message->encodeMessage(0, $assiduity) : $this->message->encodeMessage(1, "No assiduity information found");
        }

        return $this->message->encodeMessage(1, "Couldn't decrypt sent token");
    }
}
