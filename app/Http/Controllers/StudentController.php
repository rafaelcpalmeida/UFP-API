<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Student;
use App\Http\Controllers\MessagesController;

class StudentController extends Controller {
    private $message;
    private $student;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(MessagesController $message, Student $student) {
        $this->message = $message;
        $this->student = $student;
    }

    public function getAllStudents() {
        return $this->message->encodeMessage(0, $this->student->all());
    }
}
