<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class APIController extends Controller {    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    public function index() {
        return json_encode(["Version" => "1.0"]);
    }
}
