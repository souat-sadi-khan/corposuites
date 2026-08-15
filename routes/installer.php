<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Installer\RequirementController;
use App\Http\Controllers\Installer\LicenseController;
use App\Http\Controllers\Installer\DatabaseController;
use App\Http\Controllers\Installer\AdminController;
use App\Http\Controllers\Installer\CompanyController;
use App\Http\Controllers\Installer\InstallerCompleteController;

Route::get('/',[RequirementController::class,'index'])->name('install');
Route::get('/license',[LicenseController::class,'index']);
Route::post('/license',[LicenseController::class,'verify']);
Route::get('/database',[DatabaseController::class,'index']);
Route::get('/migration',[DatabaseController::class,'migration']);
Route::post('/database',[DatabaseController::class,'store']);
Route::get('/admin',[AdminController::class,'index']);
Route::post('/admin',[AdminController::class,'store']);
Route::get('/company',[CompanyController::class,'index']);
Route::post('/company',[CompanyController::class,'store']);
Route::get('/complete', [InstallerCompleteController::class,'index']);
