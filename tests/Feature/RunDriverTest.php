<?php

use App\Enums\DeliveryStatus;
use App\Enums\RunStatus;
use App\Livewire\Driver\ActiveRun;
use App\Livewire\Runs\Show;
use App\Mail\FirstInLineMail;
use App\Mail\PlaceOnListMail;
use App\Mail\TwoStopsAwayMail;
use App\Mail\YouAreNextMail;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\Run;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Ohffs\SimpleSpout\ExcelSheet;

/**
 * A run whose customers are emailed at their lower-cased name, e.g. ann@example.com.
 *
 * @param  array<int, string>  $names
 */
function runWithCustomers(array $names): Run
{
    $run = Run::factory()->create();

    foreach ($names as $index => $name) {
        Delivery::factory()->for($run)->create([
            'position' => $index + 1,
            'name' => $name,
            'email' => strtolower($name).'@example.com',
        ]);
    }

    session(['driver_run_'.$run->uuid => true]);

    return $run;
}

function customer(Run $run, string $name): Delivery
{
    return $run->deliveries()->where('name', $name)->firstOrFail();
}

it('Starting a run tells every customer their place', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat', 'Dan']);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun');

    Mail::assertQueued(FirstInLineMail::class, fn ($mail) => $mail->hasTo('ann@example.com') && $mail->hasSubject("You're first in line!"));
    Mail::assertQueued(PlaceOnListMail::class, fn ($mail) => $mail->hasTo('bob@example.com') && $mail->hasSubject("You're 2nd on the list"));
    Mail::assertQueued(PlaceOnListMail::class, fn ($mail) => $mail->hasTo('cat@example.com') && $mail->hasSubject("You're 3rd on the list"));
    Mail::assertQueued(PlaceOnListMail::class, fn ($mail) => $mail->hasTo('dan@example.com') && $mail->hasSubject("You're 4th on the list"));
    Mail::assertQueuedCount(4);
});

it('Places on a long list read naturally', function () {
    Mail::fake();
    $run = runWithCustomers(array_map(fn (int $place) => "Customer{$place}", range(1, 111)));

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');

    $expectedPlaces = [11 => '11th', 12 => '12th', 13 => '13th', 21 => '21st', 22 => '22nd', 23 => '23rd', 101 => '101st', 111 => '111th'];
    foreach ($expectedPlaces as $place => $ordinal) {
        Mail::assertQueued(PlaceOnListMail::class, fn ($mail) => $mail->hasTo("customer{$place}@example.com") && $mail->hasSubject("You're {$ordinal} on the list"));
    }
});

it('Delivering to the first stop moves everyone along', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat', 'Dan', 'Eve']);
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');
    Mail::fake();

    $driver->call('completeDelivery', customer($run, 'Ann')->id);

    Mail::assertQueued(YouAreNextMail::class, fn ($mail) => $mail->hasTo('bob@example.com'));
    Mail::assertQueued(TwoStopsAwayMail::class, fn ($mail) => $mail->hasTo('dan@example.com'));
    Mail::assertQueuedCount(2);
});

it('Delivering to a later stop moves everyone along', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat', 'Dan', 'Eve']);
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun')
        ->call('completeDelivery', customer($run, 'Ann')->id);
    Mail::fake();

    $driver->call('completeDelivery', customer($run, 'Bob')->id);

    Mail::assertQueued(YouAreNextMail::class, fn ($mail) => $mail->hasTo('cat@example.com'));
    Mail::assertQueued(TwoStopsAwayMail::class, fn ($mail) => $mail->hasTo('eve@example.com'));
    Mail::assertQueuedCount(2);
});

it('The run finishes when every delivery is done', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob']);
    $businessOwner = User::factory()->create(['business_id' => $run->business_id]);
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');

    $driver->call('completeDelivery', customer($run, 'Ann')->id)
        ->call('completeDelivery', customer($run, 'Bob')->id);

    Livewire::actingAs($businessOwner)
        ->test(Show::class, ['run' => $run->fresh()])
        ->assertSee('Run completed');
});

