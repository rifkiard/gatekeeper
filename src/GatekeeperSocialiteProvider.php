<?php

namespace Rifkiard\Gatekeeper;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use GuzzleHttp\RequestOptions;

class GatekeeperSocialiteProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Get the base URL from the guzzle configuration array.
     * (Argumen ke-5 di constructor disimpan di $this->guzzle oleh parent)
     */


    protected function getBaseUrl()
    {
        $url = $this->guzzle['base_url'] ?? null;

        if (!$url) {
            throw new \Exception("Gatekeeper Config Error: 'base_url' is missing in services.php");
        }

        return rtrim($url, '/');
    }

    /**
     * URL untuk lempar user ke halaman login AstraWorld Gatekeeper.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl() . '/oauth/accounts', $state);
    }

    /**
     * URL untuk tukar 'code' jadi 'access_token'.
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl() . '/oauth/token';
    }

    /**
     * Ambil data user mentah dari API Gatekeeper menggunakan token.
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl() . '/api/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Mapping hasil JSON dari API Gatekeeper ke object User Socialite.
     */
    protected function mapUserToObject(array $user)
    {
        if (isset($user['setting'])) {
            $user['setting'] = (object) $user['setting'];
        }

        return (new User)->setRaw($user)->map($user);
    }

    /**
     * Definisikan default scope jika diperlukan (opsional).
     */
    protected $scopes = ['*'];

    /**
     * Karakter pemisah scope.
     */
    protected $scopeSeparator = ' ';
}
