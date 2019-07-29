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
Route::put('password-reset', 'API\UserController@passwordResetConfirm');

Route::post('user', 'API\UserController@register');

Route::group([
    'middleware' => 'auth:api'
], function() {

    Route::delete('session', 'API\UserController@logout');
    
    Route::get('user/{id}', 'API\UserController@details');

});
