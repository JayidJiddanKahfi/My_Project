<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\DashboardController;

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

    Route::post('/create_payment',[PaymentController::class,'create_payment_third_version']);
    Route::get('/read_payment/{targetYear}/{dataPerPage}/{targetMonth?}',[PaymentController::class,'read_for_payments']);
    Route::get('/read_contribution/{targetYear}/{dataPerPage}/{targetMonth?}',[PaymentController::class,'read_for_contributions']);
    Route::put('/update_payment/{residentId}/{paymentDate}/{paymentType}',[PaymentController::class,'update_payment']);
    Route::delete('/delete_payment/{residentId}/{paymentDate}/{paymentType}',[PaymentController::class,'delete_payment']);

   
    Route::get('/report_contribution_pdf/{year}/{month?}', [ReportController::class, 'report_for_contribution_pdf']);
    Route::get('/report_payment_image/{residentId}/{paymentDate}/{paymentType}',[ReportController::class,'report_for_payment_image']);
    Route::get('/report_payment_pdf/{residentId}/{paymentDate}/{paymentType}',[ReportController::class,'report_for_payment_pdf']);

    Route::post('/create_expense',[ExpenseController::class,'create']);
    Route::get('/read_expense',[ExpenseController::class,'read']);

    Route::get('read_dashboard',[DashboardController::class,'read']);

});;