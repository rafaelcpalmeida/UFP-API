<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;

class MessagesController extends Controller
{
    private $crypt;
    private $secure;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Encrypter $crypt)
    {
        $this->crypt = $crypt;
        $this->secure = $request->input("secure") ? true : false;
    }

    public function decryptToken($encryptedToken)
    {
        try {
            return $this->crypt->decrypt($encryptedToken);
        } catch (DecryptException $e) {
            return false;
        }
    }

    public function encodeMessage($status, $message)
    {
        return response(json_encode(["status" => ($status == 200) ? "Ok" : "Error", "message" => $this->secure ? $this->encryptMessage($message) : $message]), $status)->header('Content-Type', 'application/json');
    }

    public function encryptMessage($message)
    {
        return $this->crypt->encrypt($message);
    }
}
