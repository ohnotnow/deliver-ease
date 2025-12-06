<?php

use App\Livewire\Driver\AccessRun;
use App\Livewire\Driver\ActiveRun;
use App\Models\Business;
use App\Models\Run;
use App\Models\User;
use Livewire\Livewire;

it('allows drivers to proceed when the correct PIN is entered', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
        'pin' => '1234',
    ]);

    Livewire::test(AccessRun::class, ['uuid' => $run->uuid])
        ->set('pin', '1234')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('driver.run', ['uuid' => $run->uuid]));

    expect(session()->get('driver_run_'.$run->uuid))->toBeTrue();
});

it('rejects incorrect pins and shows an error state', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
        'pin' => '5678',
    ]);

    Livewire::test(AccessRun::class, ['uuid' => $run->uuid])
        ->set('pin', '0000')
        ->call('submit')
        ->assertSet('invalidPin', true)
        ->assertSet('pin', '');

    expect(session()->has('driver_run_'.$run->uuid))->toBeFalse();
});

it('redirects drivers without a session back to pin entry', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
    ]);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->assertRedirect(route('driver.access', ['uuid' => $run->uuid]));
});
