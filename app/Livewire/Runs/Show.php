<?php

namespace App\Livewire\Runs;

use App\Models\Run;
use Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Run $run;

    public ?string $generatedPin = null;

    public ?string $pinHint = null;

    public function mount(Run $run): void
    {
        $this->authorize('view', $run);
        $this->run = $run;
        $this->pinHint = $run->pin_hint;
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

    public function generateDriverPin(): void
    {
        $this->authorize('update', $this->run);

        $this->generatedPin = $this->run->regeneratePin();
        $this->pinHint = $this->run->pin_hint;

        Flux::toast('New PIN generated. Share it with your driver.');
    }

    public function render()
    {
        return view('livewire.runs.show');
    }
}
