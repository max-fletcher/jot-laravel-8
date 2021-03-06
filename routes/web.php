<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::get('/logout-manual', function(){
    request()->session()->invalidate();
});

// The where method uses a regex. This is currently saying:
// If 'any' has one or more characters after the / is passed to AppController
Route::get('/{any}', [App\Http\Controllers\AppController::class, 'index'])->where('any', '.*');
