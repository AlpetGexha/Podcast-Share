<?php

use App\Models\Episode;
use App\Models\ListeningParty;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|url')]
    public string $mediaUrl = '';

    #[Validate('required')]
    public $start_at;


    public function createListeningParty()
    {
        $episode = Episode::create([
            'media_url' => $this->mediaUrl,
        ]);

        ListeningParty::create([
            'episode_id' => $episode->id,
            'name' => $this->name,
            'start_at' => $this->start_at,
        ]);
    }

    public function with()
    {
        return [
            'listening_party' => ListeningParty::all()
        ];
    }

}; ?>


<div class="flex flex-col min-h-screen pt-8 bg-emerald-50">
    {{-- Top Half: Create New Listening Party Form --}}
    <div class="flex items-center justify-center p-4">
        <div class="w-full max-w-lg">
            <form wire:click="createListeningParty" class="mt-6 space-y-6">
                <x-input wire:model='name' placeholder="Listening Party Name"/>
                <x-input wire:model='mediaUrl' placeholder="Listening Party Name"/>
                <x-input wire:model='start_at' placeholder="Listening Party Start Time"
                         :min="now()->subDays(1)"/>
                <x-button type="submit" class="w-full">Create Listening Party</x-button>
            </form>
        </div>
    </div>
</div>
