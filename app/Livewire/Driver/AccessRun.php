<?php

namespace App\Livewire\Driver;

use App\Models\Run;
use Livewire\Component;

class AccessRun extends Component
{
    public Run $run;

    public string $pin = '';

    public bool $invalidPin = false;

    public function mount(string $uuid): void
    {
        $this->run = Run::where('uuid', $uuid)->firstOrFail();
    }

    public function submit(): void
    {
        if ($this->pin === $this->run->pin) {
            session()->put('driver_run_'.$this->run->uuid, true);
            $this->redirect(route('driver.run', ['uuid' => $this->run->uuid]), navigate: true);
        } else {
            $this->invalidPin = true;
            $this->pin = '';
        }
    }

    public function updatedPin(): void
    {
        $this->invalidPin = false;
    }

    public function render()
    {
        return view('livewire.driver.access-run');
    }
}
