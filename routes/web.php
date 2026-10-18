<?php

use App\Http\Controllers\InboxController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PartController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'company'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('parts', PartController::class)->except('show');
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('inbox/{conversation}', [InboxController::class, 'show'])->name('inbox.show');
    Route::post('inbox/{conversation}/take-over', [InboxController::class, 'takeOver'])->name('inbox.take-over');
    Route::post('inbox/{conversation}/resume-automation', [InboxController::class, 'resumeAutomation'])->name('inbox.resume-automation');
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('parts/{part}/toggle', [PartController::class, 'toggle'])->name('parts.toggle');
    Route::get('parts/{part}/inventory', [PartController::class, 'inventoryHistory'])->name('parts.inventory-history');
    Route::get('parts/{part}/inventory/adjust', [PartController::class, 'createInventoryAdjustment'])->name('parts.inventory-adjustment.create');
    Route::post('parts/{part}/inventory/adjust', [PartController::class, 'storeInventoryAdjustment'])->name('parts.inventory-adjustment.store');
});

require __DIR__.'/settings.php';
