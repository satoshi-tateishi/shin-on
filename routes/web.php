<?php

use App\Http\Controllers\Auth\LineWorksController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// LINE WORKS OAuth認証ルート
Route::prefix('auth/lineworks')->group(function () {
    Route::get('redirect', [LineWorksController::class, 'redirect'])->name('lineworks.redirect');
    Route::get('callback', [LineWorksController::class, 'callback'])->name('lineworks.callback');
    Route::post('process-id-token', [LineWorksController::class, 'processIdToken'])->name('lineworks.process-id-token');
});

// 認証が必要なルート
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [LineWorksController::class, 'logout'])->name('logout');
});

// 未認証ユーザー向けのログインページ
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');
