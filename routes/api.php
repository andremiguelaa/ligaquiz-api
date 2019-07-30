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

Route::post('sessions', 'API\UserController@login');

Route::post('password-resets', 'API\UserController@passwordResetRequest');
Route::put('password-resets', 'API\UserController@passwordResetConfirm');

Route::post('users', 'API\UserController@register');

Route::group([
    'middleware' => 'auth:api'
], function () {

    Route::delete('sessions', 'API\UserController@logout');

    Route::get('users', 'API\UserController@list');
});
