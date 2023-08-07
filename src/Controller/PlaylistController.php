<?php

namespace App\Controller;

use App\Service\SpotifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function update_current_song_playlist(Request $request): JsonResponse
    {

        $token = $request->query->get('token');

        if ($token != "28112000") {
            return $this->json(
                [
                'title' => 'Unauthorized',
                'status' => '401',
                'message' => 'You\'re not allowed to access this resource.'
                ],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $this->spotifyService->updateCurrentSongPlaylist();

        return new JsonResponse($data=null, $status=204);
    }
}