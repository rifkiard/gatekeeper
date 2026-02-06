# Gatekeeper - Laravel SSO Authentication Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A Laravel package for Single Sign-On (SSO) authentication using AstraWorld Gatekeeper service. This package provides seamless integration with Laravel's authentication system through a custom guard and Socialite provider.

## Features

- 🔐 Custom authentication guard for AstraWorld Gatekeeper
- 🔄 Laravel Socialite integration
- 👤 Automatic user retrieval and caching
- 🎯 Stateful authentication support
- ⚡ Easy configuration and setup

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0

## Installation

Install the package via Composer:

```bash
composer require rifkiard/gatekeeper
```

The package will automatically register its service provider.

## Configuration

### 1. Add Gatekeeper configuration to `config/services.php`:

```php
'gatekeeper' => [
    'base_url' => env('GATEKEEPER_BASE_URL', 'https://gatekeeper.example.com'),
    'client_id' => env('GATEKEEPER_CLIENT_ID'),
    'client_secret' => env('GATEKEEPER_CLIENT_SECRET'),
    'redirect' => env('GATEKEEPER_REDIRECT_URI'),
],
```

### 2. Update your `.env` file:

```env
GATEKEEPER_BASE_URL=https://your-gatekeeper-url.com
GATEKEEPER_CLIENT_ID=your-client-id
GATEKEEPER_CLIENT_SECRET=your-client-secret
GATEKEEPER_REDIRECT_URI=https://your-app.com/auth/callback
```

### 3. Configure the authentication guard in `config/auth.php`:

```php
'guards' => [
    'web' => [
        'driver' => 'gatekeeper-guard',
        'provider' => 'gatekeeper',
    ],
],

'providers' => [
    'gatekeeper' => [
        'driver' => 'gatekeeper',
    ],
],
```

## Usage

### Basic Authentication Flow

#### 1. Redirect to Gatekeeper login:

```php
use Laravel\Socialite\Facades\Socialite;

Route::get('/login', function () {
    return Socialite::driver('gatekeeper')->redirect();
});
```

#### 2. Handle the callback:

```php
Route::get('/auth/callback', function () {
    $user = Socialite::driver('gatekeeper')->user();

    // Store the access token in session
    session(['gatekeeper_token' => $user->token]);

    return redirect('/dashboard');
});
```

#### 3. Access authenticated user:

```php
Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    return view('dashboard', compact('user'));
});
```

### Logout

```php
Route::post('/logout', function () {
    auth()->logout();
    session()->forget('gatekeeper_token');

    return redirect('/');
});
```

### Checking Authentication

```php
if (auth()->check()) {
    // User is authenticated
    $user = auth()->user();
}

if (auth()->guest()) {
    // User is not authenticated
}
```

## User Object

The authenticated user object contains dynamic properties returned from the Gatekeeper service. Only `id` and `email` are guaranteed, while other properties depend on your Gatekeeper API response:

```php
// Guaranteed properties:
$user->id;              // User ID (required)
$user->email;           // User email (required)

// Dynamic properties (depends on Gatekeeper API response):
$user->name;            // User name (if available)
$user->username;        // Username (if available)
$user->role;            // User role (if available)
$user->setting;         // User settings (optional, object/array)
// ... any other properties from your Gatekeeper API
```

**Note:** The user object dynamically assigns all properties returned by the Gatekeeper `/api/user` endpoint, so you can access any field your API provides without modifying the package.

## Advanced Usage

### Custom Middleware

You can create custom middleware to protect routes:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureGatekeeperAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        return $next($request);
    }
}
```

### Manual Token Verification

```php
use Illuminate\Support\Facades\Auth;

$token = session('gatekeeper_token');

if ($token) {
    $user = Auth::guard('web')->setToken($token)->user();
}
```

## How It Works

1. **Socialite Provider**: Handles OAuth2 flow with Gatekeeper service
2. **Custom Guard**: Manages authentication state using session tokens
3. **User Provider**: Fetches and caches user data from Gatekeeper API
4. **Authenticatable Model**: Provides a lightweight user model for authentication

## Caching

User data is automatically cached for 5 minutes (300 seconds) to reduce API calls to the Gatekeeper service. The cache is invalidated when:

- User logs out
- Cache expires (TTL)

## Security

- All API requests to Gatekeeper use bearer token authentication
- SSL verification can be configured (currently disabled for development)
- Tokens are stored securely in Laravel sessions

## Troubleshooting

### "base_url is missing" error

Make sure you've configured `GATEKEEPER_BASE_URL` in your `.env` file and added the configuration to `config/services.php`.

### Authentication not persisting

Ensure your session driver is properly configured in `config/session.php` and session middleware is active.

### User data not updating

Clear the cache to force fresh data from Gatekeeper:

```php
Cache::forget('gk_user_' . md5($token));
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Author

**Rifki Ardiansyah**

- Email: rifki.ardiansyah@ai.astra.co.id
- GitHub: [@rifkiard](https://github.com/rifkiard)

## Support

For issues, questions, or suggestions, please open an issue on the [GitHub repository](https://github.com/rifkiard/gatekeeper).
