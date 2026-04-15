# Request Flow

This is the intended request lifecycle.

## Middleware Sequence

1. Read the `Authorization: Bearer <jwt>` header.
2. Reject the request if the token is missing.
3. Pass the JWT to the configured provider adapter.
4. Receive a `RemoteIdentity` object.
5. Pass that identity to the configured local user resolver.
6. Attach both the remote identity and the resolved local user to the request.
7. Continue the middleware stack.

## Request Attributes

The plugin should attach at least:

- `identityBridge.remoteIdentity`
- `identityBridge.user`

If the host app wants a simpler alias later, it can mirror the local user into something like `identity`.

## Failure Behavior

Authentication failures should be explicit and JSON-safe:

- missing token: `401`
- invalid token: `401`
- provider verification failure: `401`
- local user resolution failure: `500` or `503`, depending on cause

Do not silently continue unauthenticated if the route is supposed to be protected.

## Middleware Placement

Place the middleware after request parsing and before authorization-sensitive app code.

The plugin should not make authorization decisions itself. Its job stops once identity has been resolved.
