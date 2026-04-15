# IdentityBridge Implementation

This folder documents how to build the plugin in a way that stays provider-agnostic while remaining practical for a single-provider CakePHP app.

## Recommended Reading Order

1. [user-instructions.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/user-instructions.md)
2. [architecture.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/architecture.md)
3. [contracts.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/contracts.md)
4. [request-flow.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/request-flow.md)
5. [schema-and-mapping.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/schema-and-mapping.md)
6. [build-order.md](/Users/amoreno/ClaudeDesktopDropBox/lopusboard/lopusboard-be/plugins/IdentityBridge/implementation/build-order.md)

## Core Idea

The plugin should not know the host app’s full `User` shape. It should only:

- verify the token
- normalize remote identity
- call a host-app resolver that returns the local user

That keeps provider concerns and application persistence rules cleanly separated while keeping the package API small.
