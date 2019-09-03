<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'HomeController@index');
Route::get('home', 'HomeController@index')->name('home');
Route::get('getUserData', 'API\ApiController@getUserData');

Route::controllers([
    'api' => 'API\ApiController',
]);

Route::group([], function () {
    Route::get('auth/logout', 'Auth\AuthController@getLogout')->name('auth.logout');
    Route::group(['middleware' => 'guest'], function () {
        Route::get('auth/login', 'Auth\AuthController@getLogin')->name('auth.login');
        Route::get('auth/two-factor', 'Auth\AuthController@getTwoFactor')->name('auth.two.factor');
        Route::get('auth/register', 'Auth\AuthController@getRegister')->name('auth.register');
        Route::get('authy/status', 'Auth\AuthyController@status');
        Route::get('password/email', 'Auth\PasswordController@getEmail')->name('password.email');

        Route::post('authy/callback', ['middleware' => 'validate_authy', 'uses' => 'Auth\AuthyController@callback']);

        Route::controller('password', 'Auth\PasswordController');
        Route::controller('auth', 'Auth\AuthController');
    });
});

