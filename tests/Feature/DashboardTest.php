<?php

use App\Enums\RunStatus;
use App\Livewire\Dashboard;
use App\Models\Business;
use App\Models\Run;
use App\Models\User;
use Livewire\Livewire;

it('shows counts scoped to the authenticated business', function () {
    $business = Business::factory()->create();
    $otherBusiness = Business::factory()->create();

    $user = User::factory()->create(['business_id' => $business->id]);

    Run::factory()->for($business)->inProgress()->create();
    Run::factory()->for($business)->completed()->create(['completed_at' => now()]);
    Run::factory()->for($business)->create(['status' => RunStatus::Pending]);

    // Other business data should not count
    Run::factory()->for($otherBusiness)->inProgress()->create();
    Run::factory()->for($otherBusiness)->completed()->create();

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSet('activeRunsCount', 1)
        ->assertSet('completedTodayCount', 1)
        ->assertSet('pendingRunsCount', 1)
        ->assertCount('recentRuns', 3);
});
