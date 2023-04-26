<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Service\SecurityService;

class SpotifyApiService {

    const LIMIT_MAX = 50;
    const TRACKS_TO_DELETE_MAX = 100;
    const TRACKS_TO_ADD_MAX = 100;
    private HttpClientInterface $client;
    private SecurityService $securityService;

    public function __construct(HttpClientInterface $client, SecurityService $securityService) 
    {
        $this->client = $client;
        $this->securityService = $securityService;
    }

    public function getLikedItems(int $offset = 0, int $limit = 50): Array
    {
        $url = "https://api.spotify.com/v1/me/tracks?" .
        http_build_query(
            [
                "limit" => $limit,
                "offset" => $offset
            ]
        );

        try 
        {
            $response = $this->sendRequest('GET', $url);
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        if ($response->getStatusCode() !== 200) 
        {
            throw new \Exception($response->getContent());
        }

        return $response->toArray();

        // stokage des résultats dans un array d'Entity(Object) indexé par leur Id
    }

    public function getPlaylistItems(string $playlistId, int $offset = 0, int $limit = 50): Array
    {
        $url = "https://api.spotify.com/v1/playlists/" . $playlistId . "/tracks?" .
        http_build_query(
            [
                "limit" => $limit,
                "offset" => $offset,
                // "field" => ""
            ]
        );

        try 
        {
            $response = $this->sendRequest('GET', $url);
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        if ($response->getStatusCode() !== 200) 
        {
            throw new \Exception($response->getContent());
        }

        return $response->toArray();

        // stokage des résultats dans un array d'Entity(Object) indexé par leur Id
    }

    /**
     * @param string $playlistId
     * @param array $tracks
     * 
     * @return string statusCode 
     */
    public function deletePlaylistItems(string $playlistId, array $tracks): string
    {
        $url = "https://api.spotify.com/v1/playlists/" . $playlistId . "/tracks";
        $json_tracks = json_encode($tracks); 

        try 
        {
            $response = $this->sendRequest('DELETE', $url, [ 'body' => $json_tracks]);
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        if ($response->getStatusCode() !== 200) 
        {
            throw new \Exception($response->getContent());
        }

        return $response->getStatusCode();
    }

    /**
     * @param string $playlistId
     * @param array $tracks
     * 
     * @return string statusCode 
     */
    public function addPlaylistItems(string $playlistId, array $tracks): string
    {
        $url = "https://api.spotify.com/v1/playlists/" . $playlistId . "/tracks";
        $json_tracks = json_encode($tracks); 

        try 
        {
            $response = $this->sendRequest('POST', $url, [ 'body' => $json_tracks]);
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        if ($response->getStatusCode() !== 201) 
        {
            throw new \Exception($response->getContent());
        }

        return $response->getStatusCode();
    }

    public function sendRequest(string $method, string $url, array $data = []): ResponseInterface
    {
        
        $authorization =  "Bearer ". $this->securityService->getAccessToken();

        $data['headers']['Authorization'] = $authorization;

        try 
        {
            $response = $this->client->request($method, $url, $data);
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        if ($response->getStatusCode() == 401) 
        {
           
            $authorization =  "Bearer ". $this->securityService->getNewAccessToken();
           
            $data['headers']['Authorization'] = $authorization;

            try 
            {
                $response = $this->client->request($method, $url, $data);
            } 
            catch (\Throwable $th) 
            {
                throw $th;
            }
        }

        return $response;
    }

}