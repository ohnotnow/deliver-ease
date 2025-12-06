<?php

namespace App\Livewire\Runs;

use App\Models\Run;
use Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;

    public Run $run;

    public string $name = '';

    public string $pin = '';

    /** @var array<int, array{id: int|null, name: string, email: string}> */
    public array $deliveries = [];

    public function mount(Run $run): void
    {
        $this->authorize('view', $run);

        $this->run = $run;
        $this->name = $run->name;
        $this->pin = $run->pin;

        $this->deliveries = $run->deliveries()
            ->orderBy('position')
            ->get()
            ->map(fn ($delivery) => [
                'id' => $delivery->id,
                'name' => $delivery->name ?? '',
                'email' => $delivery->email,
            ])
            ->toArray();

        if (empty($this->deliveries)) {
            $this->deliveries = [['id' => null, 'name' => '', 'email' => '']];
        }
    }

    public function addDelivery(): void
    {
        $this->deliveries[] = ['id' => null, 'name' => '', 'email' => ''];
    }

    public function removeDelivery(int $index): void
    {
        if (count($this->deliveries) > 1) {
            unset($this->deliveries[$index]);
            $this->deliveries = array_values($this->deliveries);
            Flux::modal('confirm-delete-delivery-'.$index)->close();
        }
    }

    public function sortDelivery(int $itemIndex, int $newPosition): void
    {
        $item = $this->deliveries[$itemIndex];
        unset($this->deliveries[$itemIndex]);
        $this->deliveries = array_values($this->deliveries);
        array_splice($this->deliveries, $newPosition, 0, [$item]);
    }

    public function save(): void
    {
        $this->authorize('update', $this->run);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'string', 'size:4'],
            'deliveries' => ['required', 'array', 'min:1'],
            'deliveries.*.email' => ['required', 'email'],
            'deliveries.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->run->update([
            'name' => $this->name,
            'pin' => $this->pin,
        ]);

        // Delete existing deliveries and recreate
        $this->run->deliveries()->delete();

        foreach ($this->deliveries as $position => $delivery) {
            $this->run->deliveries()->create([
                'email' => $delivery['email'],
                'name' => $delivery['name'] ?: null,
                'position' => $position + 1,
            ]);
        }

        Flux::toast('Run updated successfully');

        $this->redirect(route('runs.show', $this->run), navigate: true);
    }

    public function regeneratePin(): void
    {
        $this->pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->run);

        $this->run->delete();

        Flux::toast('Run deleted');

        $this->redirect(route('runs.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.runs.edit');
    }
}