it('The driver can mark any stop still to do as done', function (string $name, bool $asksFirst) {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $stop = customer($run, $name);
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');

    $driver->assertSeeHtml("completeDelivery({$stop->id})")
        ->call('completeDelivery', $stop->id);

    $asksFirst
        ? $driver->assertDispatched('modal-show', name: 'confirm-out-of-order')->call('confirmOutOfOrder')
        : $driver->assertNotDispatched('modal-show');
    expect($stop->fresh()->isCompleted())->toBeTrue();
})->with([
    'Ann, the next stop, in one tap' => ['Ann', false],
    'Bob, after confirming' => ['Bob', true],
    'Cat, after confirming' => ['Cat', true],
]);

it("Dropping off out of order keeps everyone's timing right", function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat', 'Dan', 'Eve']);
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');
    Mail::fake();

    $driver->call('completeDelivery', customer($run, 'Cat')->id)
        ->call('confirmOutOfOrder');

    Mail::assertQueued(TwoStopsAwayMail::class, fn ($mail) => $mail->hasTo('dan@example.com'));
    Mail::assertQueuedCount(1);
    expect($run->fresh()->nextStop()->is(customer($run, 'Ann')))->toBeTrue();
});

it('The driver sees their next stop', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');

    $driver->call('completeDelivery', customer($run, 'Ann')->id);

    $driver->assertSeeInOrder(['Bob', 'Next stop', 'Cat'])
        ->assertDontSee('Customer notified');
    expect(substr_count($driver->html(), 'Next stop'))->toBe(1);
});

it('Only the next stop is highlighted', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $businessOwner = User::factory()->create(['business_id' => $run->business_id]);
    $bob = customer($run, 'Bob');
    $cat = customer($run, 'Cat');
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');

    $driver->call('completeDelivery', customer($run, 'Ann')->id);
    $businessPage = Livewire::actingAs($businessOwner)->test(Show::class, ['run' => $run->fresh()]);

    foreach ([$driver, $businessPage] as $screen) {
        $screen->assertSeeHtmlInOrder(["delivery-{$bob->id}", 'bg-blue-50 dark:bg-blue-900', "delivery-{$cat->id}"]);
        expect(substr_count($screen->html(), 'bg-blue-50 dark:bg-blue-900'))->toBe(1);
    }
});

it('Before starting, the driver is told what starting will do', function () {
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->assertSee('Every customer will be emailed their place on the list.')
        ->assertDontSee('The first two customers will be notified.');
});

it('The business sees which stop the driver is heading to', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $businessOwner = User::factory()->create(['business_id' => $run->business_id]);
    $bob = customer($run, 'Bob');
    $cat = customer($run, 'Cat');
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');

    $driver->call('completeDelivery', customer($run, 'Ann')->id);

    $businessPage = Livewire::actingAs($businessOwner)->test(Show::class, ['run' => $run->fresh()]);
    $businessPage->assertSeeInOrder(['Driver is heading to', 'Bob', 'Deliveries'])
        ->assertSeeHtmlInOrder(["delivery-{$bob->id}", 'Next stop', "delivery-{$cat->id}"])
        ->assertDontSee('Notified');
    expect(substr_count($businessPage->html(), 'Next stop'))->toBe(1);
});

it('The business sees the other stops still to do as To do', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $businessOwner = User::factory()->create(['business_id' => $run->business_id]);
    $cat = customer($run, 'Cat');
    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun')
        ->call('completeDelivery', customer($run, 'Ann')->id);

    $businessPage = Livewire::actingAs($businessOwner)->test(Show::class, ['run' => $run->fresh()]);

    $businessPage->assertSeeHtmlInOrder(["delivery-{$cat->id}", 'To do'])
        ->assertDontSee('Pending');
});

