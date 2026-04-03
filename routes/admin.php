<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::livewire('users', 'pages::admin.users.index')->name('users.index');
        Route::livewire('users/create', 'pages::admin.users.create')->name('users.create');
        Route::livewire('users/{user}/edit', 'pages::admin.users.edit')->name('users.edit');
    });
