# Docker Windows WSL2 and Permissions

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #problem-solution #docker #windows

## Problem
On Windows host workstations running Docker Desktop with WSL2 backend, bind mounts from Windows NTFS drives (`/mnt/c/...` or `C:\...`) can occasionally suffer from file permission mismatches (`storage/logs` not writable by `www-data` UID 82/1000) or slow file notification event propagation.

## Root Cause
Windows NTFS file permissions do not map 1:1 to Linux POSIX file ownership (`uid:gid`). Containers running as `www-data` in Alpine Linux (UID 82) may fail to write to `storage/framework/views` or `storage/logs`.

## Solution / Prevention
1. **In Dockerfile (`Dockerfile.dev`):**
   Ensure `www-data` UID/GID matches the host mapping or grant 775 permissions on `storage` and `bootstrap/cache`:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
2. **In Production (`Dockerfile.prod`):**
   Files are copied into the container filesystem directly (`COPY --chown=www-data:www-data . .`), bypassing host NTFS bind mounts entirely.
3. **Execution Commands:**
   Always run artisan commands via `docker compose exec app ...` so commands run inside the Linux container context.

## Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
