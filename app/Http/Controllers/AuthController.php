<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class AuthController extends Controller {
    private $request;
    private $soap;
    private $message;
    private $apiToken;
    private $username;
    private $password;
    private $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, SOAPController $soap, MessagesController $message, User $user) {
        $this->request = $request;
        $this->soap = $soap;
        $this->apiToken = $request->input("token");
        $this->username = $request->input("username");
        $this->password = $request->input("password");
        $this->user = $user;
        $this->message = $message;
    }

    public function login() {
        $authToken = $this->soap->getDataFromSOAPServer("Encrypt", array("Encrypt" => array("phrase" => "$this->username,$this->password")));

        $sessionToken = $this->soap->getDataFromSOAPServer("shakeHands", array("shakeHands" => array("input" => $authToken->EncryptResult)));

        if (isset($sessionToken->shakeHandsResult) && $sessionToken->shakeHandsResult != "") {
            if (!$this->hasUserDetails("number", $this->username)) {
                $newUser = new $this->user;

                $newUser->number = $this->username;
                $newUser->password = $this->message->encryptMessage($this->password);
                $newUser->token = $this->message->encryptMessage($sessionToken->shakeHandsResult);

                $newUser->save();
            } else {
                $existingUser = $this->user->where("number", "=", $this->username)->first();

                $existingUser->password = $this->message->encryptMessage($this->password);
                $existingUser->token = $this->message->encryptMessage($sessionToken->shakeHandsResult);

                $existingUser->save();
            }

            return $this->message->encodeMessage(0, $this->message->encryptMessage(["number" => $this->username, "token" => $sessionToken->shakeHandsResult]));
        }

        return $this->message->encodeMessage(1, "Check your credentials");
    }

    private function hasUserDetails($detail, $userNumber) {
        return $this->user->where($detail, "=", $userNumber)->exists();
    }
}
