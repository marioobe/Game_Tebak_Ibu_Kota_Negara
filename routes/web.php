<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

// Views
Route::get('/', [GameController::class, 'index'])->name('home');
Route::get('/host', [GameController::class, 'hostSetup'])->name('host.setup');
Route::get('/host/{code}', [GameController::class, 'hostRoom'])->name('host.room');
Route::get('/play/{code}', [GameController::class, 'playerRoom'])->name('player.room');

// APIs
Route::prefix('api/rooms')->group(function () {
    Route::post('/', [GameController::class, 'createRoom'])->name('api.rooms.create');
    Route::post('/{code}/join', [GameController::class, 'joinRoom'])->name('api.rooms.join');
    Route::post('/{code}/start', [GameController::class, 'startMatch'])->name('api.rooms.start');
    Route::post('/{code}/answer', [GameController::class, 'submitAnswer'])->name('api.rooms.answer');
    Route::post('/{code}/next', [GameController::class, 'nextQuestion'])->name('api.rooms.next');
    Route::post('/{code}/kick', [GameController::class, 'kickPlayer'])->name('api.rooms.kick');
    Route::post('/{code}/end', [GameController::class, 'endMatch'])->name('api.rooms.end');
    Route::post('/{code}/reset', [GameController::class, 'resetRoom'])->name('api.rooms.reset');
    Route::get('/{code}/state', [GameController::class, 'getRoomState'])->name('api.rooms.state');
    Route::get('/{code}/events', [GameController::class, 'streamEvents'])->name('api.rooms.events');
});
