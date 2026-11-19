<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\GraphNodeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SphereColorController;
use App\Http\Controllers\SphereGraphController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:web',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

    Route::middleware('role:full admin,web')->group(function () {
        Route::view('/projects/sebra-system', 'projects.show', ['project' => null])->name('projects.system');
        Route::get('/api/sphere/events', [SphereColorController::class, 'events'])->name('sphere.events');
        Route::get('/api/sphere/node', [GraphNodeController::class, 'system'])->middleware('throttle:60,1')->name('sphere.node');
        Route::get('/api/sphere/graph', SphereGraphController::class)->name('sphere.graph');
        Route::post('/api/audio/transcribe', [AudioController::class, 'transcribe'])->middleware('throttle:audio')->name('audio.transcribe');
        Route::post('/api/audio/speak', [AudioController::class, 'speak'])->middleware('throttle:audio')->name('audio.speak');
        Route::post('/api/graph/query', [SphereGraphController::class, 'ask'])->middleware('throttle:20,1')->name('graph.query');
    });

    Route::prefix('projects/{project}')->where(['project' => '[0-9]+'])->middleware('can:view,project')->group(function () {
        Route::get('/', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('/node', [GraphNodeController::class, 'project'])->middleware('throttle:60,1')->name('projects.node');
        Route::get('/graph', [SphereGraphController::class, 'projectGraph'])->name('projects.graph');
        Route::get('/events', [SphereColorController::class, 'projectEvents'])->name('projects.events');
        Route::post('/audio/transcribe', [AudioController::class, 'transcribe'])->middleware('throttle:audio')->name('projects.audio.transcribe');
        Route::post('/audio/speak', [AudioController::class, 'speak'])->middleware('throttle:audio')->name('projects.audio.speak');
        Route::post('/ask', [SphereGraphController::class, 'askProject'])->middleware('throttle:20,1')->name('projects.ask');
        Route::post('/actions', [SphereGraphController::class, 'modifyProject'])->middleware('throttle:20,1')->name('projects.actions');
        Route::post('/notes', [ProjectController::class, 'storeNote'])->name('projects.notes.store');
        Route::post('/graph', [ProjectController::class, 'importGraph'])->middleware('can:updateGraph,project')->name('projects.graph.import');
    });
});
