<?php

namespace Rifkiard\Gatekeeper;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class GatekeeperGuard implements StatefulGuard
{
    protected $name;
    protected $provider;
    protected $request;
    protected $user;

    public function __construct(UserProvider $provider, Request $request, $name = 'gatekeeper')
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Ambil user yang sedang login.
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        // Ambil gatekeeper_token yang disimpan di session pas callback SSO
        $token = $this->request->session()->get('gatekeeper_token');

        if ($token) {
            // Panggil GatekeeperUserProvider->retrieveByToken
            $user = $this->provider->retrieveByToken(null, $token);

            if ($user) {
                $this->user = $user;
                return $this->user;
            }
        }

        return null;
    }

    /**
     * Manual login user ke dalam session.
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $this->user = $user;
        // Biasanya token disimpan manual di controller saat callback
    }

    /**
     * Logout user dan hapus session.
     */
    public function logout()
    {
        $this->user = null;
        $this->request->session()->forget('gatekeeper_token');
        $this->request->session()->invalidate();
        $this->request->session()->regenerateToken();
    }

    // --- Method Wajib Interface StatefulGuard (Isi minimal agar Fortify tidak komplain) ---

    public function check()
    {
        return !is_null($this->user());
    }
    public function guest()
    {
        return !$this->check();
    }
    public function id()
    {
        return $this->user() ? $this->user()->getAuthIdentifier() : null;
    }

    public function attempt(array $credentials = [], $remember = false)
    {
        return false;
    }
    public function validate(array $credentials = [])
    {
        return false;
    }
    public function hasUser()
    {
        return !is_null($this->user);
    }
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    public function loginUsingId($id, $remember = false)
    {
        return false;
    }
    public function once(array $credentials = [])
    {
        return false;
    }
    public function onceUsingId($id)
    {
        return false;
    }
    public function viaRemember()
    {
        return false;
    }

    // Method untuk Laravel 11+
    public function forgetUser()
    {
        $this->user = null;
    }

    public function getProvider()
    {
        return $this->provider;
    }
}
