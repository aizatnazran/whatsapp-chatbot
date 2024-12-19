<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\AppointmentController;

// No routes needed in api.php as we're using webhook.php for WhatsApp endpoints

// API routes for the frontend
Route::get('/appointments', [AppointmentController::class, 'index']);
Route::put('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);

Route::get('/users', function () {
    return \App\Models\User::with('appointments')->get();
});
