<?php

use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('enquetes', 'enquetes')->name('enquetes');
});

Route::prefix('survey')->name('survey.')->group(function () {
    Route::get('/{survey}', [SurveyController::class, 'show'])->name('show');
    Route::post('/{survey}', [SurveyController::class, 'store'])->name('store');
    Route::get('/response/{response}/thank-you', [SurveyController::class, 'thankYou'])->name('thankyou');
    Route::post('/response/{response}/contact-details', [SurveyController::class, 'storeContactDetails'])->name('contact-details.store');
});

Route::prefix('survey-withdraw')->name('survey.withdraw.')->group(function () {
    Route::get('/{token}', [SurveyWithdrawalController::class, 'show'])->name('show');
    Route::post('/{token}', [SurveyWithdrawalController::class, 'destroy'])->name('destroy');
});

Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');

require __DIR__.'/settings.php';
