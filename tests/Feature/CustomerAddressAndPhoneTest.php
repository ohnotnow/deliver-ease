<?php

use App\Livewire\Driver\ActiveRun;
use App\Livewire\Runs\Create;
use App\Livewire\Runs\Edit;
use App\Livewire\Runs\Import;
use App\Livewire\Runs\Show;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\Run;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Ohffs\SimpleSpout\ExcelSheet;

test("adding a customer's address and phone number", function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('name', 'Morning Run')
        ->set('pin', '1234')
        ->set('deliveries', [
            ['email' => 'jo@example.com', 'name' => 'Jo Bloggs', 'address' => '12 High Street, Paisley', 'phone' => '07700 900123'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $delivery = Run::sole()->deliveries()->sole();

    expect($delivery->email)->toBe('jo@example.com')
        ->and($delivery->name)->toBe('Jo Bloggs')
        ->and($delivery->address)->toBe('12 High Street, Paisley')
        ->and($delivery->phone)->toBe('07700 900123');
});

test('address and phone number are optional', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('name', 'Morning Run')
        ->set('pin', '1234')
        ->set('deliveries', [
            ['email' => 'jo@example.com', 'name' => '', 'address' => '', 'phone' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $delivery = Run::sole()->deliveries()->sole();

    expect($delivery->email)->toBe('jo@example.com')
        ->and($delivery->address)->toBeNull()
        ->and($delivery->phone)->toBeNull();
});

test("changing a customer's address and phone number", function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    Delivery::factory()->for($run)->create([
        'position' => 1,
        'email' => 'jo@example.com',
        'name' => 'Jo Bloggs',
        'address' => '12 High Street, Paisley',
        'phone' => '07700 900123',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['run' => $run])
        ->assertSet('deliveries.0.address', '12 High Street, Paisley')
        ->assertSet('deliveries.0.phone', '07700 900123')
        ->set('deliveries.0.address', '3 Low Road, Renfrew')
        ->set('deliveries.0.phone', '0141 496 0000')
        ->call('save')
        ->assertHasNoErrors();

    $delivery = $run->deliveries()->sole();

    expect($delivery->email)->toBe('jo@example.com')
        ->and($delivery->name)->toBe('Jo Bloggs')
        ->and($delivery->address)->toBe('3 Low Road, Renfrew')
        ->and($delivery->phone)->toBe('0141 496 0000');
});

test('the business sees addresses and phone numbers while tracking a run', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    Delivery::factory()->for($run)->create([
        'position' => 1,
        'address' => '12 High Street, Paisley',
        'phone' => '07700 900123',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['run' => $run])
        ->assertOk()
        ->assertSee('12 High Street, Paisley')
        ->assertSee('07700 900123');
});

test("the driver sees each customer's address and phone number", function () {
    $run = Run::factory()->create();
    Delivery::factory()->for($run)->create([
        'position' => 1,
        'address' => '12 High Street, Paisley',
        'phone' => '07700 900123',
    ]);
    session(['driver_run_'.$run->uuid => true]);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->assertOk()
        ->assertSee('12 High Street, Paisley')
        ->assertSee('07700 900123');
});

test('the driver can ring a customer', function () {
    $run = Run::factory()->create();
    Delivery::factory()->for($run)->create([
        'position' => 1,
        'phone' => '+44 7700 900123',
    ]);
    session(['driver_run_'.$run->uuid => true]);

    Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->assertOk()
        ->assertSeeHtml('href="tel:+447700900123"')
        ->assertSee('+44 7700 900123');
});

test('a customer with no address or phone number', function () {
    $run = Run::factory()->create();
    Delivery::factory()->for($run)->withoutName()->create([
        'position' => 1,
        'email' => 'jo@example.com',
    ]);
    session(['driver_run_'.$run->uuid => true]);

    $driverScreen = Livewire::test(ActiveRun::class, ['uuid' => $run->uuid])
        ->assertOk()
        ->assertSee('jo@example.com')
        ->assertDontSeeHtml('href="tel:');

    expect($driverScreen->html())->not->toMatch('/<p[^>]*data-flux-text[^>]*>\s*<\/p>/');
});

test('importing customers with addresses and phone numbers', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    $spreadsheet = (new ExcelSheet)->generate([
        ['email', 'name', 'address', 'phone'],
        ['jo@example.com', 'Jo Bloggs', '12 High Street, Paisley', '07700 900123'],
        ['sam@example.com', 'Sam Smith', '3 Low Road, Renfrew', '0141 496 0000'],
    ]);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', UploadedFile::fake()->createWithContent('run.xlsx', file_get_contents($spreadsheet)))
        ->call('import')
        ->assertHasNoErrors();

    $jo = $run->deliveries()->where('email', 'jo@example.com')->sole();
    $sam = $run->deliveries()->where('email', 'sam@example.com')->sole();

    expect($run->deliveries()->count())->toBe(2)
        ->and($jo->name)->toBe('Jo Bloggs')
        ->and($jo->address)->toBe('12 High Street, Paisley')
        ->and($jo->phone)->toBe('07700 900123')
        ->and($sam->name)->toBe('Sam Smith')
        ->and($sam->address)->toBe('3 Low Road, Renfrew')
        ->and($sam->phone)->toBe('0141 496 0000');
});

test('older two-column spreadsheets still import', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    $spreadsheet = (new ExcelSheet)->generate([
        ['email', 'name'],
        ['jo@example.com', 'Jo Bloggs'],
    ]);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', UploadedFile::fake()->createWithContent('run.xlsx', file_get_contents($spreadsheet)))
        ->call('import')
        ->assertHasNoErrors();

    $jo = $run->deliveries()->sole();

    expect($jo->email)->toBe('jo@example.com')
        ->and($jo->name)->toBe('Jo Bloggs')
        ->and($jo->address)->toBeNull()
        ->and($jo->phone)->toBeNull();
});

test('a blank cell on re-import leaves existing details alone', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    Delivery::factory()->for($run)->create([
        'position' => 1,
        'email' => 'jo@example.com',
        'name' => 'Jo Bloggs',
        'address' => '12 High Street, Paisley',
        'phone' => '07700 900123',
    ]);
    $spreadsheet = (new ExcelSheet)->generate([
        ['email', 'name', 'address', 'phone'],
        ['jo@example.com', 'Jo Bloggs', '', ''],
    ]);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', UploadedFile::fake()->createWithContent('run.xlsx', file_get_contents($spreadsheet)))
        ->call('import')
        ->assertHasNoErrors();

    $jo = $run->deliveries()->sole();

    expect($jo->address)->toBe('12 High Street, Paisley')
        ->and($jo->phone)->toBe('07700 900123');
});

test('exporting a run includes addresses and phone numbers', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    Delivery::factory()->for($run)->create([
        'position' => 1,
        'email' => 'jo@example.com',
        'name' => 'Jo Bloggs',
        'address' => '12 High Street, Paisley',
        'phone' => '07700 900123',
    ]);
    Delivery::factory()->for($run)->create([
        'position' => 2,
        'email' => 'sam@example.com',
        'name' => 'Sam Smith',
        'address' => '3 Low Road, Renfrew',
        'phone' => '0141 496 0000',
    ]);

    $response = $this->actingAs($user)->get(route('runs.export', $run))->assertOk();

    $rows = (new ExcelSheet)->importFirst($response->baseResponse->getFile()->getPathname());

    expect(array_slice($rows[0], 0, 4))->toBe(['Name', 'Email', 'Address', 'Phone'])
        ->and(array_slice($rows[1], 0, 4))->toBe(['Jo Bloggs', 'jo@example.com', '12 High Street, Paisley', '07700 900123'])
        ->and(array_slice($rows[2], 0, 4))->toBe(['Sam Smith', 'sam@example.com', '3 Low Road, Renfrew', '0141 496 0000']);
});

