<?php

use App\Enums\RunStatus;
use App\Livewire\Runs\Create;
use App\Livewire\Runs\Edit;
use App\Livewire\Runs\Index;
use App\Livewire\Runs\Show;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\Run;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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

it('validates fields when creating a run', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('name', '')
        ->set('pin', '12')
        ->set('deliveries', null)
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'pin',
            'deliveries' => 'required',
        ]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('name', str_repeat('a', 300))
        ->set('pin', 'abc')
        ->set('deliveries', [
            ['email' => 'not-an-email', 'name' => str_repeat('b', 300)],
        ])
        ->call('save')
        ->assertHasErrors([
            'name' => 'max',
            'pin',
            'deliveries.0.email' => 'email',
            'deliveries.0.name' => 'max',
        ]);

    expect(Run::count())->toBe(0);
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

it('validates fields when updating a run', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $user->id,
        'name' => 'Original',
        'pin' => '1234',
    ]);

    Delivery::factory()->for($run)->create(['position' => 1, 'email' => 'first@example.com']);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->set('name', '')
        ->set('pin', '123')
        ->set('deliveries', null)
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'pin',
            'deliveries' => 'required',
        ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->set('name', str_repeat('x', 260))
        ->set('pin', 'abc')
        ->set('deliveries', [
            ['id' => null, 'email' => 'bad-email', 'name' => str_repeat('y', 260)],
        ])
        ->call('save')
        ->assertHasErrors([
            'name' => 'max',
            'pin',
            'deliveries.0.email' => 'email',
            'deliveries.0.name' => 'max',
        ]);

    expect($run->fresh()->name)->toBe('Original')
        ->and($run->fresh()->pin)->toBe('1234');
});

it('lists only the authenticated business runs and can filter by status', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $pending = Run::factory()->for($business)->create(['created_by_user_id' => $user->id, 'status' => RunStatus::Pending, 'name' => 'Pending Run']);
    $inProgress = Run::factory()->for($business)->inProgress()->create(['created_by_user_id' => $user->id, 'name' => 'In Progress Run']);
    $completed = Run::factory()->for($business)->completed()->create(['created_by_user_id' => $user->id, 'name' => 'Completed Run']);

    // Another business run should be hidden
    Run::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertCount('runs', 3)
        ->set('status', RunStatus::InProgress->value)
        ->assertCount('runs', 1)
        ->assertSee($inProgress->name)
        ->assertDontSee($pending->name)
        ->assertDontSee($completed->name);
});

it('redirects guests away from the runs index', function () {
    $this->get('/runs')->assertRedirect('/login');
});

it('forbids users without a business from accessing runs', function () {
    $user = User::factory()->create(['business_id' => null]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->assertForbidden();
});

it('keeps at least one delivery row when removing', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->call('removeDelivery', 0)
        ->assertCount('deliveries', 1);
});

it('generates a four digit pin when regenerating', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $component = Livewire::actingAs($user)
        ->test(Create::class);

    $firstPin = $component->get('pin');

    $component->call('regeneratePin');

    $newPin = $component->get('pin');

    expect($newPin)->toMatch('/^\\d{4}$/')
        ->and($newPin)->not->toBe('');

    // It may occasionally match by chance; ensure at least format correctness.
    expect(strlen($newPin))->toBe(4);
    expect(strlen($firstPin))->toBe(4);
});

it('persists reordered deliveries when saving edits', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $user->id,
        'pin' => '1234',
    ]);

    $first = Delivery::factory()->for($run)->create(['position' => 1, 'email' => 'first@example.com']);
    $second = Delivery::factory()->for($run)->create(['position' => 2, 'email' => 'second@example.com']);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->call('sortDelivery', 1, 0)
        ->call('save')
        ->assertHasNoErrors();

    $run->refresh();

    expect($run->deliveries()->orderBy('position')->pluck('email')->all())->toEqual([
        $second->email,
        $first->email,
    ]);
});

it('allows owners to view their run details', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $user->id,
    ]);

    Delivery::factory()->for($run)->create(['position' => 1, 'email' => 'first@example.com']);
    Delivery::factory()->for($run)->completed()->create(['position' => 2, 'email' => 'second@example.com']);

    Livewire::actingAs($user)
        ->test(Show::class, ['run' => $run])
        ->assertOk()
        ->assertSee('first@example.com')
        ->assertSee('second@example.com');
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

it('allows completed runs to be deleted by their owner', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->completed()->create([
        'created_by_user_id' => $user->id,
    ]);

    Delivery::factory()->for($run)->count(1)->create(['position' => 1]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->call('delete')
        ->assertRedirect(route('runs.index'));

    expect(Run::whereKey($run->id)->exists())->toBeFalse();
});

it('prevents deleting runs that belong to another business', function () {
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

it('prevents deleting in-progress runs', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->inProgress()->create([
        'created_by_user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->call('delete')
        ->assertForbidden();
});

it('prevents updating runs that are already in progress', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->inProgress()->create([
        'created_by_user_id' => $user->id,
        'name' => 'Locked Run',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->set('name', 'Attempted Update')
        ->call('save')
        ->assertForbidden();
});

it('prevents viewing runs that belong to another business', function () {
    $business = Business::factory()->create();
    $otherBusiness = Business::factory()->create();

    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
    ]);

    $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

    Livewire::actingAs($otherUser)
        ->test(\App\Livewire\Runs\Show::class, ['run' => $run])
        ->assertForbidden();
});

it('prevents deleting runs that belong to another business from the index', function () {
    $business = Business::factory()->create();
    $otherBusiness = Business::factory()->create();

    $owner = User::factory()->create(['business_id' => $business->id]);
    $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

    $run = Run::factory()->for($business)->create(['created_by_user_id' => $owner->id]);

    Livewire::actingAs($otherUser)
        ->test(Index::class)
        ->call('delete', $run)
        ->assertForbidden();

    expect(Run::whereKey($run->id)->exists())->toBeTrue();
});

it('prevents deleting in-progress runs from the index', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->inProgress()->create([
        'created_by_user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('delete', $run)
        ->assertForbidden();

    expect(Run::whereKey($run->id)->exists())->toBeTrue();
});
