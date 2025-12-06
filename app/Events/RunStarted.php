<?php

namespace App\Events;

use App\Models\Run;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RunStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Run $run) {}
}
