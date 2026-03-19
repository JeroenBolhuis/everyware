<?php

use App\Livewire\Student\Survey as StudentSurvey;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('student/enquete', StudentSurvey::class)->name('student.survey.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('enquetes', 'enquetes')->name('enquetes');
});

require __DIR__.'/settings.php';
