<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyWithdrawalController;
Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('enquetes', 'enquetes')->name('enquetes');
});
Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
Route::post('/surveys/{survey}', [SurveyController::class, 'store'])->name('surveys.store');

Route::get('/survey-response/{response}/thank-you', [SurveyController::class, 'thankYou'])->name('surveys.thankyou');

Route::get('/survey-withdraw/{token}', [SurveyWithdrawalController::class, 'show'])->name('surveys.withdraw.show');
Route::post('/survey-withdraw/{token}', [SurveyWithdrawalController::class, 'destroy'])->name('surveys.withdraw.destroy');
require __DIR__.'/settings.php';
