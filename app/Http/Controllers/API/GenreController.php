<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Genre;

class GenreController extends BaseController
{
    public function get()
    {
        $genres = Genre::all();
        $baseGenres = $genres->where('parent_id', null);
        $nestedGenres = array_reduce($baseGenres->toArray(), function ($acc, $genre) use ($genres) {
            array_push($acc, (object) [
                'id' => $genre['id'],
                'slug' => $genre['slug'],
                'subgenres' => array_values(
                    $genres
                        ->where('parent_id', $genre['id'])
                        ->map
                        ->only(['id', 'slug', 'target'])
                        ->toArray()
                )
            ]);
            return $acc;
        }, []);
        return $this->sendResponse($nestedGenres, 200);
    }
}
