<?php

use App\Events\EmojiReactionEvent;
use App\Events\NewMessageEvent;
use App\Models\ListeningParty;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    public ListeningParty $listeningParty;

    public int $userId;

    public array $emojis = [];

    public bool $isFinished = false;

    #[Validate('required|string|max:255')]
    public string $message = '';

    public function authenticateUser()
    {
        session()->put('auth_redirect', route('parties.show', $this->listeningParty));
        return redirect()->route('register');
    }

    public function sendEmoji($emoji): void
    {
        $newEmoji = [
            'id' => uniqid(),
            'emoji' => $emoji,
            'x' => rand(100, 300),
            'y' => rand(100, 300),
        ];

        event(new EmojiReactionEvent($this->listeningParty->id, $newEmoji, $this->userId));
    }

    #[On('echo:listening-party.{listeningParty.id},.emoji-reaction')]
    public function receiveEmoji($payload): void
    {
        if ($payload['userId'] !== $this->userId) {
            $this->emojis[] = $payload['emoji'];
        }
    }

    public function sendMessage(): void
    {
        $this->validate();

        $this->listeningParty->messages()->create([
            'user_id' => auth()->user()->id,
            'message' => $this->message,
        ]);

        event(new NewMessageEvent($this->listeningParty->id, $this->message));

        $this->message = '';
    }

    public function getListeners(): array
    {
        return [
            'echo:listening-party.{listeningParty.id},.new-message' => 'refresh',
        ];
    }

    public function mount(ListeningParty $listeningParty)
    {
        if ($this->listeningParty->end_at && $this->listeningParty->end_at->isPast()) {
            $this->isFinished = true;
        }

        if (!auth()->check()) {
            if (!Session::has('user_id')) {
                $this->userId = uniqid('user_', true);
                Session::put('user_id', $this->userId);
            } else {
                $this->userId = Session::get('user_id');
            }
        } else {
            $this->userId = auth()->id();
        }

        $this->listeningParty->load('episode.podcast', 'messages.user');
    }

    public function with()
    {
        return [
            'messages' => $this->listeningParty->messages()->with('user')->orderBy('created_at', 'asc')->get(),
        ];
    }
}; ?>

