<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class MenuController extends Controller {
    private $soap;
    private $message;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SOAPController $soap, MessagesController $message) {
        $this->soap = $soap;
        $this->message = $message;
    }

    public function getMenu($language) {
        $parsedMenu = [];

        if ($language !== "pt" && $language != "en")
            return $this->message->encodeMessage(1, "Invalid option");

        $menuAux = $this->soap->getDataFromSOAPServer("menu");

        // We need to wrap the YYYY-MM-DD with "" and we need to remove all new line chars otherwise the JSON will be invalid
        $menuJSON = str_replace(array("\n","\r"), '', preg_replace('/((\d){4}-(\d){2}-(\d){2})/', '"$1"', $menuAux->menuResult));

        foreach (json_decode($menuJSON)->UnidadeEmenta as $menuJSONAux) {
            if ($language == "pt") {
                $parsedMenu[$menuJSONAux->Unidade] = $this->parseMenu($menuJSONAux->Ementa);
            } else {
                $parsedMenu[$menuJSONAux->Unidade] = $this->parseMenu($menuJSONAux->Menu);
            }
        }

        return (!empty($parsedMenu)) ? $this->message->encodeMessage(0, $parsedMenu) : $this->message->encodeMessage(1, "No menu information found");
    }

    private function parseMenu($data) {
        $days = [];

        preg_match_all("/(?:[A-Z]){3}:/", $data, $days);

        $days = $days[0];

        $num = count($days);

        for($i = 0; $i < $num; $i++) {
            $end = ($i == ($num-1)) ? "$" : $days[$i+1];

            $pattern = "/(?<=$days[$i])(?:.*?)(?=$end)/";
            
            preg_match($pattern, $data, $days[str_replace(":", "", $days[$i])]);
            unset($days[$i]);
        }

        // In order to maintain the order when the endpoint is called we must create an array of objects
        $json = [];
        foreach($days as $key => $value) {
            $json[] = [$key => $value];
        }

        return $json;
    }
}
