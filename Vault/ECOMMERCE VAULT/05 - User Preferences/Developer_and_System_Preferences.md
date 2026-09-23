# Developer and System Preferences

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #preferences #conventions #standards

## 1. Coding & Code Style Standards
- **Strict Formatting:** Run `vendor/bin/pint --dirty --format agent` after modifying PHP code to ensure PSR-12 and Laravel style compliance.
- **Type Safety & Returns:** Use explicit PHP 8+ return type declarations, constructor property promotion, and PHPDoc blocks for array shapes.
- **Enums:** Use TitleCase for Enum keys (e.g. `OrderStatus::Pending`).
- **No Tinker Scripts as Tests:** Write dedicated PHPUnit feature tests (`tests/Feature/`) with model factories rather than throwaway scratch scripts.

## 2. Architecture & Tooling Preferences
- **Laravel Boost Tools:** Use Boost MCP tools (`database-query`, `database-schema`, `get-absolute-url`, `browser-logs`) when inspecting and developing Laravel features.
- **Container First:** Execute Artisan commands and tests via Docker Compose:
  - `docker compose exec app php artisan ...`
  - `docker compose exec app vendor/bin/phpunit`
- **Secrets Management:** Never commit `.env` or production credentials. Store secrets in environment variables or GitHub Actions Secrets.

## 3. Obsidian Memory Protocols
- Always follow the **Hierarchy of Truth**: Source code is always authoritative over memory notes.
- Use targeted semantic retrieval; never load the entire vault into the context window.
- Consolidate and update existing notes progressively rather than generating fragmented duplicates.
- Mark confidence states (`VERIFIED`, `INFERRED`, `PROPOSED`, `HISTORICAL`, `OBSOLETE`).

## Related Links
- [[CORE_MEMORY]]
- [[Functional_Requirements]]
