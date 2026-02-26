<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateSessionOnLogin
{
    public function handle(Login $event): void
    {
        $event->user->update([
            'current_session_id' => session()->getId(),
        ]);
    }
}
