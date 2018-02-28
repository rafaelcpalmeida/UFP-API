<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class AuthController extends Controller
{
    private $request;
    private $soap;
    private $message;
    private $apiToken;
    private $username;
    private $password;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, SOAPController $soap, MessagesController $message)
    {
        $this->request = $request;
        $this->soap = $soap;
        $this->apiToken = $request->input("token");
        $this->username = $request->input("username");
        $this->password = $request->input("password");
        $this->message = $message;
    }

    public function login()
    {
        $authToken = $this->soap->getDataFromSOAPServer("Encrypt", array("Encrypt" => array("phrase" => "$this->username,$this->password")));

        $sessionToken = $this->soap->getDataFromSOAPServer("shakeHands", array("shakeHands" => array("input" => $authToken->EncryptResult)));
        
        return ((isset($sessionToken->shakeHandsResult) && $sessionToken->shakeHandsResult != "")) ? $this->message->encodeMessage(200, $this->message->encryptMessage(["number" => $this->username, "token" => $sessionToken->shakeHandsResult])) : $this->message->encodeMessage(401, "Check your credentials");
    }
}
