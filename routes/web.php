<?php

use App\Http\Controllers\ChatbotController;

Route::get('/chat', function () {
    return view('chat');
});

Route::post('/chatbot', [ChatbotController::class, 'getResponse']);
Route::post('/chatbot/tools', [ChatbotController::class, 'getResponseWithTools']);

