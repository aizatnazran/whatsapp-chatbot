<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

// WhatsApp webhook route
Route::match(['get', 'post'], '/webhook', [ChatbotController::class, 'webhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('web');

Route::get('/', function () {
    return view('welcome');
});

// Test route
Route::get('/test', function() {
    return response()->json(['status' => 'working']);
});
