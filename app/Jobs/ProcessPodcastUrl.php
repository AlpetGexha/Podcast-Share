<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\ListeningParty;
use App\Models\Podcast;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPodcastUrl implements ShouldQueue
{
    use Queueable;


    // https://feeds.simplecast.com/sY509q85

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string         $rssUrl,
        public ListeningParty $listeningParty,
        public Episode        $episode
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // grab the podcast name information
        // grab the latest episode
        // add the latest episode media url to the existing episode
        // update the existing episode's media url to the latest episode's media url
        // find the episodes length and set the listening end_time to the start_at + length of the episode

        $xml = simplexml_load_file($this->rssUrl);

        $podcastTitle = $xml->channel->title;
        $podcastArtworkUrl = $xml->channel->image->url;

        $latestEpisode = $xml->channel->item[0];

        $episodeTitle = $latestEpisode->title;
        $episodeMediaUrl = (string)$latestEpisode->enclosure['url'];

        // register the itunes namespace to grab the duration
        $namespaces = $xml->getNamespaces(true);
        $itunesNamespace = $namespaces['itunes'];

        $episodeLength = $latestEpisode->children($itunesNamespace)->duration;

        $interval = CarbonInterval::createFromFormat('H:i:s', $episodeLength);

        $endTime = $this->listeningParty->start_at->add($interval);

        // save these to the database
        // create the Podcast, and then upate the episode to be linked to the podcast

        $podcast = Podcast::updateOrCreate([
            'title' => $podcastTitle,
            'artwork_url' => $podcastArtworkUrl,
            'rss_url' => $this->rssUrl,
        ]);

        $this->episode->podcast()->associate($podcast);

        $this->episode->update([
            'title' => $episodeTitle,
            'media_url' => $episodeMediaUrl,
        ]);


        $this->listeningParty->update([
            'end_time' => $endTime,
        ]);

    }
}
