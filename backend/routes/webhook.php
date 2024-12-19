<?php

use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

// Webhook routes without any middleware
Route::get('/webhook', [ChatbotController::class, 'verifyWebhook']);
Route::post('/webhook', [ChatbotController::class, 'handleWebhook']);
