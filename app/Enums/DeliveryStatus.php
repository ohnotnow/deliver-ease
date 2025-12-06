<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Notified = 'notified';
    case Completed = 'completed';
}
