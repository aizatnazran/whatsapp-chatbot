<?php

use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

// API routes for the frontend
Route::middleware(['api'])->group(function () {
    Route::get('/appointments', function () {
        return \App\Models\Appointment::with('user')->get();
    });

    Route::get('/users', function () {
        return \App\Models\User::with('appointments')->get();
    });
});
