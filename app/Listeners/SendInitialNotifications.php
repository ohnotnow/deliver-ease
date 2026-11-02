<?php

namespace App\Listeners;

use App\Events\RunStarted;
use App\Mail\FirstInLineMail;
use App\Mail\PlaceOnListMail;
use Illuminate\Support\Facades\Mail;

class SendInitialNotifications
{
    public function handle(RunStarted $event): void
    {
        $firstStop = $event->run->deliveries->first();

        foreach ($event->run->deliveries as $delivery) {
            $delivery->markAsNotified();

            if ($delivery->is($firstStop)) {
                Mail::to($delivery->email)->queue(new FirstInLineMail($delivery));
                $delivery->recordToldTheyAreNext();

                continue;
            }

            Mail::to($delivery->email)->queue(new PlaceOnListMail($delivery));
        }
    }
}
