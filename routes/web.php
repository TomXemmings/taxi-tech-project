<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AmoCrmWebhookController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AmoCrmAuthController;
use App\Http\Controllers\YandexController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/drivers',         [DriverController::class, 'index'])->name('drivers.index');
    Route::delete('/drivers/{id}', [DriverController::class, 'destroy'])->name('drivers.destroy');
});

Route::get('/oauth/redirect', [AmoCrmAuthController::class, 'redirectToAmoCRM']);
Route::get('/oauth/callback', [AmoCrmAuthController::class, 'handleCallback']);

Route::post('/amocrm/webhook', [AmoCrmWebhookController::class, 'handleWebhook']);

Route::middleware('yandex.auth')->group(function () {
    Route::post('/api/yandex/auth',        [YandexController::class, 'auth']);
    Route::post('/api/yandex/get-cookies', [YandexController::class, 'getCookies']);
});
require __DIR__.'/auth.php';
