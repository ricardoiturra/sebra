<?php

use App\Http\Controllers\SphereColorController;
use App\Http\Controllers\SphereGraphController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:full admin'])->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/sphere/color', [SphereColorController::class, 'store']);
        Route::post('/sphere/direction', [SphereColorController::class, 'direction']);
        Route::post('/sphere/community', [SphereColorController::class, 'community'])->name('sphere.community');
    });

    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/graph/questions', [SphereGraphController::class, 'query']);
        Route::post('/graph/ask', [SphereGraphController::class, 'ask']);
    });
});
