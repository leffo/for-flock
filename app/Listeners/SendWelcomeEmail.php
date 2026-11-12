<?php

namespace App\Listeners;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        if (! ($event->user instanceof User)) {
            return;
        }

        try {
            Mail::to($event->user)->send(new WelcomeEmail($event->user));
            $event->user->forceFill(['welcome_email_sent' => true])->save();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
