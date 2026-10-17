<?php

use App\Http\Controllers\PartController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'company'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('parts', PartController::class)->except('show');
    Route::patch('parts/{part}/toggle', [PartController::class, 'toggle'])->name('parts.toggle');
});

require __DIR__.'/settings.php';
