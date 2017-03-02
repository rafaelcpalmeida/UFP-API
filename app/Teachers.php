<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Teacher extends Eloquent
{
    protected $collection = 'teachers';
}
