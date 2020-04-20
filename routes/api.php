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

Route::get('national-rankings', 'API\NationalRankingController@list');
Route::get('individual-quiz-players', 'API\IndividualQuizPlayerController@list');
Route::get('individual-quizzes', 'API\IndividualQuizController@list');

Route::group([
    'middleware' => 'auth:api',
], function () {
    Route::patch('session', 'API\UserController@renew');
    Route::delete('session', 'API\UserController@logout');

    Route::get('users', 'API\UserController@list');
    Route::patch('users', 'API\UserController@update');

    Route::get('roles', 'API\RoleController@list');

    Route::get('permissions', 'API\PermissionController@list');

    Route::get('individual-quiz-types', 'API\IndividualQuizTypeController@list');

    Route::post('individual-quiz-players', 'API\IndividualQuizPlayerController@create');

    Route::post('individual-quizzes', 'API\IndividualQuizController@create');
    Route::patch('individual-quizzes', 'API\IndividualQuizController@update');
});
