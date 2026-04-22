<?php

use App\Http\Controllers\Api\Backend\ApiSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/',function (){
    return view('welcome');
})->middleware('authCheck')->name('home');



Route::post('/stripe/webhook', [ApiSubscriptionController::class, 'handleWebhook']);

require __DIR__.'/auth.php';


