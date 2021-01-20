<?php

use Illuminate\Http\Request;
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

//Grouping up all other routes inside auth api route to implement auth.
Route::middleware('auth:api')->group(function(){
    Route::get('/contacts', [App\Http\Controllers\ContactsController::class, 'index']);
    Route::post('/contacts', [App\Http\Controllers\ContactsController::class, 'store']);
    Route::get('/contacts/{contact}', [App\Http\Controllers\ContactsController::class, 'show']);
    Route::patch('/contacts/{contact}', [App\Http\Controllers\ContactsController::class, 'update']);
    Route::delete('/contacts/{contact}', [App\Http\Controllers\ContactsController::class, 'destroy']);

    Route::get('/birthdays', [App\Http\Controllers\BirthdaysController::class, 'index']);
    
});

