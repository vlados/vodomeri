<?php

use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('welcome');

// Invitation system
Route::get('invitation/{code}', [InvitationController::class, 'showAcceptForm'])
    ->name('invitation.accept');
Route::post('invitation/{code}', [InvitationController::class, 'accept'])
    ->name('invitation.process');

// Dashboard
Route::get('dashboard', App\Livewire\Resident\Dashboard::class)
    ->middleware(['auth'])
    ->name('dashboard');

// Readings history
Route::get('readings/history', App\Livewire\Resident\MeterReadings::class)
    ->middleware(['auth', 'verified'])
    ->name('readings.history');

// Submit reading
Route::get('readings/submit/{meterId?}', App\Livewire\Resident\SubmitReading::class)
    ->middleware(['auth', 'verified'])
    ->name('readings.submit');

// Submit multiple readings
Route::get('readings/submit-multiple', App\Livewire\Resident\SubmitMultipleReadings::class)
    ->middleware(['auth', 'verified'])
    ->name('readings.multiple');

// Add new water meter
Route::get('meters/add', App\Livewire\Resident\AddWaterMeter::class)
    ->middleware(['auth', 'verified'])
    ->name('meters.add');

// Edit water meter
Route::get('meters/edit/{meterId}', App\Livewire\Resident\EditWaterMeter::class)
    ->middleware(['auth', 'verified'])
    ->name('meters.edit');

// List water meters
Route::get('meters/list', App\Livewire\Resident\WaterMetersList::class)
    ->middleware(['auth', 'verified'])
    ->name('meters.list');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

// Impersonation routes
Route::impersonate();

require __DIR__.'/auth.php';
