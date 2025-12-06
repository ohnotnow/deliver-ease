<?php

namespace App\Livewire\Driver;

use App\Models\Delivery;
use App\Models\Run;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ActiveRun extends Component
{
    public Run $run;

    public function mount(string $uuid): void
    {
        $this->run = Run::where('uuid', $uuid)->firstOrFail();

        // Check if driver has entered the PIN
        if (! session()->has('driver_run_'.$this->run->uuid)) {
            $this->redirect(route('driver.access', ['uuid' => $uuid]), navigate: true);
        }
    }

    #[Computed]
    public function deliveries()
    {
        return $this->run->deliveries() // Call the relationship method
            ->get()                     // Get the collection
            ->sortBy(fn ($delivery) => $delivery->isCompleted() ? 1 : 0)
            ->values();
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

    public function startRun(): void
    {
        if ($this->run->isPending()) {
            $this->run->start();
        }
    }

    public function completeDelivery(int $deliveryId): void
    {
        $delivery = Delivery::where('id', $deliveryId)
            ->where('run_id', $this->run->id)
            ->firstOrFail();

        if ($delivery->isNotified()) {
            $delivery->markAsCompleted();
        }
    }

    public function render()
    {
        return view('livewire.driver.active-run');
    }
}