<div x-data="{
    audio: null,
    isLoading: true,
    isLive: false,
    isPlaying: false,
    countdownText: '',
    isReady: false,
    audioMetadataLoaded: false,
    currentTime: 0,
    startTimestamp: {{ $listeningParty->start_at->timestamp }},
    endTimestamp: {{ $listeningParty->end_at ? $listeningParty->end_at->timestamp : 'null' }},
    copyNotification: false,
    emojis: @entangle('emojis'),
    addEmoji(emoji, event) {
        const newEmoji = {
            id: Date.now(),
            emoji: emoji,
            x: event.clientX,
            y: event.clientY,
        };
        this.emojis.push(newEmoji);
        $wire.sendEmoji(emoji);
    },


    init() {
        this.startCountdown();
        if (this.$refs.audioPlayer && !this.isFinished) {
            this.initializeAudioPlayer();
        }
    },

    initializeAudioPlayer() {
        this.audio = this.$refs.audioPlayer;
        this.audio.addEventListener('loadedmetadata', () => {
            this.isLoading = false;
            this.audioMetadataLoaded = true;
            console.log('Audio duration after metadata loaded:', this.audio.duration);

            this.checkLiveStatus();
        });

        this.audio.addEventListener('timeupdate', () => {
            this.currentTime = this.audio.currentTime;
            if (this.endTimestamp && this.currentTime >= (this.endTimestamp - this.startTimestamp)) {
                this.finishListeningParty();
            }
        });

        this.audio.addEventListener('play', () => {
            this.isPlaying = true;
            this.isReady = true;
        });

        this.audio.addEventListener('pause', () => {
            this.isPlaying = false;
        });

        this.audio.addEventListener('ended', () => {
            this.isPlaying = false;
            this.finishListeningParty();
        });
    },

    finishListeningParty() {
        $wire.isFinished = true;
        $wire.$refresh();
        this.isPlaying = false;
        if (this.audio) {
            this.audio.pause();
        }
    },

    startCountdown() {
        this.checkLiveStatus();
        setInterval(() => this.checkLiveStatus(), 1000);
    },

    checkLiveStatus() {
        const now = Math.floor(Date.now() / 1000);
        const timeUntilStart = this.startTimestamp - now;

        if (timeUntilStart <= 0) {
            this.isLive = true;
            this.countdownText = 'Live';
            if (this.audio && !this.isPlaying && !this.isFinished) {
                this.playAudio();
            }
        } else {
            const days = Math.floor(timeUntilStart / 86400);
            const hours = Math.floor((timeUntilStart % 86400) / 3600);
            const minutes = Math.floor((timeUntilStart % 3600) / 60);
            const seconds = timeUntilStart % 60;
            this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }
    },

    playAudio() {
        if (!this.audio) return;
        const now = Math.floor(Date.now() / 1000);
        const elapsedTime = Math.max(0, now - this.startTimestamp);
        this.audio.currentTime = elapsedTime;
        this.audio.play().catch(error => {
            console.error('Playback failed:', error);
            this.isPlaying = false;
            this.isReady = false;
        });
    },

    joinAndBeReady() {
        this.isReady = true;
        if (this.isLive && this.audio && !this.isFinished) {
            this.playAudio();
        }
    },

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    },

    copyToClipboard() {
        navigator.clipboard.writeText(window.location.href);
        this.copyNotification = true;
        setTimeout(() => {
            this.copyNotification = false;
        }, 3000);
    },

}" x-init="init()">
    @if ($listeningParty->end_at === null)
        <div class="flex items-center justify-center min-h-screen bg-primary-50" wire:poll.5s>
            <div class="w-full max-w-2xl p-8 mx-8 bg-white rounded-lg shadow-lg">
                <div class="flex items-center justify-center space-x-8">
                    <div class="relative flex items-center justify-center w-16 h-16">
                        <span
                            class="absolute inline-flex rounded-full opacity-75 size-10 bg-primary-400 animate-ping"></span>
                        <span
                            class="relative inline-flex items-center justify-center text-2xl font-bold text-white rounded-full size-12 bg-primary-500">
                            🫶
                            </svg>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-serif text-lg font-semibold text-slate-900">{{__('Creating your listening party')}}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            The {{config('app.name')}} room <span class="font-bold"> {{ $listeningParty->name }}</span>
                            is being put
                            together...
                        </p>
                    </div>
                </div>
            </div>
        </div>
        {{--    @elseif($isFinished)--}}
        {{--        <div class="flex items-center justify-center min-h-screen bg-primary-50">--}}
        {{--            <div class="w-full max-w-2xl p-8 mx-8 text-center bg-white rounded-lg shadow-lg">--}}
        {{--                <h2 class="mb-4 font-serif text-2xl font-bold text-slate-900">This listening party has finished 🥲</h2>--}}
        {{--                <p class="mt-2 text-slate-600">The {{ config('app.name') }} room <span--}}
        {{--                        class="font-bold">{{ $listeningParty->name }}</span> is no longer live.--}}
        {{--                </p>--}}
        {{--            </div>--}}
        {{--        </div>--}}
    @else
        <audio x-ref="audioPlayer" :src="'{{ $listeningParty->episode->media_url }}'" preload="auto"></audio>


        <div x-show="!isLive" class="flex items-center justify-center min-h-screen bg-primary-50" x-cloak>
            <div class="relative w-full max-w-2xl p-6 bg-white rounded-lg shadow-lg">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <x-avatar src="{{ $listeningParty->episode->podcast->artwork_url }}" size="xl"
                                  rounded="sm" alt="Podcast Artwork"/>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[0.9rem] font-semibold truncate text-slate-900">
                            {{ $listeningParty->name }}</p>
                        <div class="mt-0.8">
                            <p class="max-w-xs text-sm truncate text-slate-600">
                                {{ $listeningParty->episode->title }}</p>
                            <p class="text-[0.7rem] tracking-tighter uppercase text-slate-400">
                                {{ $listeningParty->episode->podcast->title }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="font-serif font-semibold tracking-tight text-slate-600">Starting in:</p>
                    <p class="font-mono text-3xl font-semibold tracking-wider text-primary-700" x-text="countdownText">
                    </p>
                </div>

                <div class="mt-6">
                    <x-button x-show="!isReady" class="w-full" @click="joinAndBeReady()">Join and Be Ready</x-button>
                </div>

                <h2 x-show="isReady"
                    class="mt-8 font-serif text-lg tracking-tight text-center text-slate-900 font-bolder">
                    Ready to start the {{ config('app.name') }} party! Stay tuned. 🫶</h2>

                <div class="flex items-center justify-end mt-8">
                    <button @click="copyToClipboard();"
                            class="flex items-center justify-center w-auto h-8 px-3 py-1 text-xs bg-white border rounded-md cursor-pointer border-neutral-200/60 hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none text-neutral-500 hover:text-neutral-600 group">
                        <span x-show="!copyNotification">Share Listening Party URL</span>
                        <svg x-show="!copyNotification" class="w-4 h-4 ml-1.5 stroke-current"
                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                        </svg>
                        <span x-show="copyNotification" class="tracking-tight text-primary-500" x-cloak>Copied!</span>
                        <svg x-show="copyNotification" class="w-4 h-4 ml-1.5 text-green-500 stroke-current"
                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>


        <div x-show="isLive" x-cloak class="flex items-center justify-center min-h-screen bg-primary-50">
            <div class="w-full max-w-6xl p-6 space-y-6">
                <div class="flex space-x-6">
                    <!-- Left Column: Listening Party Info and Emoji Picker -->
                    <div class="w-1/2 space-y-6">
                        <!-- Listening Party Info -->
                        <div class="p-6 bg-white rounded-lg shadow-lg">
                            <div class="flex items-center mb-6 space-x-4">
                                <div class="flex-shrink-0">
                                    <x-avatar src="{{ $listeningParty->episode->podcast->artwork_url }}" size="xl"
                                              rounded="sm" alt="Podcast Artwork"/>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-lg font-semibold truncate text-slate-900">
                                        {{ $listeningParty->name }}</p>
                                    <p class="text-sm truncate text-slate-600">{{ $listeningParty->episode->title }}
                                    </p>
                                    <p class="text-xs tracking-tighter uppercase text-slate-400">
                                        {{ $listeningParty->episode->podcast->title }}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-6" x-show="audioMetadataLoaded">
                                <div class="flex items-center justify-between mb-2">
                                    <span x-text="formatTime(currentTime)" class="text-sm text-slate-600"></span>
                                    <span class="text-sm text-slate-600">
                                        @php
                                            $duration = $listeningParty->start_at->diffInSeconds(
                                                $listeningParty->end_at,
                                            );
                                            $minutes = floor($duration / 60);
                                            $seconds = $duration % 60;
                                        @endphp
                                        {{ sprintf('%02d:%02d', $minutes, $seconds) }}
                                    </span>
                                </div>
                                <div class="h-2 rounded-full bg-primary-100">
                                    <div class="h-2 rounded-full bg-primary-500"
                                         :style="`width: ${(currentTime / audio.duration) * 100}%`"></div>
                                </div>
                            </div>

                            <div x-show="!isPlaying" class="mt-6">
                                <x-button class="w-full" primary label="Join Listening Party"/>
                            </div>
                        </div>

                        <!-- Emoji Picker -->
                        <div class="p-4 bg-white rounded-lg shadow-lg">
                            <div class="grid grid-cols-6 gap-2">
                                @foreach (['👍', '❤️', '😂', '😮', '😢', '😡'] as $emoji)
                                    <button @click="addEmoji('{{ $emoji }}', $event)"
                                            class="p-2 text-2xl transition-colors rounded-full hover:bg-primary-100">
                                        {{ $emoji }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="fixed inset-0 pointer-events-none" aria-hidden="true">
                            <template x-for="emoji in emojis" :key="emoji.id">
                                <div class="absolute text-4xl animate-fall"
                                     :style="`left: ${emoji.x}px; top: ${emoji.y}px;`" x-text="emoji.emoji"></div>
                            </template>
                        </div>
                    </div>

                    <!-- Right Column: Chat Room -->
                    <div class="w-1/2">
                        <div class="bg-white rounded-lg shadow-lg h-[600px] flex flex-col">
                            <div class="flex flex-col justify-end flex-1 p-4 overflow-y-auto" id="message-container">
                                <div class="space-y-0.5">
                                    @forelse ($messages as $message)
                                        <div class="px-2 py-2 rounded hover:bg-slate-100"
                                             wire:key="{{ $message->id }}">
                                            <div class="flex items-center">
                                                <x-avatar xs
                                                          label="{{ strtoupper(substr($message->user->name, 0, 1)) }}"/>
                                                <div class="flex items-center ml-2 space-x-2">
                                                    <span
                                                        class="text-xs font-bold text-slate-900">{{ $message->user->name }}:</span>
                                                    <p class="text-sm text-slate-700">{{ $message->message }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-xs font-bold text-slate-900">
                                            No Message Yet. Be the first
                                        </span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="p-4 border-t">
                                @auth
                                    <form class="flex space-x-2" wire:submit='sendMessage'>
                                        <x-input type="text" placeholder="Type your message..." wire:model='message'
                                                 class="w-full"/>
                                        <x-button primary label="Send" type="submit"/>
                                    </form>
                                @else
                                    <x-button wire:click="authenticateUser" label="Login to Chat" class="w-full"/>
                                @endauth
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    @endif
</div>
