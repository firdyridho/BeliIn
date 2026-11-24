<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'BeliIn API',
        'message' => 'Welcome to BeliIn REST API. Please use /api/v1 for all endpoints.',
    ]);
});
