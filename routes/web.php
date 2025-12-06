<?php

use App\Livewire\Dashboard;
use App\Livewire\Driver\AccessRun;
use App\Livewire\Driver\ActiveRun;
use App\Livewire\Runs\Create as RunsCreate;
use App\Livewire\Runs\Edit as RunsEdit;
use App\Livewire\Runs\Index as RunsIndex;
use App\Livewire\Runs\Show as RunsShow;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::get('/', function () {
    return view('welcome');
});

// Authenticated routes (business owner)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/runs', RunsIndex::class)->name('runs.index');
    Route::get('/runs/create', RunsCreate::class)->name('runs.create');
    Route::get('/runs/{run}', RunsShow::class)->name('runs.show');
    Route::get('/runs/{run}/edit', RunsEdit::class)->name('runs.edit');
});

// Public driver routes
Route::prefix('driver')->name('driver.')->group(function () {
    Route::get('/run/{uuid}', AccessRun::class)->name('access');
    Route::get('/run/{uuid}/active', ActiveRun::class)->name('run');
});
