<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Service\SpotifyApiService;
use App\Service\SecurityService;

class SpotifyService {

    private HttpClientInterface $client;
    private SecurityService $securityService;
    private SpotifyApiService $spotifyApiService;
    private string $playlistId;
    

    public function __construct(HttpClientInterface $client, SecurityService $securityService, SpotifyApiService $spotifyApiService) 
    {
        $this->client = $client;
        $this->playlistId = $_ENV['PLAYLIST_ID'];
        $this->securityService = $securityService;
        $this->spotifyApiService = $spotifyApiService;
    }

    public function updateCurrentSongPlaylist(int $maxDate = 2, string $unity = 'month')
    {
        // recuper titre like avec https://api.spotify.com/v1/me/tracks
        // contient date d'ajout plus id 
        // recuperer via pagination (limit max = 50)

        // possibilité d'ajout dans une playlist, aucune vérification de doublon
        // possiblité de savoir quand une musique est ajoutée dans une playlist

        // Garder la possibilité d'ajouter ou de supprimer des titres à la mains 

        // CONCLUSION 
        //  - GET items from playlist (max 3 requests)
        //  - make array from to old items
        //  - DELETE items to old from playlist (max 2 requests)
        //  - GET items from saved track 
        //  - STOP GET when add date:hours is superior of last update (max 3 requests)
        //  - make array of items that are not in playlist
        //  - POST new items in playlist (max 2 requests)
        //  - save date of update

### - GET items from playlist (max 3 requests)
        
        $limit = SpotifyApiService::LIMIT_MAX;
        $offset = 0;
        $total = 1;
        $items = array();

        while ($offset < $total) 
        {
            $resPlaylistItems = $this->spotifyApiService->getPlaylistItems($this->playlistId, $offset, $limit);
            $items = array_merge($items, $resPlaylistItems['items']);
            $total = $resPlaylistItems['total'];
            $offset += $limit;
        }

### - make array from to old items

        $itemsToDelete = array();

        foreach ($items as $item) 
        {
            $addedAt = new \DateTime($item['added_at']);
            $now = new \DateTime();
            $dateInterval = $addedAt->diff($now);
            $diff = intval($dateInterval->format('%m'));
            
            if ($diff >= $maxDate)
                $itemsToDelete[] = [ 'uri' => $item['track']['uri'] ];
        }

### - DELETE items to old from playlist (max 2 requests)
         
        try
        {
            $limit = SpotifyApiService::TRACKS_TO_DELETE_MAX;
            $offset = 0;
            $total = count($itemsToDelete);

            while ($offset < $total) 
            {
                $this->spotifyApiService->deletePlaylistItems($this->playlistId, ['tracks' => array_slice($itemsToDelete, $offset, $limit)]);
                $offset += $limit;
            }
        }
        catch (\Throwable $e)
        {
            return [$e->__toString()];
        }


### - GET items from saved track 
### - STOP GET when add date:hours is superior of last update (max 3 requests)

        $limit = SpotifyApiService::LIMIT_MAX;
        $offset = 0;
        $total = 1;
        $itemsLiked = array();

        $lastUpdate = new \DateTime();
        $lastUpdate = $lastUpdate->add(\DateInterval::createFromDateString('-2 month'));

        $continue = true;

        while ($continue && $offset < $total)
        {
            $resPlaylistItemsLiked = $this->spotifyApiService->getLikedItems($offset, $limit);
            $itemsLiked = array_merge($itemsLiked, $resPlaylistItemsLiked['items']);
            $total = $resPlaylistItemsLiked['total'];
            $offset += $limit;

            $addedAt = new \DateTime($itemsLiked[count($itemsLiked)-1]['added_at']);
            $dateInterval = $lastUpdate->diff($addedAt);
            $diff = $dateInterval->format('%R');
            
            if ($diff == '-')
                $continue = false;
        }

        $itemsLikedBeforeLastUpade = array();

        foreach($itemsLiked as $item)
        {
            $addedAt = new \DateTime($item['added_at']);
            $dateInterval = $lastUpdate->diff($addedAt);
            $diff = $dateInterval->format('%R');
            
            if ($diff == '+') 
            {
                $itemsLikedBeforeLastUpade[$item['track']['uri']] =  [
                    'addedAt' => $item['added_at'],
                    'uri' => $item['track']['uri']
                ];
            }
        }

        
### - make array of items that are not in playlist

        $playlistItems = array();
        foreach ($items as $item) {
            $playlistItems[$item['track']['uri']] = $item;
        }

        $itemsToAdd = array();
        foreach ($itemsLikedBeforeLastUpade as $uri => $item) 
        {
            if (!isset($playlistItems[$uri]))
            {
                $itemsToAdd[] = $uri;
            }
        }

### - POST new items in playlist (max 2 requests)

        try
        {
            $limit = SpotifyApiService::TRACKS_TO_ADD_MAX;
            $offset = 0;
            $total = count($itemsToAdd);

            while ($offset < $total) 
            {
                $this->spotifyApiService->addPlaylistItems($this->playlistId, ['uris' => array_slice($itemsToAdd, $offset, $limit)]);
                $offset += $limit;
            }
        }
        catch (\Throwable $e)
        {
            return [$e->__toString()];
        }

### - save date of update

        return;
    }

}