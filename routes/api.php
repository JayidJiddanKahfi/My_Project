<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\UserController;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class,'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me',[AuthController::class,'me']);
    Route::post('/logout',[AuthController::class,'logout']);

    Route::middleware('adminMiddleware')->group(function(){

        Route::post('/create_user',[UserController::class,'create']);
        Route::get('/read_user',[UserController::class,'read']);
        Route::put('/update_user/{id}',[UserController::class,'update']);
        Route::delete('/delete_user/{id}',[UserController::class,'delete']);

        Route::post('/create_resident',[ResidentController::class,'create']);
        Route::get('/read_resident',[ResidentController::class,'read']);
        Route::put('/update_resident/{id}',[ResidentController::class,'update']);
        Route::delete('/delete_resident/{id}',[ResidentController::class,'delete']);

        Route::post('/create_fee',[FeeController::class,'create']);
        Route::get('/read_fee',[FeeController::class,'read']);
        Route::put('/update_fee',[FeeController::class,'update']);
        Route::delete('/delete_fee',[FeeController::class,'delete']);

    });

    Route::post('/create_payment',[PaymentController::class,'create']);
    Route::get('/read_payment',[PaymentController::class,'read']);

    Route::post('/create_expense',[ExpenseController::class,'create']);
    Route::get('/read_expense',[ExpenseController::class,'read']);

});;