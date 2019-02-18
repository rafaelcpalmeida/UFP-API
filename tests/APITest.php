<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class APITest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function checkAPIVersion()
    {
        $this->get('/api/v1')->seeJsonEquals(
            ['Version' => '1.0']
        );
    }
}
