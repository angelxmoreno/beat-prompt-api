# Contracts

These are the key contracts the plugin should expose.

## Provider Interface

Purpose: verify a token and return normalized identity.

Suggested shape:

```php
interface ProviderInterface
{
    public function verify(string $jwt): RemoteIdentity;
}
```

The provider should throw a domain-specific authentication exception when verification fails.

## Local User Resolver Interface

Purpose: hand normalized identity to the host app and get back the resolved local user.

Suggested shape:

```php
interface LocalUserResolverInterface
{
    public function resolve(RemoteIdentity $identity): ArrayAccess;
}
```

This interface should be implemented by the host application, not by the package.
The host app decides how to map, look up, create, or update its local user.

## Remote Identity Value Object

Suggested fields:

- `provider`
- `providerUserId`
- `email`
- `emailVerified`
- `displayName`
- `avatarUrl`
- `claims`

Keep it immutable. The rest of the plugin should depend on this shape instead of provider-specific arrays.
