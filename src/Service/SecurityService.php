<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityService {

    private HttpClientInterface $client;
    private string $pathFileToken;
    private string $client_secret;
    private string $client_id;

    public function __construct(HttpClientInterface $client) 
    {
        $this->client_secret = $_ENV['CLIENT_SECRET'];
        $this->pathFileToken = $_ENV['TOKEN_FILE'];
        $this->client_id = $_ENV['CLIENT_ID'];
        $this->client = $client;
    }

    public function getAccessToken(): string
    {
        $jsonDataFromFile = file_get_contents($this->pathFileToken);
        $dataFromFile = json_decode($jsonDataFromFile, true);

        return $dataFromFile['access_token'];
    }

    private function getRefreshToken(): string
    {
        $jsonDataFromFile = file_get_contents($this->pathFileToken);
        $dataFromFile = json_decode($jsonDataFromFile, true);

        return $dataFromFile['refresh_token'];
    }

    public function getNewAccessToken(): string
    {
        $body = [
            'grant_type' =>'refresh_token',
            'refresh_token' => $this->getRefreshToken()
        ];

        $headers = [
            'Authorization' => 'Basic '.base64_encode($this->client_id.':'.$this->client_secret)
        ];

        $response = $this->client->request(
            'POST', 
            'https://accounts.spotify.com/api/token',
            [
                'body' => $body,
                'headers' => $headers
            ] 
        );

        $data = $response->toArray();

        $token = $data['access_token'];
        // $refresh_token = $data['refresh_token'];
        $expires_in = $data['expires_in'];

        // stokage des token dans ficher.json

        $data_file = [
            'access_token' => $token,
            'refresh_token' => $this->getRefreshToken(),
        ];
        
        $this->stockageToken($data_file);

        return $token;
    }

    public function stockageToken(Array $data)
    {
        $jsonData = json_encode($data);
        
        file_put_contents($this->pathFileToken, $jsonData);
    }
}