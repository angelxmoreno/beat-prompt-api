# IdentityBridge Build Instructions

This file is the human-facing build guide for the `IdentityBridge` plugin. Use it as a step-by-step implementation handoff for a junior developer.

The goal is to build a CakePHP plugin that:

- accepts a bearer JWT from the frontend
- verifies that JWT against one configured remote auth provider
- normalizes the remote identity into a stable plugin shape
- hands that identity to the host app
- lets the host app map and resolve the local user
- attaches the local user to the request
- lets controllers access the resolved auth state cleanly

This plugin should support one configured provider per app. It should not try to auto-detect providers per request.

The recommended v1 shape is:

- a shared authenticator service that does the real work
- config-driven middleware that decides which routes authenticate
- a thin component that gives controllers easy access to the resolved auth objects

## Before You Start

Read these docs first:

1. [README.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/README.md)
2. [architecture.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/architecture.md)
3. [contracts.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/contracts.md)

Do not start with provider-specific code. Build the shared plugin contracts first.

## Step 1: Create The Core Types

Create these files:

- `plugins/IdentityBridge/src/ValueObject/RemoteIdentity.php`
- `plugins/IdentityBridge/src/Exception/AuthenticationException.php`
- `plugins/IdentityBridge/src/Exception/ConfigurationException.php`

Purpose of each class:

- `RemoteIdentity`: immutable normalized identity object shared across the plugin
- `AuthenticationException`: exception for invalid or unverifiable tokens
- `ConfigurationException`: exception for invalid plugin or provider setup

What to build:

- `RemoteIdentity` should be an immutable value object.
- It should represent the normalized result of a verified remote user.
- It should not depend on any one provider's raw payload shape.

Minimum `RemoteIdentity` fields:

- `provider`
- `providerUserId`
- `email`
- `emailVerified`
- `displayName`
- `avatarUrl`
- `claims`

Acceptance criteria:

- the object can be constructed with all expected fields
- the object exposes readonly properties or equally simple accessors
- there is no provider-specific logic in this class

## Step 2: Define The Contracts

Create these files:

- `plugins/IdentityBridge/src/Provider/ProviderInterface.php`
- `plugins/IdentityBridge/src/Resolver/LocalUserResolverInterface.php`

Purpose of each class:

- `ProviderInterface`: contract for JWT verification plus remote identity normalization
- `LocalUserResolverInterface`: host-app contract for mapping normalized identity and returning an `ArrayAccess`-compatible local user object

What to build:

- `ProviderInterface` verifies a JWT and returns `RemoteIdentity`
- `LocalUserResolverInterface` returns the local app user after the host app maps and resolves it

Acceptance criteria:

- contracts are small and clear
- method names communicate responsibility
- interfaces depend on `RemoteIdentity`, not raw arrays from providers

Note:

- if you already completed steps 1 and 2, do not recreate these files later
- there should be only one `LocalUserResolverInterface`

## Step 3: Define The Plugin Config Shape

Before building middleware, write down the exact config the plugin will support.

You do not need code first. A small documented array shape is enough.

Suggested config shape:

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

Important syntax note:

- `Api/Health/index` means `prefix/controller/action`
- it is not a URL
- it is not a named route

This v1 format is just a simple route-target matcher for middleware config.

Override rules:

- `true` means protected
- `false` means public
- any route not listed in `overrides` falls back to `mode`

What this config is for:

- the middleware uses it to decide whether the current route should authenticate

What this config is not for:

- the shared authenticator service does not need route config
- the provider contract does not need route config

Acceptance criteria:

- you can clearly answer how a route becomes public or protected
- the config is simple enough for a normal CakePHP app team to understand quickly

## Step 4: Build The Shared Authenticator Service

Create:

- `plugins/IdentityBridge/src/Service/IdentityAuthenticator.php`
- optionally `plugins/IdentityBridge/src/ValueObject/AuthenticatedRequestIdentity.php`

Purpose of each class:

- `IdentityAuthenticator`: plugin-owned service that performs token verification and host-app user resolution
- `AuthenticatedRequestIdentity`: optional value object that bundles `RemoteIdentity` plus the resolved local user

What `IdentityAuthenticator` should do:

1. accept a bearer token string
2. call the configured provider
3. receive `RemoteIdentity`
4. call the configured `LocalUserResolverInterface`
5. return both the `RemoteIdentity` and the resolved local user

Important:

- put the real auth logic here, not in middleware and not in the component
- middleware and the component should stay thin adapters over this service
- the host app still owns mapping and persistence through `LocalUserResolverInterface`
- this service can be built before concrete plugin config exists

Why the order still works:

- the service only depends on `ProviderInterface` and `LocalUserResolverInterface`
- for tests, use fake implementations of those interfaces
- route config only matters once middleware exists

Acceptance criteria:

- one method can authenticate a token end to end
- token verification and local-user resolution are wired in one place
- middleware and component can both reuse the same service later

## Step 5: Build The Middleware

Create:

- `plugins/IdentityBridge/src/Middleware/IdentityBridgeMiddleware.php`

Purpose of this class:

- `IdentityBridgeMiddleware`: route-aware middleware that decides whether a request should authenticate and, if needed, stores the resolved auth state on the request

What it should do:

1. run after Cake routing so route params are available
2. inspect plugin config to decide whether this route should authenticate
3. if the route is skipped, continue immediately
4. if the route is protected, extract the bearer token
5. call `IdentityAuthenticator`
6. attach both the remote identity and local user to the request
7. continue to the next middleware

Request attributes to set:

- `identityBridge.remoteIdentity`
- `identityBridge.user`

Route matching guidance:

- match on `prefix/controller/action`
- do not match on raw URL strings if you can avoid it
- keep the first version simple and predictable

Do not:

