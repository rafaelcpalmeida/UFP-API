<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class DetailedGrades extends Eloquent
{
    protected $collection = 'detailed_grades';
}
