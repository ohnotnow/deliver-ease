<?php

use App\Http\Controllers\RunExportController;
use App\Livewire\Runs\Import;
use App\Models\Business;
use App\Models\Run;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Ohffs\SimpleSpout\ExcelSheet;

it('can export a run', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $user->update(['business_id' => $business->id]);
    
    $run = Run::factory()->for($business)->create([
        'name' => 'Test Export Run'
    ]);
    
    // Add some deliveries
    $run->deliveries()->create(['email' => 'one@example.com', 'position' => 1]);
    $run->deliveries()->create(['email' => 'two@example.com', 'position' => 2]);

    $expectedFilename = Str::slug($run->name) . '-' . now()->format('d-m-Y') . '.xlsx';

    $this->actingAs($user)
        ->get(route('runs.export', $run))
        ->assertOk()
        ->assertDownload($expectedFilename);
});

it('can import deliveries from excel', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $user->update(['business_id' => $business->id]);
    
    $run = Run::factory()->for($business)->create();

    // Generate a valid excel file using the same library the app uses
    $data = [
        ['email', 'name'], // Header row (should be skipped)
        ['new@example.com', 'New Person'],
        ['UPPER@EXAMPLE.COM', 'Upper Case'],
        ['onlyemail@example.com', ''],
    ];
    
    $tempFilePath = (new ExcelSheet)->generate($data);
    $content = file_get_contents($tempFilePath);
    
    // Create a fake upload with the real excel content
    $file = UploadedFile::fake()->createWithContent('import.xlsx', $content);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors()
        ->assertRedirect(route('runs.edit', $run));

    // Verify DB state
    expect($run->deliveries)->toHaveCount(3);
    
    $new = $run->deliveries()->where('email', 'new@example.com')->first();
    expect($new)->not->toBeNull()
        ->and($new->name)->toBe('New Person');

    $upper = $run->deliveries()->where('email', 'upper@example.com')->first();
    expect($upper)->not->toBeNull()
        ->and($upper->name)->toBe('Upper Case'); // Email should be lowercased by app

    $onlyEmail = $run->deliveries()->where('email', 'onlyemail@example.com')->first();
    expect($onlyEmail)->not->toBeNull()
        ->and($onlyEmail->name)->toBe('');
});

it('updates existing deliveries during import', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $user->update(['business_id' => $business->id]);
    
    $run = Run::factory()->for($business)->create();
    
    // Create existing delivery
    $run->deliveries()->create([
        'email' => 'existing@example.com', 
        'name' => 'Old Name',
        'position' => 1
    ]);

    $data = [
        ['existing@example.com', 'New Name Updated'],
    ];
    
    $tempFilePath = (new ExcelSheet)->generate($data);
    $content = file_get_contents($tempFilePath);
    $file = UploadedFile::fake()->createWithContent('update.xlsx', $content);

    Livewire::actingAs($user)
        ->test(Import::class, ['run' => $run])
        ->set('file', $file)
        ->call('import');

    expect($run->deliveries)->toHaveCount(1);
    expect($run->deliveries->first()->name)->toBe('New Name Updated');
});