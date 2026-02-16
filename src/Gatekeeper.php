<?php

namespace Rifkiard\Gatekeeper;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Socialite;

class Gatekeeper
{
    public static function setAuthToken($token)
    {
        session()->put('gk_token', $token);
    }

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

    public static function userAppsCacheKey($token)
    {
        return self::clientId() . ":user_apps:" . md5($token);
    }

    public static function permissionsCacheKey($token)
    {
        return   self::clientId() . ':perm:' . md5($token);
    }

    public static function permissions()
    {
        $token = self::authToken();

        if (!$token) {
            return collect([]);
        }

        return collect(Cache::remember(self::permissionsCacheKey($token), 300, function () use ($token) {

            $response = Http::withoutVerifying()
                ->withToken($token)
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

    public static function application()
    {

        return Cache::remember(self::appCacheKey(), 300, function () {
            $http = Http::withOptions([
                'verify' => false,
            ]);

            $response = $http->get(
                self::baseUrl() . '/api/application',
                ['client_id' => self::clientId()]
            );

            if ($response->successful()) {
                return $response->object();
            }

            return null;
        });
    }

    public static function userApplications()
    {
        $token =  Gatekeeper::authToken();
        return Cache::remember(self::userAppsCacheKey($token), 300, function () use ($token) {
            $http = Http::withOptions([
                'verify' => false,
            ]);

            $response = $http->withToken($token)->get(
                self::baseUrl() . '/api/user/applications',
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

    public static function redirect()
    {
        return Socialite::driver('gatekeeper')
            ->redirect();
    }

    public static function user()
    {
        return Socialite::driver('gatekeeper')
            ->user();
    }

    public static function callback()
    {
        $user = Socialite::driver('gatekeeper')
            ->user();
        self::setAuthToken($user->token);
    }

    public static function updateSetting(array $data)
    {
        $token =  Gatekeeper::authToken();

        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->withToken($token)
            ->put(Gatekeeper::baseUrl() . '/api/user/setting', $data);

        if ($response->successful()) {
            cache()->forget(Gatekeeper::userAuthCacheKey($token));
            cache()->remember(Gatekeeper::userAuthCacheKey($token), 300, function () use ($token) {
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

        return false;
    }
}
