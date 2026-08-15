<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});
Route::get('/language/{code}', [LanguageController::class,'change']

)->name('language.change');

Route::get('admin', function() {
    return redirect()->route('admin.login');
})->name('admin');