- put provider verification logic directly in the middleware
- make the middleware depend on controller state
- use route metadata in v1

Acceptance criteria:

- protected routes return `401` when the token is missing or invalid
- skipped routes do not attempt auth
- protected routes with valid tokens attach both auth objects to the request

## Step 6: Build The Controller Component

Create:

- `plugins/IdentityBridge/src/Controller/Component/IdentityBridgeComponent.php`

Purpose of this class:

- `IdentityBridgeComponent`: thin controller adapter that exposes the resolved auth state to controllers

What it should do:

- read `identityBridge.remoteIdentity` from the request
- read `identityBridge.user` from the request
- expose convenience methods such as:
  - `getRemoteIdentity()`
  - `getUser()`
  - `hasUser()`
  - `requireUser()`

Important:

- the component should not authenticate tokens itself
- the component should not decide which routes are public or protected
- it is only a controller-facing accessor layer

Acceptance criteria:

- controllers can get the normalized identity without reading raw request attributes
- controllers can get the resolved local user without reading raw request attributes
- missing required auth can be surfaced cleanly through `requireUser()`

## Step 7: Register Plugin Services

Update:

- `plugins/IdentityBridge/src/IdentityBridgePlugin.php`

Purpose of this class:

- `IdentityBridgePlugin`: plugin entrypoint for container registrations and optional middleware wiring

What to add:

- container registrations for the provider implementation
- container registration for the local user resolver
- container registration for `IdentityAuthenticator`
- middleware registration if the plugin is going to self-register it
- component registration if needed by Cake conventions

Important:

- keep the plugin generic
- the host app should be able to swap provider and local user resolver implementations through configuration or DI bindings
- middleware configuration should be easy for the host app to override
- the simplest v1 shape is config-backed class names for `provider` and `resolver`
- the plugin can pass `IdentityBridge.providerConfig` into the provider constructor as a single array
- if the host app needs a different provider constructor shape, it should override the provider binding in `Application::services()`

Acceptance criteria:

- the plugin can resolve all required services from the container
- there is a clear place for the host app to override provider and resolver bindings
- middleware can receive its auth config cleanly

## Step 8: Implement One Real Provider

Do not implement all providers first.

Pick one provider and prove the plugin works end to end. Good first choices:

- Appwrite
- Clerk
- Supabase
- Firebase

Create one file such as:

- `plugins/IdentityBridge/src/Provider/AppwriteProvider.php`

Purpose of this class:

- `<ProviderName>Provider`: provider-specific adapter that verifies tokens and converts claims into `RemoteIdentity`

What it should do:

- verify the JWT
- extract the user identity
- normalize provider-specific claims into `RemoteIdentity`

Important:

- this is the only layer that should know provider claim names
- if the provider supports JWKS verification, prefer that over per-request remote fetches unless the provider requires otherwise

Acceptance criteria:

- valid provider token returns `RemoteIdentity`
- expired or invalid token throws `AuthenticationException`

## Step 9: Add Tests As You Build

Add tests for each layer while building it.

Recommended test files:

- `tests/TestCase/ValueObject/RemoteIdentityTest.php`
- `tests/TestCase/Provider/...`
- `tests/TestCase/Service/IdentityAuthenticatorTest.php`
- `tests/TestCase/Middleware/IdentityBridgeMiddlewareTest.php`
- `tests/TestCase/Controller/Component/IdentityBridgeComponentTest.php`

What to test:

- `RemoteIdentity` stores expected values
- provider adapter normalizes claims correctly
- `IdentityAuthenticator` passes verified identity into the resolver correctly
- middleware respects the route config and authenticates only when expected
- component returns the resolved auth state correctly

Do not leave the middleware and component untested. Those are the main framework integration points.

## Step 10: Define The Host App Resolution Contract

Once the plugin core works, document what the host app must provide.

At minimum, the host app must define:

- which provider to use
- how the host app resolves the local user from `RemoteIdentity`
- how the host app maps `RemoteIdentity` into the local user shape
- which local fields are immutable
- which local fields may be refreshed from remote identity
- which routes are public vs protected in middleware config

If the app still uses `appwrite_id`, call that out clearly. The current app schema is too provider-specific for a reusable bridge plugin.

## Step 11: Integrate Into The Host App

After the plugin works in isolation:

1. update the host app user schema if needed
2. load the plugin
3. register the middleware after routing
4. add middleware config for public vs protected routes
5. load the component in controllers that need auth-state access
6. remove old provider-specific auth code from the app
7. verify authenticated requests resolve a local user

Do not mix old and new auth flows longer than necessary.

## Definition Of Done

The plugin is done when all of these are true:

- a request with a valid bearer token resolves a verified `RemoteIdentity`
- the plugin passes that identity into the host app’s local user resolver correctly
- protected routes are enforced by middleware config
- the request carries the resolved local user for downstream app code
- controllers can access the resolved auth state through the component
- invalid tokens return `401`
- tests cover provider verification, authenticator behavior, middleware behavior, and component behavior

## Suggested Delivery Order

Use this commit order:

1. `feat: add identity bridge core value objects and contracts`
2. `feat: add identity bridge authenticator service`
3. `feat: add identity bridge auth middleware`
4. `feat: add identity bridge controller component`
5. `feat: register identity bridge services in plugin container`
6. `feat: add <provider> adapter for identity bridge`
7. `test: add identity bridge framework integration coverage`
8. `docs: document host app integration for identity bridge`

## Common Mistakes To Avoid

- putting provider-specific claim parsing in middleware
- letting the plugin depend directly on the host app `UsersTable` shape
- treating local-user lookup and persistence as package-owned logic
- using raw claim arrays everywhere instead of `RemoteIdentity`
- making controllers talk backward to middleware
- blending authentication and authorization
- trying to support all providers before the first one works end to end
