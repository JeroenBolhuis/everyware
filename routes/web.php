<?php

use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyManagerController;
use App\Http\Controllers\SurveyWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('enquetes', 'enquetes')->name('enquetes');
});
Route::middleware(['auth', 'verified', 'role:admin|LICEmployee'])
    ->prefix('enquetes')
    ->name('survey-manager.')
    ->group(function () {
        Route::get('/', [SurveyManagerController::class, 'index'])->name('index');
        Route::get('/nieuw', [SurveyManagerController::class, 'create'])->name('create');
        Route::post('/', [SurveyManagerController::class, 'store'])->name('store');
        Route::get('/{survey}/bewerken', [SurveyManagerController::class, 'edit'])->name('edit');
        Route::put('/{survey}', [SurveyManagerController::class, 'update'])->name('update');
        Route::patch('/{survey}/sluiten', [SurveyManagerController::class, 'close'])->name('close');
    });

Route::prefix('survey')->name('survey.')->group(function () {
    Route::get('/thank-you', [SurveyController::class, 'genericThankYou'])->name('thankyou.generic');
    Route::get('/{survey}', [SurveyController::class, 'show'])->name('show');
    Route::post('/{survey}', [SurveyController::class, 'store'])->name('store');
    Route::get('/response/{response}/thank-you', [SurveyController::class, 'thankYou'])->name('thankyou');
    Route::post('/response/{response}/contact-details', [SurveyController::class, 'storeContactDetails'])->name('contact-details.store');
});

Route::prefix('s')->name('survey.share.')->group(function () {
    Route::get('/{token}', [SurveyController::class, 'showByToken'])->name('show');
    Route::post('/{token}', [SurveyController::class, 'storeByToken'])->middleware('throttle:5,1')->name('store');
});

Route::prefix('survey-withdraw')->name('survey.withdraw.')->group(function () {
    Route::get('/{token}', [SurveyWithdrawalController::class, 'show'])->name('show');
    Route::post('/{token}', [SurveyWithdrawalController::class, 'destroy'])->name('destroy');
});

Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
