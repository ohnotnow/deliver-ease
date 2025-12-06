<?php

namespace App\Listeners;

use App\Events\RunStarted;
use App\Mail\OneStopAwayMail;
use App\Mail\YouAreNextMail;
use Illuminate\Support\Facades\Mail;

class SendInitialNotifications
{
    public function handle(RunStarted $event): void
    {
        $run = $event->run;

        $firstDelivery = $run->deliveries()->where('position', 1)->first();
        $secondDelivery = $run->deliveries()->where('position', 2)->first();

        if ($firstDelivery) {
            Mail::to($firstDelivery->email)->queue(new YouAreNextMail($firstDelivery));
            $firstDelivery->markAsNotified();
        }

        if ($secondDelivery) {
            Mail::to($secondDelivery->email)->queue(new OneStopAwayMail($secondDelivery));
            $secondDelivery->markAsNotified();
        }
    }
}
