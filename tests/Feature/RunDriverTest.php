<?php

use App\Enums\DeliveryStatus;
use App\Enums\RunStatus;
use App\Livewire\Driver\ActiveRun;
use App\Mail\OneStopAwayMail;
use App\Mail\YouAreNextMail;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\Run;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('notifies the first two deliveries when a run starts', function () {
    Mail::fake();

    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
    ]);

    $first = Delivery::factory()->for($run)->create(['position' => 1, 'email' => 'first@example.com']);
    $second = Delivery::factory()->for($run)->create(['position' => 2, 'email' => 'second@example.com']);
    $third = Delivery::factory()->for($run)->create(['position' => 3, 'email' => 'third@example.com']);

    session(['driver_run_'.$run->uuid => true]);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun');

    $run->refresh();

    expect($run->status)->toBe(RunStatus::InProgress)
        ->and($first->fresh()->status)->toBe(DeliveryStatus::Notified)
        ->and($second->fresh()->status)->toBe(DeliveryStatus::Notified)
        ->and($third->fresh()->status)->toBe(DeliveryStatus::Pending);

    Mail::assertQueued(YouAreNextMail::class, 1);
    Mail::assertQueued(OneStopAwayMail::class, 1);
    Mail::assertQueued(YouAreNextMail::class, fn ($mail) => $mail->hasTo($first->email));
    Mail::assertQueued(OneStopAwayMail::class, fn ($mail) => $mail->hasTo($second->email));
});

it('returns a 404 for unknown driver links', function () {
    $this->get('/driver/run/unknown')->assertNotFound();
    $this->get('/driver/run/unknown/active')->assertNotFound();
});

it('cascades notifications as deliveries are completed and completes the run when pending stops are exhausted', function () {
    Mail::fake();

    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
    ]);

    $first = Delivery::factory()->for($run)->create(['position' => 1, 'email' => 'first@example.com']);
    $second = Delivery::factory()->for($run)->create(['position' => 2, 'email' => 'second@example.com']);
    $third = Delivery::factory()->for($run)->create(['position' => 3, 'email' => 'third@example.com']);
    $fourth = Delivery::factory()->for($run)->create(['position' => 4, 'email' => 'fourth@example.com']);
    $fifth = Delivery::factory()->for($run)->create(['position' => 5, 'email' => 'fifth@example.com']);

    session(['driver_run_'.$run->uuid => true]);

    $component = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid]);

    $component->call('startRun');

    $component->call('completeDelivery', $first->id);

    expect($first->fresh()->status)->toBe(DeliveryStatus::Completed)
        ->and($third->fresh()->status)->toBe(DeliveryStatus::Notified)
        ->and($fourth->fresh()->status)->toBe(DeliveryStatus::Notified)
        ->and($fifth->fresh()->status)->toBe(DeliveryStatus::Pending);

    Mail::assertQueued(YouAreNextMail::class, fn ($mail) => $mail->hasTo($third->email));
    Mail::assertQueued(OneStopAwayMail::class, fn ($mail) => $mail->hasTo($fourth->email));

    $component->call('completeDelivery', $second->id);

    expect($fifth->fresh()->status)->toBe(DeliveryStatus::Notified);

    Mail::assertQueued(YouAreNextMail::class, fn ($mail) => $mail->hasTo($fifth->email));

    $component->call('completeDelivery', $third->id);

    expect($run->fresh()->status)->toBe(RunStatus::InProgress)
        ->and($fourth->fresh()->status)->toBe(DeliveryStatus::Notified)
        ->and($fifth->fresh()->status)->toBe(DeliveryStatus::Notified);

    $component->call('completeDelivery', $fourth->id);

    expect($run->fresh()->status)->toBe(RunStatus::InProgress)
        ->and($fifth->fresh()->status)->toBe(DeliveryStatus::Notified);

    $component->call('completeDelivery', $fifth->id);

    expect($run->fresh()->status)->toBe(RunStatus::Completed)
        ->and($fifth->fresh()->status)->toBe(DeliveryStatus::Completed);

    Mail::assertQueued(YouAreNextMail::class, 3);
    Mail::assertQueued(OneStopAwayMail::class, 2);
});

