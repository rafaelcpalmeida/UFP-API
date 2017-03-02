<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Teacher;
use App\Http\Controllers\MessagesController;

class TeacherController extends Controller {
    private $message;
    private $teacher;
    private $teacherDetails;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, MessagesController $message, Teacher $teacher) {
        $this->teacherDetails = $request->input("details");
        $this->message = $message;
        $this->teacher = $teacher;
    }

    public function getTeacherDetails($alias) {
        if(!$this->hasTeacherDetails($alias)) {
            $newTeacher = new $this->teacher;

            $newTeacher->alias = $alias;

            $newTeacher->save();
            
            return $this->message->encodeMessage(1, "No teacher information found");
        }

        return $this->message->encodeMessage(0, $this->teacher->where("alias", "=", $alias)->first());
    }

    public function storeTeacherDetails($alias) {
        if(!$this->hasTeacherDetails($alias)) {
            $newTeacher = new $this->teacher;

            $newTeacher->alias = $alias;
            $newTeacher->details = json_decode($this->teacherDetails);

            $newTeacher->save();
            
            return;
        }

        $existingTeacher = $this->teacher->where("alias", "=", $alias)->first();

        $existingTeacher->details = json_decode($this->teacherDetails);

        $existingTeacher->save();
    }

    private function hasTeacherDetails($alias) {
        return $this->teacher->where("alias", "=", $alias)->exists();
    }
}
