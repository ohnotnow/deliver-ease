<?php

namespace App\Listeners;

use App\Events\DeliveryCompleted;
use App\Mail\TwoStopsAwayMail;
use App\Mail\YouAreNextMail;
use Illuminate\Support\Facades\Mail;

class SendNextDeliveryNotifications
{
    public function handle(DeliveryCompleted $event): void
    {
        $run = $event->delivery->run;

        if ($run->hasNoStopsLeft()) {
            $run->markCompleted();

            return;
        }

        foreach ($run->stopsStillToDo() as $stopsAhead => $delivery) {
            if ($delivery->position < $event->delivery->position) {
                continue;
            }

            if ($stopsAhead === 0) {
                Mail::to($delivery->email)->queue(new YouAreNextMail($delivery));
                $delivery->recordToldTheyAreNext();
            }

            if ($stopsAhead === 2) {
                Mail::to($delivery->email)->queue(new TwoStopsAwayMail($delivery));
            }
        }
    }
}
