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

    public function sortDelivery(int $deliveryId, int $newPosition): void
    {
        $deliveries = $this->run->deliveries()->orderBy('position')->get();
        $movedDelivery = $deliveries->firstWhere('id', $deliveryId);

        if (! $movedDelivery) {
            return;
        }

        // Remove from current position and insert at new position
        $deliveries = $deliveries->reject(fn ($d) => $d->id === $deliveryId)->values();
        $deliveries->splice($newPosition, 0, [$movedDelivery]);

        // Update all positions (1-based in database)
        foreach ($deliveries as $index => $delivery) {
            $delivery->update(['position' => $index + 1]);
        }

        unset($this->deliveries);
    }

    public function render()
    {
        return view('livewire.driver.active-run');
    }
}
