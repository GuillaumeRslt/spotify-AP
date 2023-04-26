<?php

namespace App\Controller;

use App\Service\SpotifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SpotifyApiService;

class PlaylistController extends AbstractController
{

    private SpotifyApiService $ApiService;
    private SpotifyService $spotifyService;
    private string $playlistId;

    public function __construct(SpotifyApiService $ApiService, SpotifyService $spotifyService)
    {
        $this->playlistId = $_ENV['PLAYLIST_ID'];
        $this->ApiService = $ApiService;
        $this->spotifyService = $spotifyService;
    }

    #[Route('/current_song_playlist', name: 'update_current_song_playlist', methods: ['PUT'])]
    public function update_current_song_playlist(): JsonResponse
    {

        $this->spotifyService->updateCurrentSongPlaylist();

        // return $this->json($items, 200);

        return new JsonResponse($data=null, $status=204);
    }
}