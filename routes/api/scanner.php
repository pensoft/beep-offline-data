<?php

use Illuminate\Support\Facades\Route;

Route::group(
    [
        'namespace' => 'Scanner',
        'prefix'    => 'scanner',
        'as'        => 'Scanner::',
    ],
    function () {
        Route::group(
            [
                'namespace' => 'Scan',
                'prefix'    => 'scan',
                'as'        => 'Scan::',
            ],
            function () {
                Route::post('/', 'ScansController@scan')->name('scan');
            }
        );
    }
);
