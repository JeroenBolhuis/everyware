<?php

use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyWithdrawalController;
use App\Http\Controllers\SurveyManagementController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('enquetes', 'enquetes')->name('enquetes');
});
Route::middleware(['auth', 'verified', 'role:admin|lic-medewerker'])->group(function () {
    Route::get('enquetes', [SurveyManagementController::class, 'index'])->name('enquetes');
    Route::get('enquetes/nieuw', [SurveyManagementController::class, 'create'])->name('enquetes.create');
    Route::post('enquetes', [SurveyManagementController::class, 'store'])->name('enquetes.store');
    Route::get('enquetes/{survey}/bewerken', [SurveyManagementController::class, 'edit'])->name('enquetes.edit');
    Route::put('enquetes/{survey}', [SurveyManagementController::class, 'update'])->name('enquetes.update');
    Route::patch('enquetes/{survey}/sluiten', [SurveyManagementController::class, 'close'])->name('enquetes.close');
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
require __DIR__.'/admin.php';