test('a blank name on re-import leaves the existing name alone', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    Delivery::factory()->for($run)->create([
        'position' => 1,
        'email' => 'jo@example.com',
        'name' => 'Jo Bloggs',
    ]);
    $spreadsheet = (new ExcelSheet)->generate([
        ['email', 'name', 'address', 'phone'],
        ['jo@example.com', '', '12 High Street, Paisley', ''],
    ]);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', UploadedFile::fake()->createWithContent('run.xlsx', file_get_contents($spreadsheet)))
        ->call('import')
        ->assertHasNoErrors();

    $jo = $run->deliveries()->sole();

    expect($jo->name)->toBe('Jo Bloggs')
        ->and($jo->address)->toBe('12 High Street, Paisley');
});

test('a missing address or phone number is recorded the same way however the customer was added', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('name', 'Morning Run')
        ->set('pin', '1234')
        ->set('deliveries', [
            ['email' => 'jo@example.com', 'name' => 'Jo Bloggs', 'address' => '', 'phone' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $run = Run::sole();
    $spreadsheet = (new ExcelSheet)->generate([
        ['email', 'name', 'address', 'phone'],
        ['sam@example.com', 'Sam Smith', '', ''],
    ]);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', UploadedFile::fake()->createWithContent('run.xlsx', file_get_contents($spreadsheet)))
        ->call('import')
        ->assertHasNoErrors();

    $addedByHand = $run->deliveries()->where('email', 'jo@example.com')->sole();
    $imported = $run->deliveries()->where('email', 'sam@example.com')->sole();

    expect($addedByHand->address)->toBeNull()
        ->and($addedByHand->phone)->toBeNull()
        ->and($imported->address)->toBeNull()
        ->and($imported->phone)->toBeNull();
});

test('a blank name on import is recorded the same way as on the form', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);
    $run = Run::factory()->for($business)->create(['created_by_user_id' => $user->id]);
    $spreadsheet = (new ExcelSheet)->generate([
        ['email', 'name', 'address', 'phone'],
        ['jo@example.com', '', '', ''],
    ]);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', UploadedFile::fake()->createWithContent('run.xlsx', file_get_contents($spreadsheet)))
        ->call('import')
        ->assertHasNoErrors();

    $jo = $run->deliveries()->sole();

    expect($jo->email)->toBe('jo@example.com')
        ->and($jo->name)->toBeNull();
});
