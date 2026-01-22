<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/scan', [ScanController::class, 'startScan']);
    Route::get('/scan/{id}', [ScanController::class, 'getScan']);
    Route::get('/scan/{id}/pdf', [ScanController::class, 'generatePDF']);
    Route::get('/scans', [ScanController::class, 'listScans']);//إنشاء Dashboard لعرض الفحوصات
    Route::delete('/scan/{id}', [ScanController::class, 'deleteScan']);//إضافة إمكانية حذف الفحص
    Route::post('/scan/{id}/rescan', [ScanController::class, 'rescan']);//إضافة إمكانية إعادة الفحص (Rescan)




});
