<?php

use App\Enums\RunStatus;
use App\Livewire\Dashboard;
use App\Models\Business;
use App\Models\Run;
use App\Models\User;
use Livewire\Livewire;

it('shows only the authenticated business runs and metrics', function () {
    $business = Business::factory()->create();
    $otherBusiness = Business::factory()->create();

    $user = User::factory()->create(['business_id' => $business->id]);

    $inProgress = Run::factory()->for($business)->inProgress()->create(['name' => 'Business In Progress']);
    $completed = Run::factory()->for($business)->completed()->create(['completed_at' => now(), 'name' => 'Business Completed']);
    $pending = Run::factory()->for($business)->create(['status' => RunStatus::Pending, 'name' => 'Business Pending']);

    // Other business data should not count
    $otherRun = Run::factory()->for($otherBusiness)->create(['name' => 'Other Business Run']);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSet('activeRunsCount', 1)
        ->assertSet('completedTodayCount', 1)
        ->assertSet('pendingRunsCount', 1)
        ->assertCount('recentRuns', 3)
        ->assertSee($inProgress->name)
        ->assertSee($completed->name)
        ->assertSee($pending->name)
        ->assertDontSee($otherRun->name);
});

it('prompts user to create business if missing', function () {
    $user = User::factory()->create(['business_id' => null]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Setup Required')
        ->assertSee('Create Business')
        ->set('businessName', 'My New Business')
        ->call('saveBusiness')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->business)->not->toBeNull();
    expect($user->business->name)->toBe('My New Business');
});
