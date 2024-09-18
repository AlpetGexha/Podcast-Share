<?php

use Livewire\Volt\Component;
use App\Models\ListeningParty;
new class extends Component {

    public ListeningParty $listeningParty;

    public function mount(ListeningParty $listeningParty)
    {
        $this->listeningParty = $listeningParty;
    }
}; ?>
<div class="text-red-600">
    {{ $listeningParty->title }}
    {{ $listeningParty->start_at }}
    {{-- {{ $listeningParty->episode->title }} --}}
    {{-- {{ $listeningParty->episode->podcast->title }} --}}
    {{-- <img src="{{ $listeningParty->episode->podcast->artwork_url }}" class="size-28" /> --}}
</div>
