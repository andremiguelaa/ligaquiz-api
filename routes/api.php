<?php

use Illuminate\Http\Request;

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

Route::group([
    'middleware' => 'auth:api'
], function () {

    Route::patch('session', 'API\UserController@renew');
    Route::delete('session', 'API\UserController@logout');

    Route::get('users', 'API\UserController@list');
    Route::patch('users', 'API\UserController@update');

    Route::get('roles', 'API\RoleController@list');

    Route::get('permissions', 'API\PermissionController@list');

    Route::get('individual-quizzes', 'API\IndividualQuizController@list');
    Route::get('individual-quiz-types', 'API\IndividualQuizTypeController@list');
    Route::get('individual-quiz-players', 'API\IndividualQuizPlayerController@list');
});
