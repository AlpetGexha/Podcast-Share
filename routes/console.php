<?php

use App\Models\ListeningParty;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// if the party has ended deactivate that party
Schedule::call(function () {
    ListeningParty::where('end_at', '<', now())->update(['is_active' => false]);
})->everyMinute();
