<?php

use Livewire\Volt\Component;
use App\Models\ListeningParty;
new class extends Component {
    public ListeningParty $listeningParty;
    public function mount(ListeningParty $listeningParty)
    {
        // $listeningParty->load('episode');
        $this->listeningParty = $listeningParty;
    }
}; ?>
<div>
    {{ $listeningParty }}
    {{ $listeningParty->episode->title }}
    {{ $listeningParty->start_at }}
    {{-- {{ $listeningParty->episode->podcast->title }} --}}
    {{-- <img src="{{ $listeningParty->episode->podcast->artwork_url }}" class="size-28" /> --}}
</div>
