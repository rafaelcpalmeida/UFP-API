<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class FinalGrades extends Eloquent
{
    protected $collection = 'final_grades';
}
