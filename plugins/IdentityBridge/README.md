# IdentityBridge

IdentityBridge is a CakePHP plugin for applications that delegate user authentication to a single external identity provider such as Supabase, Firebase, Clerk, or Appwrite.

The frontend owns login state and sends a bearer token with each request. The backend uses IdentityBridge to:

1. read the incoming JWT
2. verify it against the configured provider
3. fetch or normalize the remote user
4. pass the normalized identity into the host app
5. let the host app map and resolve the local user record
6. attach the resolved local user to the request

This plugin is intentionally built around one configured provider per app. It does not try to detect the provider per request.

## Goals

- keep remote identity concerns outside the host application
- support provider-specific adapters behind one plugin contract
- keep the plugin API small enough for fast adoption
- make local authorization independent from remote authentication

## Non-Goals

- frontend login UI
- session-based CakePHP authentication
- multi-provider routing inside a single app
- project membership or application authorization rules

## Proposed Shape

- `Middleware/IdentityBridgeMiddleware.php`
  Verifies the bearer token and resolves the authenticated local user.
- `Provider/ProviderInterface.php`
  Contract for verifying a token and returning normalized remote identity data.
- `Resolver/LocalUserResolverInterface.php`
  Host-app contract for mapping remote identity and finding, creating, or updating an `ArrayAccess`-compatible local user object.
- `ValueObject/RemoteIdentity.php`
  Normalized identity returned by provider adapters.

## Host App Responsibilities

- choose one provider adapter
- provide provider-specific configuration
- set `IdentityBridge.provider` to the provider class name
- provide `IdentityBridge.providerConfig` for the provider constructor
- implement `LocalUserResolverInterface`
- set `IdentityBridge.resolver` to the resolver class name
- use the resolved local user for authorization and domain rules

## Route Matching Note

When the plugin docs show values like `Api/Health/index`, that syntax means:

- `prefix/controller/action`

It is:

- not a URL like `/api/health`
- not a named route

The first version of the plugin should use this controller-target style because it is simple and does not require every app to define named routes up front.

## Route Protection Config

The recommended v1 config shape is:

```php
[
    'provider' => App\Auth\ClerkProvider::class,
    'providerConfig' => [
        'jwksUrl' => env('CLERK_JWKS_URL'),
        'issuer' => env('CLERK_ISSUER'),
    ],
    'resolver' => App\Auth\AppUserResolver::class,
    'mode' => AuthenticationMode::ProtectedByDefault->value,
    'overrides' => [
        'Api/Auth/*' => false,
        'Api/Health/index' => false,
    ],
]
```

A fuller host-app example in `config/app_local.php` would look like:

```php
<?php
declare(strict_types=1);

use IdentityBridge\Enum\AuthenticationMode;

return [
    'IdentityBridge' => [
        'provider' => App\Auth\ClerkProvider::class,
        'providerConfig' => [
            'jwksUrl' => env('CLERK_JWKS_URL'),
            'issuer' => env('CLERK_ISSUER'),
        ],
        'resolver' => App\Auth\AppUserResolver::class,
        'mode' => AuthenticationMode::ProtectedByDefault->value,
        'overrides' => [
            'Api/Auth/*' => false,
            'Api/Health/index' => false,
            'Api/Webhooks/identity' => false,
            'Api/Issues/*' => true,
        ],
    ],
];
```

Rules:

- `provider` is the configured `ProviderInterface` implementation class
- `providerConfig` is passed to the provider constructor as a single config array
- `resolver` is the configured `LocalUserResolverInterface` implementation class
- `mode` sets the default behavior for every route
- `overrides` replaces that default for specific `prefix/controller/action` targets
- `true` means protected
- `false` means public

If the host app needs a custom provider constructor or more complex dependencies, it can still override the provider binding in `Application::services()`.

## Implementation Docs

Build notes live in [implementation/README.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/README.md).
