<?php

namespace App\Actions;

use App\Models\ListeningParty;

class DeactivateFinishedListeningParty
{
    public function handle(): void
    {
        ListeningParty::where('end_at', '<=', now())->update(['is_active' => false]);
    }
}