it("The export shows when each customer was told they're next", function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $businessOwner = User::factory()->create(['business_id' => $run->business_id]);

    $this->travelTo(now()->setTime(9, 0));
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');
    $this->travelTo(now()->setTime(9, 20));
    $driver->call('completeDelivery', customer($run, 'Ann')->id);

    $response = $this->actingAs($businessOwner)->get(route('runs.export', $run))->assertOk();
    $rows = (new ExcelSheet)->importFirst($response->baseResponse->getFile()->getPathname());

    $today = now()->format('Y-m-d');
    expect(array_slice($rows[1], 4, 2))->toBe(['completed', "{$today} 09:00:00"])
        ->and(array_slice($rows[2], 4, 2))->toBe(['notified', "{$today} 09:20:00"])
        ->and($rows[3][4])->toBe('notified')
        ->and($rows[3][5] ?? null)->toBeEmpty();
});

it('The driver can complete any stop not yet delivered once the run has started', function () {
    Mail::fake();
    $run = Run::factory()->inProgress()->create();
    $notYetEmailed = Delivery::factory()->for($run)->create(['position' => 1]);
    session(['driver_run_'.$run->uuid => true]);

    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->assertSeeHtml("completeDelivery({$notYetEmailed->id})");

    $driver->call('completeDelivery', $notYetEmailed->id);

    expect($notYetEmailed->fresh()->isCompleted())->toBeTrue();
});

it('The driver is asked to confirm before completing a stop out of order', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $cat = customer($run, 'Cat');
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');

    $driver->call('completeDelivery', $cat->id);

    $driver->assertDispatched('modal-show', name: 'confirm-out-of-order')
        ->assertSee('Mark Cat as delivered?')
        ->assertSee('Ann is still next.');
    expect($cat->fresh()->isCompleted())->toBeFalse();

    $driver->call('confirmOutOfOrder');

    expect($cat->fresh()->isCompleted())->toBeTrue();
    $driver->assertDontSeeHtml('wire:confirm');
});

it('The new next stop completes without asking, without a reload', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $bob = customer($run, 'Bob');
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun')
        ->call('completeDelivery', customer($run, 'Ann')->id);

    $driver->call('completeDelivery', $bob->id);

    expect($bob->fresh()->isCompleted())->toBeTrue();
    $driver->assertNotDispatched('modal-show');
});

it('Cancelling the out-of-order confirmation leaves the stop undelivered', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $cat = customer($run, 'Cat');
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])->call('startRun');
    Mail::fake();

    $driver->call('completeDelivery', $cat->id);

    expect($cat->fresh()->isCompleted())->toBeFalse();
    Mail::assertNothingQueued();
});

it('refuses to confirm an out-of-order stop without a driver session', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat']);
    $cat = customer($run, 'Cat');
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun')
        ->call('completeDelivery', $cat->id);
    session()->forget('driver_run_'.$run->uuid);

    $driver->call('confirmOutOfOrder')->assertForbidden();

    expect($cat->fresh()->isCompleted())->toBeFalse();
});

it('A second quick tap on Mark as delivered does nothing', function () {
    Mail::fake();
    $run = runWithCustomers(['Ann', 'Bob', 'Cat', 'Dan']);
    $cat = customer($run, 'Cat');
    $driver = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->call('startRun')
        ->call('completeDelivery', $cat->id)
        ->call('confirmOutOfOrder');
    Mail::fake();

    $driver->call('confirmOutOfOrder')->assertOk();

    expect($cat->fresh()->isCompleted())->toBeTrue()
        ->and($run->deliveries()->completed()->count())->toBe(1);
    Mail::assertNothingQueued();
});

it('returns a 404 for unknown driver links', function () {
    $this->get('/driver/run/unknown')->assertNotFound();
    $this->get('/driver/run/unknown/active')->assertNotFound();
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

it('does not complete a delivery before the run starts', function () {
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

    Mail::assertQueued(FirstInLineMail::class, 1);
    Mail::assertQueued(PlaceOnListMail::class, 1);
    Mail::assertQueuedCount(2);
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
    $component->call('completeDelivery', $second->id)
        ->call('confirmOutOfOrder');

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
