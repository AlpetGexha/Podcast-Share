<?php

use App\Models\ListeningParty;
use Livewire\Volt\Component;

new class extends Component {

    public string $name = '';
    public $start_at;

    public function with()
    {
        return [
            'listening_party' => ListeningParty::all()
        ];
    }

}; ?>

<div>
    Welcome to the dashboard!
</div>
