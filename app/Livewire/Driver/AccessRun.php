<?php

namespace App\Livewire\Driver;

use App\Models\Run;
use Illuminate\Support\Facades\RateLimiter;
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
        $throttleKey = 'driver-access:'.$this->run->uuid.':'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('pin', "Too many attempts. Please try again in {$seconds} seconds.");

            return;
        }

        if ($this->pin === $this->run->pin) {
            RateLimiter::clear($throttleKey);
            session()->put('driver_run_'.$this->run->uuid, true);
            $this->redirect(route('driver.run', ['uuid' => $this->run->uuid]), navigate: true);
        } else {
            RateLimiter::hit($throttleKey);
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
