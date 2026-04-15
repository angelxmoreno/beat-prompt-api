# Schema And Mapping

The current app-level `users` table looks Appwrite-specific because it uses `appwrite_id`. That is too narrow for a reusable remote-identity plugin.

## Recommended Local Identity Fields

For a single-provider app, the simplest direction is:

- `auth_provider`
- `provider_user_id`
- `email`
- `name`
- `avatar_url`

Then enforce uniqueness on:

- `auth_provider + provider_user_id`
- optionally `email`, if your product requires it

## Better Long-Term Option

If you expect one local user to link to multiple providers later, use a separate identity table:

- `users`
- `user_identities`

Example `user_identities` fields:

- `user_id`
- `provider`
- `provider_user_id`
- `email`
- `claims_json`
- `last_seen`

That is more flexible, but it is more than you need if each app will always use one provider.

## Mapping Rule

The plugin should not hardcode:

- which remote claim becomes the local name
- whether email is required
- whether avatar URLs are persisted
- how the local user is looked up
- when the local user is created
- which local fields are updated later

Those decisions belong in the host app implementation of `LocalUserResolverInterface`.

## Update Policy

Decide early which fields are:

- immutable after first sync
- refreshed on every request
- refreshed only on sign-in

Good defaults:

- provider id: immutable
- email: update if changed
- display name: update if changed
- avatar url: update if changed
