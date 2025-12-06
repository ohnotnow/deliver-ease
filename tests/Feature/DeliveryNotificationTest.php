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

    Mail::assertQueued(YouAreNextMail::class, fn ($mail) => $mail->hasTo($first->email));
    Mail::assertQueued(OneStopAwayMail::class, fn ($mail) => $mail->hasTo($second->email));
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

    expect($run->fresh()->status)->toBe(RunStatus::Completed);
});
