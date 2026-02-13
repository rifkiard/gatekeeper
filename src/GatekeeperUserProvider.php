<?php

namespace Rifkiard\Gatekeeper;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Http;

class GatekeeperUserProvider implements UserProvider
{
    public function retrieveByToken($identifier, $token)
    {
        return \Illuminate\Support\Facades\Cache::remember(Gatekeeper::userAuthCacheKey($token), 300, function () use ($token) {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withToken($token)
                ->get(Gatekeeper::baseUrl() . '/api/user', [
                    'client_id' => Gatekeeper::clientId(),
                ]);

            if ($response->successful() && $response->json()) {
                return new GatekeeperAuthenticatableUser($response->json());
            }

            return null;
        });
    }

    public function retrieveById($identifier) {}
    public function retrieveByCredentials(array $credentials) {}
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }

    public function updateRememberToken(Authenticatable $user, $token) {}

    public function rehashPasswordIfRequired(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials, bool $force = false)
    {
        return $user;
    }

    public function updateSetting(array $data)
    {
        $token =  Gatekeeper::authToken();

        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->put(Gatekeeper::baseUrl() . '/api/user/setting', $data);

        if ($response->successful()) {
            cache()->forget(Gatekeeper::userAuthCacheKey($token));
            return $this->retrieveByToken(null, $token);
        }

        return false;
    }
}
