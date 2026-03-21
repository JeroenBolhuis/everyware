<?php

use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('enquetes', 'enquetes')->name('enquetes');
});

Route::name('surveys.')->group(function () {
    Route::controller(SurveyController::class)->group(function () {
        Route::get('/surveys/{survey}', 'show')->name('show');
        Route::post('/surveys/{survey}', 'store')->name('store');
        Route::get('/survey-response/{response}/thank-you', 'thankYou')->name('thankyou');
    });

    Route::prefix('survey-withdraw')
        ->name('withdraw.')
        ->controller(SurveyWithdrawalController::class)
        ->group(function () {
            Route::get('/{token}', 'show')->name('show');
            Route::post('/{token}', 'destroy')->name('destroy');
        });
});

require __DIR__.'/settings.php';
