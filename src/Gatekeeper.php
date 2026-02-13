<?php

namespace Rifkiard\Gatekeeper;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Gatekeeper
{
    public static function baseUrl()
    {
        return config('services.gatekeeper.base_url');
    }

    public static function clientId()
    {
        return config('services.gatekeeper.client_id');
    }

    public static function authToken()
    {
        return session()->get('gk_token');
    }


    public static function userAuthCacheKey($token)
    {
        return self::clientId() . ":auth:" . md5($token);
    }

    public static function appCacheKey()
    {
        return self::clientId() . ":app";
    }

    public static function permissionsCacheKey($token)
    {
        return   self::clientId() . ':perm:' . md5($token);
    }

    public static function permissions()
    {
        return collect(Cache::remember(self::permissionsCacheKey(Auth::id()), 300, function () {

            $response = Http::withoutVerifying()
                ->withToken(self::authToken())
                ->withHeader("X-GK-Client-ID", self::clientId())
                ->get(self::baseUrl() . '/api/user/permissions');

            if ($response->successful()) {
                return $response->object();
            }

            return [];
        }));
    }

    public static function logout()
    {
        $token = Gatekeeper::authToken();

        if ($token) {
            cache()->forget(Gatekeeper::userAuthCacheKey($token));
            cache()->forget(Gatekeeper::permissionsCacheKey($token));
        }

        session()->forget('gk_token');
        session()->invalidate();
        session()->regenerateToken();
    }

    public static function application(?string $clientId = null)
    {

        return Cache::remember(self::appCacheKey(), 300, function () use ($clientId) {
            $http = Http::withOptions([
                'verify' => false,
            ]);

            $response = $http->get(
                config('services.gatekeeper.base_url') . '/api/application',
                ['client_id' => $clientId]
            );

            if ($response->successful()) {
                return $response->object();
            }

            return null;
        });
    }

    public static function hasPermission(string $permission): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $arrTarget = explode('.', $permission);

        if (count($arrTarget) != 2) {
            return false;
        }

        $permissions = self::permissions();

        return $permissions->contains(function ($perm) use ($arrTarget) {
            return $perm->key == $arrTarget[0] && $perm->{$arrTarget[1]} == true;
        });
    }
}
