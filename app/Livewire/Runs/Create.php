<?php

namespace App\Livewire\Runs;

use App\Models\Run;
use Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $pin = '';

    /** @var array<int, array{name: string, email: string}> */
    public array $deliveries = [
        ['name' => '', 'email' => ''],
    ];

    public function mount(): void
    {
        $this->authorize('create', Run::class);

        $this->pin = $this->generatePin();
    }

    public function addDelivery(): void
    {
        $this->deliveries[] = ['name' => '', 'email' => ''];
    }

    public function removeDelivery(int $index): void
    {
        if (count($this->deliveries) > 1) {
            unset($this->deliveries[$index]);
            $this->deliveries = array_values($this->deliveries);
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
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'digits:4'],
            'deliveries' => ['required', 'array', 'min:1'],
            'deliveries.*.email' => ['required', 'email'],
            'deliveries.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        $run = Run::create([
            'business_id' => Auth::user()->business_id,
            'created_by_user_id' => Auth::id(),
            'name' => $this->name,
            'pin' => $this->pin,
        ]);

        foreach ($this->deliveries as $position => $delivery) {
            $run->deliveries()->create([
                'email' => $delivery['email'],
                'name' => $delivery['name'] ?: null,
                'position' => $position + 1,
            ]);
        }

        Flux::toast('Run created successfully');

        $this->redirect(route('runs.show', $run), navigate: true);
    }

    public function generatePin(): string
    {
        return Run::generatePin();
    }

    public function regeneratePin(): void
    {
        $this->pin = $this->generatePin();
    }

    public function render()
    {
        return view('livewire.runs.create');
    }
}
