<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect('/board'));
    Route::get('/board', fn() => Inertia::render('Board'))->name('board');
    Route::get('/journal', fn() => Inertia::render('Journal'))->name('journal');
    Route::get('/activity', fn() => Inertia::render('Activity'))->name('activity');
    Route::get('/schedule', fn() => Inertia::render('Schedule'))->name('schedule');
    Route::get('/projects', fn() => Inertia::render('Projects'))->name('projects');
});
