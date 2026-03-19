<?php

use App\Livewire\PublicEnquetes\Fill as PublicEnqueteFill;
use App\Livewire\PublicEnquetes\Index as PublicEnqueteIndex;
use App\Livewire\PublicEnquetes\Show as PublicEnqueteShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('enquetes', PublicEnqueteIndex::class)->name('enquetes.index');
Route::livewire('enquetes/{enquete}', PublicEnqueteShow::class)->name('enquetes.show');
Route::livewire('enquetes/{enquete}/begin', PublicEnqueteFill::class)->name('enquetes.fill');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('admin/enquetes', 'enquetes')->name('enquetes.manage');
});

require __DIR__.'/settings.php';
