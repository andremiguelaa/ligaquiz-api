<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('session', 'API\UserController@login');

Route::post('password-reset', 'API\UserController@passwordResetRequest');
Route::patch('password-reset', 'API\UserController@passwordResetConfirm');

Route::post('users', 'API\UserController@register');

Route::get('national-rankings', 'API\NationalRankingController@get');
Route::get('individual-quiz-players', 'API\IndividualQuizPlayerController@get');
Route::get('individual-quizzes', 'API\IndividualQuizController@get');

Route::group([
    'middleware' => 'auth:api',
], function () {
    Route::patch('session', 'API\UserController@renew');
    Route::delete('session', 'API\UserController@logout');

    Route::get('users', 'API\UserController@get');
    Route::patch('users', 'API\UserController@update');

    Route::get('roles', 'API\RoleController@get');

    Route::get('permissions', 'API\PermissionController@get');

    Route::get('individual-quiz-types', 'API\IndividualQuizTypeController@get');

    Route::post('individual-quiz-players', 'API\IndividualQuizPlayerController@create');

    Route::post('individual-quizzes', 'API\IndividualQuizController@create');
    Route::patch('individual-quizzes', 'API\IndividualQuizController@update');
    Route::delete('individual-quizzes', 'API\IndividualQuizController@delete');

    Route::delete('national-rankings', 'API\NationalRankingController@delete');
});
