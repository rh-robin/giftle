<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



/*============ LOG CLEAR ROUTE ==========*/
Route::get('/log-clear/rh', function () {
    file_put_contents(storage_path('logs/laravel.log'), '');
    return 'Log file cleared!';
});

Route::get('/log/rh', function () {
    $path = storage_path('logs/laravel.log');

    if (!File::exists($path)) {
        return response('Log file does not exist.', 404);
    }

    $logContent = File::get($path);

    return response("<pre>{$logContent}</pre>");
})->name('logs.view');
