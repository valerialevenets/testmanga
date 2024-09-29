<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manga extends Model
{
    use HasFactory;
    protected $fillable = [
        'anilist_id',
        'idMal',
        'name',
        'titles',
        'synonyms',
        'is_adult',
        'status',
        'chapters',
        'volumes',
        'description',
        'score',
        'source',
        'cover_image',
        'started_at',
        'ended_at',

    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'mangas_genres', 'manga_id', 'genre_id');
    }
}
