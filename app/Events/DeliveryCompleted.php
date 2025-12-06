<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Delivery $delivery) {}
}
