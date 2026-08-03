<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:registration')
        ->name('register.store');
});

Route::middleware('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/docs', [DashboardController::class, 'docs'])->name('docs');
    Route::get('/pair/{device}', [DashboardController::class, 'pair'])->name('pair');
    Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('password.edit');
    Route::put('/change-password', [ChangePasswordController::class, 'update'])
        ->middleware('throttle:password-change')
        ->name('password.update');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
