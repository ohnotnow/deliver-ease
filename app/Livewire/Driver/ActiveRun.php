<?php

namespace App\Livewire\Driver;

use App\Models\Delivery;
use App\Models\Run;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ActiveRun extends Component
{
    public Run $run;

    public ?int $outOfOrderDeliveryId = null;

    public function mount(string $uuid): void
    {
        $this->run = Run::where('uuid', $uuid)->firstOrFail();

        // Check if driver has entered the PIN
        if (! $this->hasValidDriverSession()) {
            $this->redirect(route('driver.access', ['uuid' => $uuid]), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.driver.active-run');
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
    public function nextStop(): ?Delivery
    {
        return $this->run->nextStop();
    }

    #[Computed]
    public function outOfOrderDelivery(): ?Delivery
    {
        return $this->run->deliveries()->find($this->outOfOrderDeliveryId);
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
        $this->ensureDriverSession();

        if ($this->run->isPending()) {
            $this->run->start();
        }
    }

    public function completeDelivery(int $deliveryId): void
    {
        $this->ensureDriverSession();

        $delivery = $this->run->deliveries()->findOrFail($deliveryId);

        if (! $delivery->canBeCompleted()) {
            return;
        }

        if (! $delivery->isNextStop()) {
            $this->outOfOrderDeliveryId = $delivery->id;
            Flux::modal('confirm-out-of-order')->show();

            return;
        }

        $delivery->markAsCompleted();
    }

    public function confirmOutOfOrder(): void
    {
        $this->ensureDriverSession();

        if ($this->outOfOrderDeliveryId === null) {
            return;
        }

        $delivery = $this->run->deliveries()->findOrFail($this->outOfOrderDeliveryId);

        if ($delivery->canBeCompleted()) {
            $delivery->markAsCompleted();
        }

        $this->reset('outOfOrderDeliveryId');
        Flux::modal('confirm-out-of-order')->close();
    }

    private function hasValidDriverSession(): bool
    {
        return session()->has('driver_run_'.$this->run->uuid);
    }

    private function ensureDriverSession(): void
    {
        if (! $this->hasValidDriverSession()) {
            abort(403);
        }
    }
}
