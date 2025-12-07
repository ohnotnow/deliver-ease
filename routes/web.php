<?php

use App\Livewire\Dashboard;
use App\Livewire\Driver\AccessRun;
use App\Livewire\Driver\ActiveRun;
use App\Livewire\Runs\Index as RunsIndex;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::get('/', function () {
    return view('welcome');
});

// Authenticated routes (business owner)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/runs', RunsIndex::class)->name('runs.index');
    Route::get('/runs/create', App\Livewire\Runs\Create::class)->name('runs.create');
    Route::get('/runs/{run}', App\Livewire\Runs\Show::class)->name('runs.show');
    Route::get('/runs/{run}/edit', App\Livewire\Runs\Edit::class)->name('runs.edit');
    Route::get('/runs/{run}/import', App\Livewire\Runs\Import::class)->name('runs.import');
});

// Driver routes (Public access with PIN)
Route::prefix('driver')->name('driver.')->group(function () {
    Route::get('/run/{uuid}', AccessRun::class)->name('access');
    Route::get('/run/{uuid}/active', ActiveRun::class)->name('run');
});
