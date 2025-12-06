<?php

namespace App\Livewire;

use App\Enums\RunStatus;
use App\Models\Run;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    #[Computed]
    public function activeRunsCount(): int
    {
        return Run::where('business_id', Auth::user()->business_id)
            ->where('status', RunStatus::InProgress)
            ->count();
    }

    #[Computed]
    public function completedTodayCount(): int
    {
        return Run::where('business_id', Auth::user()->business_id)
            ->where('status', RunStatus::Completed)
            ->whereDate('completed_at', today())
            ->count();
    }

    #[Computed]
    public function pendingRunsCount(): int
    {
        return Run::where('business_id', Auth::user()->business_id)
            ->where('status', RunStatus::Pending)
            ->count();
    }

    #[Computed]
    public function recentRuns()
    {
        return Run::where('business_id', Auth::user()->business_id)
            ->with('deliveries')
            ->latest()
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
