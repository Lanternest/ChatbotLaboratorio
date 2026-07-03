<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EmailController;

/*
|--------------------------------------------------------------------------
| API Routes – Chatbot Laboratorio HU UNCuyo
|--------------------------------------------------------------------------
*/

Route::post('/chat', [ChatController::class, 'chat']);
Route::post('/send-email', [EmailController::class, 'sendEmail']);
Route::get('/', fn() => response()->json([
    'status' => 'ok',
    'servicio' => 'Chatbot Laboratorio HU UNCuyo'
]));