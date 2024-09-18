<?php

use App\Models\Episode;
use App\Models\ListeningParty;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use App\Jobs\ProcessPodcastUrl;

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

        $listeningParty = ListeningParty::create([
            'episode_id' => $episode->id,
            'title' => $this->name,
            'start_at' => $this->start_at,
        ]);

        ProcessPodcastUrl::dispatch($this->mediaUrl, $listeningParty, $episode);

        return redirect()->route('parties.show', $listeningParty);
    }

    public function with(): array
    {
        return [
            'listening_parties' => ListeningParty::where('is_active', true)->with('episode')->get(),
        ];
    }
}; ?>

<div class="min-h-screen bg-slate-50 flex flex-col">
    <h1 class="mt-12 text-3xl text-center font-cursive text-slate-800"> Not AlpetG Podcast Live</h1>

    <!-- Top half: Create Listening Party form -->
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-lg">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-center mb-6">Create Listening Party</h2>
                <form wire:submit='createListeningParty' class="space-y-6">
                    <x-input wire:model='name' placeholder="Listening Party Name" />
                    <x-input wire:model='mediaUrl' placeholder="Podcast RSS Feed URL"
                        description="Entering the RSS Feed URL will grab the latest episode" />
                    <x-datetime-picker wire:model='start_at' placeholder="Listening Party Start Time" :min="now()->subDays(1)"
                        requires-confirmation />
                    <x-button type="submit" class="w-full">Create Listening Party</x-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bottom half: Scrollable list of existing parties -->
    <div class="h-1/2 bg-gray-100 p-4 overflow-hidden">
        <div class="max-w-lg mx-auto">
            <h3 class="text-xl font-semibold mb-4">Ongoing Listening Parties</h3>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-y-auto max-h-[calc(50vh-8rem)]">
                    @foreach ($listening_parties as $listeningParty)
                        <a href="{{ route('parties.show', $listeningParty) }}" class="block">
                            <div
                                class="flex items-center justify-between p-4 border-b border-gray-200 hover:bg-gray-50 transition duration-150 ease-in-out">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <img src="{{ $listeningParty->episode->podcast?->artwork_url }}"
                                            class="w-10 h-10 rounded-full" alt="Podcast Artwork" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate">
                                            {{ $listeningParty->name }}
                                        </div>
                                        <div class="text-sm text-gray-500 truncate">
                                            {{ $listeningParty->episode->title }}
                                        </div>
                                        <div class="text-xs text-gray-400 truncate">
                                            {{ $listeningParty->episode?->podcast?->title }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-600" x-data="{
                                            startTime: {{ $listeningParty->start_at->timestamp }},
                                            countdownText: '',
                                            isLive: {{ $listeningParty->start_at->isPast() && $listeningParty->is_active ? 'true' : 'false' }},
                                            updateCountdown() {
                                                const now = Math.floor(Date.now() / 1000);
                                                const timeUntilStart = this.startTime - now;
                                                if (timeUntilStart <= 0) {
                                                    this.countdownText = 'Live';
                                                    this.isLive = true;
                                                } else {
                                                    const days = Math.floor(timeUntilStart / 86400);
                                                    const hours = Math.floor((timeUntilStart % 86400) / 3600);
                                                    const minutes = Math.floor((timeUntilStart % 3600) / 60);
                                                    const seconds = timeUntilStart % 60;
                                                    this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                                                }
                                            }
                                        }"
                                            x-init="updateCountdown();
                                            setInterval(() => updateCountdown(), 1000);">
                                            <div x-show="isLive">
                                                <x-badge flat rose label="Live">
                                                    <x-slot name="prepend" class="relative flex items-center w-2 h-2">
                                                        <span
                                                            class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-rose-500 animate-ping"></span>

                                                        <span
                                                            class="relative inline-flex w-2 h-2 rounded-full bg-rose-500"></span>
                                                    </x-slot>

                                                </x-badge>
                                            </div>
                                            <div x-show="!isLive">
                                                Starts in: <span x-text="countdownText"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <x-button flat xs class="w-20">Join</x-button>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
