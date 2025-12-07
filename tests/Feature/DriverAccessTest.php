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
        'pin_hash' => Run::hashPin('123456'),
        'pin_hint' => Run::pinHint('123456'),
    ]);

    Livewire::test(AccessRun::class, ['uuid' => $run->uuid])
        ->set('pin', '123456')
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
        'pin_hash' => Run::hashPin('567890'),
        'pin_hint' => Run::pinHint('567890'),
    ]);

    Livewire::test(AccessRun::class, ['uuid' => $run->uuid])
        ->set('pin', '000000')
        ->call('submit')
        ->assertSet('invalidPin', true)
        ->assertSet('pin', '');

    expect(session()->has('driver_run_'.$run->uuid))->toBeFalse();
});

it('throttles excessive pin attempts', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
        'pin_hash' => Run::hashPin('123456'),
        'pin_hint' => Run::pinHint('123456'),
    ]);

    $component = Livewire::test(AccessRun::class, ['uuid' => $run->uuid]);

    // 5 bad attempts
    for ($i = 0; $i < 5; $i++) {
        $component->set('pin', '111111')
            ->call('submit');
    }

    // 6th attempt should be blocked with a validation error
    $component->set('pin', '111111')
        ->call('submit')
        ->assertHasErrors(['pin']);
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
