<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SecurityService;
use Psr\Log\LoggerInterface;

class LoginController extends AbstractController
{

    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private SecurityService $securityService;
    private string $redirect_uri;
    private string $client_id;
    private string $client_secret;
    private string $scope = "user-read-private user-read-email playlist-modify-public user-modify-playback-state user-library-read";

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, SecurityService $securityService)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->client_id = $_ENV['CLIENT_ID'];
        $this->client_secret = $_ENV['CLIENT_SECRET'];
        $this->redirect_uri = $_ENV['SPOTIFY_CALLBACK_URI'];
        $this->securityService = $securityService;
    }

    #[Route('/login', name: 'login')]
    public function login(): RedirectResponse
    {

        $state = random_int(100000000, 999999999);
        // stoquer state dans la session

        $url = 'https://accounts.spotify.com/authorize?' .
        http_build_query(
            [
                "response_type" => "code",
                "client_id" => $this->client_id,
                "scope" => $this->scope,
                "redirect_uri" => $this->redirect_uri,
                "state" => $state
            ]
        );

        return $this->redirect($url);
    }

    #[Route('/callback', name: 'login_callbak')]
    public function callback(Request $request)
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state', null);

        // vérifier state dans la session
        if ($state === null) {
            return $this->redirect('/');
        }

        $body = [
            "code" => $code,
            "redirect_uri" => $this->redirect_uri,
            "grant_type" => "authorization_code"
        ];

        $headers = [
            "Authorization" => "Basic ". base64_encode($this->client_id. ":". $this->client_secret)
        ];

        $response = $this->client->request(
            'POST',
            'https://accounts.spotify.com/api/token',
            [
                "body" => $body,
                "headers" => $headers
            ]
        );

        // verifier response est bien 200

        $data = $response->toArray();

        $token = $data['access_token'];
        $refresh_token = $data['refresh_token'];
        $expires_in = $data['expires_in'];

        $this->logger->info("token: $token");
        $this->logger->info("Refresh token: $refresh_token");
        $this->logger->info("Expires in: $expires_in");
        
        $this->securityService->stockageToken(
            [
                'access_token' => $token,
                'refresh_token' => $refresh_token,
            ]
        );
        
        return $this->json("OK", 200);

    }

    #[Route('/', name: 'default')]
    public function default(): JsonResponse
    {
        return $this->json(
            [
                "Status" => "404",
                "Title" => "Not Found",
                "Message" => "Route not found"
            ],
            JsonResponse::HTTP_NOT_FOUND
        );
    }
}
