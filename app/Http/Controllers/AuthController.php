<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Student;
use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;

class AuthController extends Controller {
    private $request;
    private $soap;
    private $message;
    private $apiToken;
    private $username;
    private $password;
    private $student;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, SOAPController $soap, MessagesController $message, Student $student) {
        $this->request = $request;
        $this->soap = $soap;
        $this->apiToken = $request->input("token");
        $this->username = $request->input("username");
        $this->password = $request->input("password");
        $this->student = $student;
        $this->message = $message;
    }

    public function login() {
        $authToken = $this->soap->getDataFromSOAPServer("Encrypt", array("Encrypt" => array("phrase" => "$this->username,$this->password")));

        $sessionToken = $this->soap->getDataFromSOAPServer("shakeHands", array("shakeHands" => array("input" => $authToken->EncryptResult)));

        if (isset($sessionToken->shakeHandsResult) && $sessionToken->shakeHandsResult != "") {
            if (!$this->hasStudentDetails("number", $this->username)) {
                $newStudent = new $this->student;

                $newStudent->number = $this->username;
                $newStudent->password = $this->message->encryptMessage($this->password);
                $newStudent->token = $this->message->encryptMessage($sessionToken->shakeHandsResult);

                $newStudent->save();
            } else {
                $newStudent = $this->student->where("number", "=", $this->username)->first();

                $newStudent->password = $this->message->encryptMessage($this->password);
                $newStudent->token = $this->message->encryptMessage($sessionToken->shakeHandsResult);

                $newStudent->save();
            }

            return $this->message->encodeMessage(0, $this->message->encryptMessage(["number" => $this->username, "token" => $sessionToken->shakeHandsResult]));
        }

        return $this->message->encodeMessage(1, "Check your credentials");
    }

    private function hasStudentDetails($detail, $userNumber) {
        return $this->student->where($detail, "=", $userNumber)->exists();
    }
}
