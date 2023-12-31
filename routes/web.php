<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group(
    [
        'namespace' => 'App\Http\Controllers\Scanner',
        'prefix'    => 'scanner',
        'as'        => 'Scanner::',
    ],
    function () {
        Route::group(
            [
                'namespace' => 'Generator',
                'prefix'    => 'generator',
                'as'        => 'Generator::',
            ],
            function () {
                Route::get('/', 'RequestGeneratorsController@index')->name('index');
                Route::post('create', 'RequestGeneratorsController@create')->name('create');
                Route::get('view', 'RequestGeneratorsController@view')->name('view');
            }
        );
    }
);
