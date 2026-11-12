<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [UserController::class, 'current'])->name('api.user');
    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->can('update', 'user')
        ->name('api.users.update');
});