it('only completes the run after all deliveries are completed', function () {
    Mail::fake();

    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
    ]);

    $first = Delivery::factory()->for($run)->create(['position' => 1]);
    $second = Delivery::factory()->for($run)->create(['position' => 2]);

    session(['driver_run_'.$run->uuid => true]);

    $component = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid]);

    $component->call('startRun');

    expect($run->fresh()->status)->toBe(RunStatus::InProgress)
        ->and($first->fresh()->status)->toBe(DeliveryStatus::Notified)
        ->and($second->fresh()->status)->toBe(DeliveryStatus::Notified);

    $component->call('completeDelivery', $first->id);

    expect($run->fresh()->status)->toBe(RunStatus::InProgress)
        ->and($second->fresh()->status)->toBe(DeliveryStatus::Notified);

    $component->call('completeDelivery', $second->id);

    expect($run->fresh()->status)->toBe(RunStatus::Completed)
        ->and($second->fresh()->status)->toBe(DeliveryStatus::Completed);

    Mail::assertQueued(YouAreNextMail::class, 1);
    Mail::assertQueued(OneStopAwayMail::class, 1);
});

it('does nothing if start is called on a non-pending run', function () {
    Mail::fake();

    $run = Run::factory()->inProgress()->create();

    session(['driver_run_'.$run->uuid => true]);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun');

    expect($run->fresh()->status)->toBe(RunStatus::InProgress);
    Mail::assertNothingQueued();
});

it('does not complete a delivery unless it is notified', function () {
    Mail::fake();

    $run = Run::factory()->create();
    $pending = Delivery::factory()->for($run)->create(['status' => DeliveryStatus::Pending]);

    session(['driver_run_'.$run->uuid => true]);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('completeDelivery', $pending->id);

    expect($pending->fresh()->status)->toBe(DeliveryStatus::Pending);
    Mail::assertNothingQueued();
});

it('returns not found when attempting to complete a delivery from another run', function () {
    Mail::fake();

    $run = Run::factory()->create();
    $otherRun = Run::factory()->create();

    $foreignDelivery = Delivery::factory()->for($otherRun)->create();

    session(['driver_run_'.$run->uuid => true]);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('completeDelivery', $foreignDelivery->id);
});

it('does not send duplicate notifications when start is called twice', function () {
    Mail::fake();

    $run = Run::factory()->create();
    $first = Delivery::factory()->for($run)->create(['position' => 1, 'email' => 'first@example.com']);
    $second = Delivery::factory()->for($run)->create(['position' => 2, 'email' => 'second@example.com']);

    session(['driver_run_'.$run->uuid => true]);

    $component = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid]);

    $component->call('startRun');
    $component->call('startRun');

    expect($run->fresh()->status)->toBe(RunStatus::InProgress)
        ->and($first->fresh()->status)->toBe(DeliveryStatus::Notified)
        ->and($second->fresh()->status)->toBe(DeliveryStatus::Notified);

    Mail::assertQueued(YouAreNextMail::class, 1);
    Mail::assertQueued(OneStopAwayMail::class, 1);
});

it('sorts completed deliveries to the bottom of the list', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create([
        'created_by_user_id' => $owner->id,
    ]);

    $first = Delivery::factory()->for($run)->create(['position' => 1, 'name' => 'First Stop']);
    $second = Delivery::factory()->for($run)->create(['position' => 2, 'name' => 'Second Stop']);
    $third = Delivery::factory()->for($run)->create(['position' => 3, 'name' => 'Third Stop']);

    session(['driver_run_'.$run->uuid => true]);

    $component = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid]);

    // Initial state: 1, 2, 3
    $component->assertSeeInOrder(['First Stop', 'Second Stop', 'Third Stop']);

    $component->call('startRun');

    // Complete the second one
    $component->call('completeDelivery', $second->id);

    // Expected: 1, 3, 2 (Completed at bottom)
    $component->assertSeeInOrder(['First Stop', 'Third Stop', 'Second Stop']);

    // Complete the first one
    $component->call('completeDelivery', $first->id);

    // Expected: 3 (Pending), 1 (Completed), 2 (Completed) -> Stable sort preserves 1 before 2
    $component->assertSeeInOrder(['Third Stop', 'First Stop', 'Second Stop']);
});

it('prevents actions without valid session', function () {
    $run = Run::factory()->create();
    $delivery = Delivery::factory()->for($run)->create();

    session(['driver_run_'.$run->uuid => true]);
    $component = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid]);
    
    // Clear session to simulate expiry or bypass
    session()->forget('driver_run_'.$run->uuid);
    
    // Try to perform action
    $component->call('startRun')
        ->assertForbidden(); // We expect 403 Forbidden
        
    expect($run->fresh()->status)->toBe(RunStatus::Pending);
});