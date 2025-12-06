<?php

namespace App\Listeners;

use App\Enums\DeliveryStatus;
use App\Events\DeliveryCompleted;
use App\Mail\OneStopAwayMail;
use App\Mail\YouAreNextMail;
use Illuminate\Support\Facades\Mail;

class SendNextDeliveryNotifications
{
    public function handle(DeliveryCompleted $event): void
    {
        $delivery = $event->delivery;
        $run = $delivery->run;

        $nextDelivery = $run->nextPendingDelivery();

        if (! $nextDelivery) {
            $hasRemaining = $run->deliveries()
                ->whereIn('status', [DeliveryStatus::Pending, DeliveryStatus::Notified])
                ->exists();

            if (! $hasRemaining) {
                $run->markCompleted();
            }

            return;
        }

        Mail::to($nextDelivery->email)->queue(new YouAreNextMail($nextDelivery));
        $nextDelivery->markAsNotified();

        $oneAfter = $run->deliveries()
            ->where('position', '>', $nextDelivery->position)
            ->pending()
            ->first();

        if ($oneAfter) {
            Mail::to($oneAfter->email)->queue(new OneStopAwayMail($oneAfter));
            $oneAfter->markAsNotified();
        }
    }
}
