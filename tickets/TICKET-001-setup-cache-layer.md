# TICKET-001: Setup Cache Layer

## Description
Implement the cache bootstrap for the CakePHP API as defined in the project overview. The cache layer should be environment-driven using a DSN-like `CACHE_URL` value.

## Tasks
- [ ] Create a cache factory or adapter resolver (e.g., `Cache\CacheFactory`) that reads `CACHE_URL` from the environment.
- [ ] Configure SQLite-backed caching for local development (`sqlite://./data/dbcache.sqlite`).
- [ ] Configure Redis/Valkey-backed caching for production (`redis://user:pwd@host:6379`).
- [ ] Implement support for an optional `CACHE_NAMESPACE` (e.g., `beatprompt`).
- [ ] Create cache key builder utilities to support namespaces (e.g., `intent_key|...`, `artist_profile|...`, `final_prompt|...`).

## Acceptance Criteria
- The application can dynamically select the cache backend based on the `CACHE_URL` scheme without code changes.
- Caching configuration adheres to CakePHP conventions while satisfying the DSN requirement.