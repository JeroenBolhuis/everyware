<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('role:admin|lic-medewerker')->group(function () {
            Route::livewire('surveys', 'pages::admin.surveys.index')->name('surveys.index');
            Route::livewire('surveys/{survey}', 'pages::admin.surveys.show')->name('surveys.show');
            Route::livewire('responses/{response}', 'pages::admin.responses.show')->name('responses.show');
        });

        Route::middleware('role:admin')->group(function () {
            Route::livewire('users', 'pages::admin.users.index')->name('users.index');
            Route::livewire('users/create', 'pages::admin.users.create')->name('users.create');
            Route::livewire('users/{user}/edit', 'pages::admin.users.edit')->name('users.edit');
        });
    });
