<?php

use Livewire\Volt\Component;
use App\Models\ListeningParty;
new class extends Component {
    public $listeningParty;
    public function mount(ListeningParty $listeningParty)
    {
        // $listeningParty->load('episode');
        $this->listeningParty = $listeningParty->load('episode.podcast');

    }
}; ?>
<div class="flex flex-col max-h-screen min-h-screen m-auto">
    @if ($listeningParty->end_at !== null) // The Job its still runing or not created yet
        <div class="flex items-center justify-center p-4" wire:poll.5s>
            Creating a new listening party hold tight...
        </div>
    @else
        <div x-data="{
            audio: null,
            currentOffset: 0,
            isPlaying: false,
            showPlayButton: false,
            startTimestamp: {{ $listeningParty->start_at->timestamp }},
            serverTime: {{ now()->timestamp }},

            initializeAudio() {
                this.audio = this.$refs.audioPlayer;
                this.updateCurrentOffset();
                setInterval(() => this.updateCurrentOffset(), 1000);
                this.audio.addEventListener('loadedmetadata', () => this.attemptAutoplay());
                this.audio.addEventListener('play', () => this.isPlaying = true);
                this.audio.addEventListener('pause', () => this.isPlaying = false);
            },

            updateCurrentOffset() {
                const now = Math.floor(Date.now() / 1000);
                const timeDiff = now - this.serverTime;
                const adjustedNow = now - timeDiff;
                this.currentOffset = Math.max(0, adjustedNow - this.startTimestamp);
                if (this.isPlaying && Math.abs(this.audio.currentTime - this.currentOffset) > 1) {
                    this.audio.currentTime = this.currentOffset;
                }
            },

            attemptAutoplay() {
                this.audio.currentTime = this.currentOffset;
                this.audio.play().catch(() => {
                    this.showPlayButton = true;
                });
            },

            manualPlay() {
                this.audio.currentTime = this.currentOffset;
                this.audio.play().then(() => {
                    this.showPlayButton = false;
                }).catch(error => {
                    console.error('Manual play failed:', error);
                });
            },

            formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            }
        }" x-init="initializeAudio()">
            <audio x-ref="audioPlayer" :src="'{{ $listeningParty->episode->media_url }}'" preload="auto"></audio>
            <div>{{ $listeningParty->podcast->title }}</div>
            <div>{{ $listeningParty->episode->title }}</div>
            <div>
                Current Time: <span x-text="formatTime(currentOffset)"></span>
                <span x-show="isPlaying" class="ml-2 text-green-500">▶ Playing</span>
            </div>
            <div>Start Time: {{ $listeningParty->start_at }}</div>
            <div>Server Time: {{ now() }}</div>
            <button x-show="showPlayButton" @click="manualPlay()"
                class="mt-4 px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">
                Start Listening
            </button>
        </div>
    @endif
</div>
