<?php

namespace App\Livewire\Runs;

use App\Models\Run;
use Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Run::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function runs()
    {
        return Run::where('business_id', Auth::user()->business_id)
            ->with('deliveries')
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }

    public function delete(Run $run): void
    {
        $this->authorize('delete', $run);

        $run->delete();

        Flux::toast('Run deleted');
    }

    public function render()
    {
        return view('livewire.runs.index');
    }
}
