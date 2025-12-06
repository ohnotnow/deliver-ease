<?php

use App\Livewire\Runs\Create;
use App\Livewire\Runs\Edit;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\Run;
use App\Models\User;
use Livewire\Livewire;

it('lets a business owner create a run with deliveries', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $component = Livewire::actingAs($user)
        ->test(Create::class)
        ->set('name', 'Morning Run')
        ->set('pin', '1234')
        ->set('deliveries', [
            ['email' => 'first@example.com', 'name' => 'First Stop'],
            ['email' => 'second@example.com', 'name' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $run = Run::first();

    $component->assertRedirect(route('runs.show', $run));

    expect($run)->not->toBeNull()
        ->and($run->business_id)->toBe($business->id)
        ->and($run->pin)->toBe('1234')
        ->and($run->deliveries)->toHaveCount(2)
        ->and($run->deliveries->pluck('email')->all())->toEqual([
            'first@example.com',
            'second@example.com',
        ])
        ->and($run->deliveries->pluck('position')->all())->toEqual([1, 2]);
});

it('allows pending runs to be updated with new deliveries', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $user->id,
        'name' => 'Original Run',
        'pin' => '1111',
    ]);

    Delivery::factory()->for($run)->create(['position' => 1, 'email' => 'old1@example.com', 'name' => 'Old 1']);
    Delivery::factory()->for($run)->create(['position' => 2, 'email' => 'old2@example.com', 'name' => 'Old 2']);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->set('name', 'Updated Run')
        ->set('pin', '2222')
        ->set('deliveries', [
            ['id' => null, 'email' => 'new1@example.com', 'name' => 'New 1'],
            ['id' => null, 'email' => 'new2@example.com', 'name' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('runs.show', $run));

    $run->refresh();

    expect($run->name)->toBe('Updated Run')
        ->and($run->pin)->toBe('2222')
        ->and($run->deliveries)->toHaveCount(2)
        ->and($run->deliveries->pluck('email')->all())->toEqual([
            'new1@example.com',
            'new2@example.com',
        ])
        ->and($run->deliveries->pluck('position')->all())->toEqual([1, 2]);
});

it('prevents users from editing runs that belong to another business', function () {
    $business = Business::factory()->create();
    $otherBusiness = Business::factory()->create();

    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
    ]);

    $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

    Livewire::actingAs($otherUser)
        ->test(Edit::class, ['run' => $run])
        ->assertForbidden();
});

it('allows pending runs to be deleted by their owner', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $user->id,
    ]);

    Delivery::factory()->for($run)->count(2)->sequence(
        ['position' => 1],
        ['position' => 2],
    )->create();

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->call('delete')
        ->assertRedirect(route('runs.index'));

    expect(Run::whereKey($run->id)->exists())->toBeFalse()
        ->and(Delivery::where('run_id', $run->id)->exists())->toBeFalse();
});
