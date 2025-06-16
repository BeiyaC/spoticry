<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyService
{
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?\DateTimeInterface $tokenExpiration = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        ParameterBagInterface $params
    ) {
        $this->clientId = $params->get('spotify_client_id');
        $this->clientSecret = $params->get('spotify_client_secret');
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiration && $this->tokenExpiration > new \DateTime()) {
            return $this->accessToken;
        }

        return $this->cache->get('spotify_access_token', function (ItemInterface $item) {
            $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
            $this->tokenExpiration = new \DateTime('+' . $data['expires_in'] . ' seconds');

            $item->expiresAfter($data['expires_in'] - 60);

            return $this->accessToken;
        });
    }

    public function searchArtist(string $query, int $limit = 10): array
    {
        $cacheKey = 'spotify_search_' . md5($query . $limit);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $limit) {
            $item->expiresAfter(3600); // Cache for 1 hour

            $response = $this->httpClient->request('GET', 'https://api.spotify.com/v1/search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
                'query' => [
                    'q' => $query,
                    'type' => 'artist',
                    'limit' => $limit,
                ],
            ]);

            return $response->toArray();
        });
    }

    public function getArtistInfo(string $artistId): array
    {
        $cacheKey = 'spotify_artist_' . $artistId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($artistId) {
            $item->expiresAfter(86400);

            $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/artists/{$artistId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
            ]);

            return $response->toArray();
        });
    }

    public function getArtistTopTracks(string $artistId, string $market = 'FR'): array
    {
        $cacheKey = 'spotify_top_tracks_' . $artistId . '_' . $market;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($artistId, $market) {
            $item->expiresAfter(3600);

            $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/artists/{$artistId}/top-tracks", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
                'query' => [
                    'market' => $market,
                ],
            ]);

            return $response->toArray();
        });
    }

    public function getArtistAlbums(string $artistId, int $limit = 20): array
    {
        $cacheKey = 'spotify_albums_' . $artistId . '_' . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($artistId, $limit) {
            $item->expiresAfter(3600);

            $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/artists/{$artistId}/albums", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
                'query' => [
                    'include_groups' => 'album,single',
                    'limit' => $limit,
                ],
            ]);

            return $response->toArray();
        });
    }

    public function getRelatedArtists(string $artistId): array
    {
        $cacheKey = 'spotify_related_' . $artistId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($artistId) {
            $item->expiresAfter(86400);

            $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/artists/{$artistId}/related-artists", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
            ]);

            return $response->toArray();
        });
    }

    public function getFullArtistData(string $artistId): array
    {
        return [
            'artist' => $this->getArtistInfo($artistId),
            'topTracks' => $this->getArtistTopTracks($artistId),
            'albums' => $this->getArtistAlbums($artistId),
            'relatedArtists' => $this->getRelatedArtists($artistId),
        ];
    }
}
