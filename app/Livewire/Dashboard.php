<?php

namespace App\Livewire;

use App\Enums\RunStatus;
use App\Models\Business;
use App\Models\Run;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public string $businessName = '';

    public function mount()
    {
        $this->businessName = Auth::user()->business?->name ?? '';
    }

    public function saveBusiness()
    {
        $this->validate([
            'businessName' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        if ($user->business) {
            $user->business->update(['name' => $this->businessName]);
            Flux::toast('Business updated successfully.');
        } else {
            $business = Business::create(['name' => $this->businessName]);
            $user->update(['business_id' => $business->id]);
            Flux::toast('Business created successfully.');
        }

        $this->modal('business-modal')->close();
    }

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
