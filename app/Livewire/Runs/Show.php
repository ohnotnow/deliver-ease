<?php

namespace App\Livewire\Runs;

use App\Models\Run;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Run $run;

    public function mount(Run $run): void
    {
        $this->authorize('view', $run);
        $this->run = $run;
    }

    #[Computed]
    public function deliveries()
    {
        return $this->run->deliveries()->orderBy('position')->get();
    }

    #[Computed]
    public function progressPercentage(): int
    {
        $total = $this->deliveries->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->run->completedDeliveriesCount() / $total) * 100);
    }

    public function render()
    {
        return view('livewire.runs.show');
    }
}
