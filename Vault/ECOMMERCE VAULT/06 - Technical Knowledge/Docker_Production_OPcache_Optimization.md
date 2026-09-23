# Docker Production OPcache Optimization

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #knowledge #docker #php #performance

## 1. Why OPcache Matters
By default, PHP parses, compiles, and executes script files on every single HTTP request. In production, OPcache precompiles PHP scripts into shared memory bytecode, boosting throughput by up to 3x–10x and slashing response latency.

## 2. Production OPcache Configuration (`docker/php/opcache.ini`)
```ini
[opcache]
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### Critical Production Directives:
- `opcache.validate_timestamps=0`: In production, files never change dynamically inside the container. Setting this to 0 prevents PHP from checking disk timestamps on every request, eliminating disk I/O.
- `opcache.memory_consumption=256`: Allocates 256MB of RAM for bytecode caching (easily afforded on Oracle Free Tier 24 GB RAM).

## 3. Laravel Framework Optimization
When deploying in production, always run:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
Or simply:
```bash
php artisan optimize
```

## Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
