<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Student extends Eloquent
{
    protected $collection = 'students';
    protected $hidden = ['_id'];
}
