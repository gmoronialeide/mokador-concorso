<?php

namespace App\Observers;

use App\Enums\VerificationType;
use App\Models\Play;

class PlayObserver
{
    public function updating(Play $play): void
    {
        if (! $play->isDirty('status')) {
            return;
        }

        if ($play->isDirty('verification_type')) {
            return;
        }

        if (! auth('admin')->check()) {
            return;
        }

        $play->verification_type = VerificationType::Manual;
    }
}
