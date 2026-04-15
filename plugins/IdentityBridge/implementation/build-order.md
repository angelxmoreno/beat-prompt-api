# Build Order

Build the plugin in this order so dependencies land first.

## 1. Core Value Object And Exceptions

Create:

- `src/ValueObject/RemoteIdentity.php`
- `src/Exception/AuthenticationException.php`
- `src/Exception/ConfigurationException.php`

Goal:
- establish the stable internal shape of verified identity

Class purposes:
- `RemoteIdentity`: immutable normalized identity returned by provider adapters
- `AuthenticationException`: signals invalid, expired, or unverifiable tokens
- `ConfigurationException`: signals broken plugin or provider configuration

## 2. Contracts

Create:

- `src/Provider/ProviderInterface.php`
- `src/Resolver/LocalUserResolverInterface.php`

Goal:
- define the only two contracts the package needs for provider verification and host-app user resolution

Class purposes:
- `ProviderInterface`: verifies a JWT and returns `RemoteIdentity`
- `LocalUserResolverInterface`: host-app contract for mapping `RemoteIdentity` plus finding, creating, or updating the local user

## 3. Middleware

Create:

- `src/Middleware/IdentityBridgeMiddleware.php`

Goal:
- verify incoming tokens and attach the resolved local user to the request

Class purpose:
- `IdentityBridgeMiddleware`: orchestrates token extraction, provider verification, local user resolution, and request attribute assignment

## 4. Container Wiring

Update:

- `src/IdentityBridgePlugin.php`

Goal:
- register provider and local user resolver dependencies cleanly

## 5. First Provider Adapter

Create one provider first, not all four.

Examples:
- `src/Provider/AppwriteProvider.php`
- `src/Provider/ClerkProvider.php`

Goal:
- prove the contract works end-to-end with one real provider

Class purpose:
- `<ProviderName>Provider`: contains provider-specific JWT verification and claim normalization

## 6. Tests

Add:

- unit tests for the provider adapter
- unit tests for the host app’s local user resolver implementation
- middleware/integration tests for authenticated requests

Goal:
- keep verification and local-user resolution behavior stable

## 7. Host App Integration

After the plugin is working:

- update the app schema if needed
- wire the plugin middleware into the API stack
- remove old provider-specific auth code from the app
