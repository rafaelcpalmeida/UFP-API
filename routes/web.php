<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', 'APIController@index');

$app->group(['prefix' => 'api'], function () use ($app) {
    $app->group(['prefix' => 'v1'], function () use ($app) {
        $app->get('/', function () {
            return json_encode(["Version" => "1.0"]);
        });

        $app->post('login', 'AuthController@login');

        $app->get('queue', 'QueueController@getQueue');
        $app->get('menu/{language}', 'MenuController@getMenu');
        $app->get('teachers/{option}', 'TeacherController@getTeachers');
        
        $app->group(['middleware' => 'checkToken'], function () use ($app) {
            $app->get('assiduity', 'AssiduityController@getAssiduity');
            $app->get('atm', 'MBController@getMB');
            $app->get('exams', 'ExamController@getExams');
            $app->get('grades/detailed', 'GradesController@getDetailedGrades');
            $app->get('grades/final', 'GradesController@getFinalGrades');
            $app->get('grades/exam', 'GradesController@getExamGrades');
            $app->get('schedule', 'ScheduleController@getSchedule');
        });
    });
});
