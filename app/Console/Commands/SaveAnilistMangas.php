<?php

namespace App\Console\Commands;

use App\Anilist\Api;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\MangaGenre;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

class SaveAnilistMangas extends Command
{
    private Collection $genres;
    private Collection $tags;
    public function __construct(private Api $api)
    {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:save-anilist-mangas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->init();
        $page = 1;
        while(true){
//            $mangas = $this->api->fetchAllManga($page)['data']['Page']['media'];
            $mangas = $this->getManga($page);
            if(empty($mangas)){ break; }

            $this->insertPart($mangas);

            $page++;
            usleep(800000);
            break;
        }
    }

    private function getManga(int $page, int $iteration=1)
    {
        if($iteration > 5){
            throw new \Exception('Iterations exceeded');
        }
        try {
            return $this->api->fetchAllManga($page, 5)['data']['Page']['media'];
        } catch (ConnectionException $ex) {
            usleep(200000);
            return $this->getManga($page, $iteration+1);
        }
    }

    private function init()
    {
        $this->genres = Genre::all();
        $this->tags = Tag::all();
    }
    private function insertPart(array $mangas)
    {
        $tmp = [];
        $genres = [];

        $mangaCollection = new Collection();
        foreach($mangas as $manga){
            $mng = new Manga([
                'anilist_id' => $manga['id'],
                'idMal' => $manga['idMal'],
                'name' => $manga['title']['english'] ?: $manga['title']['romaji'],
                'titles' => json_encode($manga['title']),
                'synonyms' => json_encode($manga['synonyms']),
                'is_adult' => $manga['isAdult'],
                'status' => $manga['status'],
                'chapters' => $manga['chapters'],
                'volumes' => $manga['volumes'],
                'description' => strip_tags($manga['description']),
                'source' => $manga['source'],
                'score' => $manga['averageScore'],
                'cover_image' => $manga['coverImage']['extraLarge'] ?: $manga['coverImage']['large'],
//                    'started_at' => \DateTime::createFromFormat('Y', $manga['startDate']['year']),
            ]);

            $mangaCollection->push($mng);
            $genres[$manga['id']] = $manga['genres'];
        }


        Manga::upsert($mangaCollection->toArray(), ['anilist_id']);
        $mangaCollection->fresh();
        $mangaGenreRelations = [];
        foreach ($mangaCollection as $manga) {
            foreach ($this->genres->whereIn('name', $genres[$manga->anilist_id])->pluck('id') as $item) {
                $mangaGenreRelations[] = [
                    'manga_id' => $manga['id'],
                    'genre_id' => $item
                ];
            }
        }
        dd($mangaCollection->first()->id);
        dd($mangaGenreRelations);
        MangaGenre::upsert($mangaGenreRelations, ['manga_id', 'genre_id']);
//        foreach ($tmp as &$manga) {
////            $manga->genres()->update($genres[$manga->idMal]);
//            $manga['genres'] = $genres[$manga['idMal']];
//        }
//        Manga::upsert($tmp, ['anilist_id'], ['genres']);
//        $tmp = array_chunk($tmp, 10);
//        foreach($tmp as $chunk){
////                Manga::upsert($tmp, ['anilist_id'], array_keys($tmp[0]));
//            Manga::upsert($chunk, ['anilist_id'], array_keys($chunk[0]));
//        }
    }

//    private function insertPart(array $mangas)
//    {
//        $tmp = [];
//        foreach($mangas as $manga){
//            $mng = [
//                'anilist_id' => $manga['id'],
//                'idMal' => $manga['idMal'],
//                'name' => $manga['title']['english'] ?: $manga['title']['romaji'],
//                'titles' => json_encode($manga['title']),
//                'synonyms' => json_encode($manga['synonyms']),
//                'is_adult' => $manga['isAdult'],
//                'status' => $manga['status'],
//                'chapters' => $manga['chapters'],
//                'volumes' => $manga['volumes'],
//                'description' => strip_tags($manga['description']),
//                'source' => $manga['source'],
//                'score' => $manga['averageScore'],
//                'cover_image' => $manga['coverImage']['extraLarge'] ?: $manga['coverImage']['large'],
////                    'started_at' => \DateTime::createFromFormat('Y', $manga['startDate']['year']),
//                'genres' => [
//                    [
//                        'id' => 1
//                    ]
//                ],
//            ];
//            $tmp[] = $mng;
//        }
//        $tmp = array_chunk($tmp, 10);
//        foreach($tmp as $chunk){
////                Manga::upsert($tmp, ['anilist_id'], array_keys($tmp[0]));
//            Manga::upsert($chunk, ['anilist_id'], array_keys($chunk[0]));
//        }
//    }
}
