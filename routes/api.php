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
Route::get('users', 'API\UserController@get');

Route::group([
    'middleware' => 'auth:api',
], function () {
    Route::patch('session', 'API\UserController@renew');
    Route::delete('session', 'API\UserController@logout');
    Route::patch('users', 'API\UserController@update');

    Route::group([
        'middleware' => 'blocked',
    ], function () {
        Route::get('roles', 'API\RoleController@get');

        Route::get('permissions', 'API\PermissionController@get');

        Route::get('individual-quiz-types', 'API\IndividualQuizTypeController@get');

        Route::post('individual-quiz-players', 'API\IndividualQuizPlayerController@create');
        Route::patch('individual-quiz-players', 'API\IndividualQuizPlayerController@update');
        Route::delete('individual-quiz-players', 'API\IndividualQuizPlayerController@delete');

        Route::post('individual-quizzes', 'API\IndividualQuizController@create');
        Route::patch('individual-quizzes', 'API\IndividualQuizController@update');
        Route::delete('individual-quizzes', 'API\IndividualQuizController@delete');

        Route::post('national-rankings', 'API\NationalRankingController@create');
        Route::delete('national-rankings', 'API\NationalRankingController@delete');

        Route::get('notifications', 'API\NotificationController@get');
        Route::post('notifications', 'API\NotificationController@create');
        Route::patch('notifications', 'API\NotificationController@update');
        Route::delete('notifications', 'API\NotificationController@delete');

        Route::get('genres', 'API\GenreController@get');

        Route::get('quizzes', 'API\QuizController@get');
        Route::post('quizzes', 'API\QuizController@create');
        Route::patch('quizzes', 'API\QuizController@update');
        Route::delete('quizzes', 'API\QuizController@delete');

        Route::get('special-quizzes', 'API\SpecialQuizController@get');
        Route::post('special-quizzes', 'API\SpecialQuizController@create');
        Route::patch('special-quizzes', 'API\SpecialQuizController@update');
        Route::delete('special-quizzes', 'API\SpecialQuizController@delete');
    });
});
