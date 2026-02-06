<?php

namespace Rifkiard\Gatekeeper;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Http;

class GatekeeperUserProvider implements UserProvider
{
    public function retrieveByToken($identifier, $token)
    {
        $cacheKey = 'gk_user_' . md5($token);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($token) {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withToken($token)
                ->get(config('services.gatekeeper.base_url') . '/api/user');

            if ($response->successful()) {
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

    public function updateSetting($token, array $data)
    {
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->withToken($token)
            ->put(config('services.gatekeeper.base_url') . '/api/user/setting', $data);

        if ($response->successful()) {
            \Illuminate\Support\Facades\Cache::forget('gk_user_' . md5($token));
            return $this->retrieveByToken(null, $token);
        }

        return false;
    }
}
