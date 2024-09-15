<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Podcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'rss_url',
        'description',
        'artwork_url',
        'language',
        'author',
        'type',
    ];

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function listeningParties(): HasMany
    {
        return $this->hasMany(ListeningParty::class);
    }
}
