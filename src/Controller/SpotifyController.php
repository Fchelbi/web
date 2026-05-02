<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SpotifyController extends AbstractController
{
    #[Route('/spotify/playlist/{mood}', name: 'spotify_playlist', methods: ['GET'])]
    public function getPlaylist(string $mood): JsonResponse
    {
        $playlists = [
            'excellent' => [
                ['name' => 'Happy Hits!', 'id' => '37i9dQZF1DXdPec7aLTmlC', 'owner' => 'Spotify'],
                ['name' => 'Good Vibes', 'id' => '37i9dQZF1DX0XUsuxWHRQd', 'owner' => 'Spotify'],
                ['name' => 'Feel Good Friday', 'id' => '37i9dQZF1DX9XIFQuFvzM4', 'owner' => 'Spotify'],
                ['name' => 'Pop Rising', 'id' => '37i9dQZF1DWUa8ZRTfalHk', 'owner' => 'Spotify'],
            ],
            'bien' => [
                ['name' => 'Positive Vibes', 'id' => '37i9dQZF1DX3rxVfibe1L0', 'owner' => 'Spotify'],
                ['name' => 'Chill Hits', 'id' => '37i9dQZF1DX4WYpdgoIcn6', 'owner' => 'Spotify'],
                ['name' => 'Mood Booster', 'id' => '37i9dQZF1DX3rxVfibe1L0', 'owner' => 'Spotify'],
                ['name' => 'Feel Good Pop', 'id' => '37i9dQZF1DX2sUQwD7tbmL', 'owner' => 'Spotify'],
            ],
            'neutre' => [
                ['name' => 'Lofi Beats', 'id' => '37i9dQZF1DWWQRwui0ExPn', 'owner' => 'Spotify'],
                ['name' => 'Chill & Study', 'id' => '37i9dQZF1DX8NTLI2TtZa6', 'owner' => 'Spotify'],
                ['name' => 'Deep Focus', 'id' => '37i9dQZF1DWZeKCadgRdKQ', 'owner' => 'Spotify'],
                ['name' => 'Peaceful Piano', 'id' => '37i9dQZF1DX4sWUI1aMb7Q', 'owner' => 'Spotify'],
            ],
            'fatigue' => [
                ['name' => 'Sleep', 'id' => '37i9dQZF1DWZd79rJ6a7lp', 'owner' => 'Spotify'],
                ['name' => 'Calm Vibes', 'id' => '37i9dQZF1DX3Ogo9pFvBkY', 'owner' => 'Spotify'],
                ['name' => 'Acoustic Calm', 'id' => '37i9dQZF1DX2sk7lOMLvkj', 'owner' => 'Spotify'],
                ['name' => 'Soft Pop Hits', 'id' => '37i9dQZF1DWUvQoIOFMFUT', 'owner' => 'Spotify'],
            ],
            'triste' => [
                ['name' => 'Sad Songs', 'id' => '37i9dQZF1DX3YSRoSdA634', 'owner' => 'Spotify'],
                ['name' => 'Healing', 'id' => '37i9dQZF1DWX83CujKHMna', 'owner' => 'Spotify'],
                ['name' => 'Life Sucks', 'id' => '37i9dQZF1DX3rxVfibe1L0', 'owner' => 'Spotify'],
                ['name' => 'Rainy Day', 'id' => '37i9dQZF1DX2pSTOxoPbx9', 'owner' => 'Spotify'],
            ],
        ];

        $result = $playlists[$mood] ?? $playlists['neutre'];

        // Ajoute l'embed URL
        foreach ($result as &$p) {
            $p['embed'] = 'https://open.spotify.com/embed/playlist/' . $p['id'];
            $p['url']   = 'https://open.spotify.com/playlist/' . $p['id'];
            $p['image'] = 'https://misc.scdn.co/liked-songs/liked-songs-300.png';
        }

        return new JsonResponse($result);
    }
}