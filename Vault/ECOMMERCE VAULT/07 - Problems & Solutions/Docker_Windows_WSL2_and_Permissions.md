# Docker Windows WSL2, Architecture & Permissions

Status: VERIFIED
Last Updated: 2026-09-25
Tags: #problem-solution #docker #windows #wsl2

## 1. Problem: "This app can't run on your PC" Installer Error

### Symptom
When executing `Docker Desktop Installer.exe` on Windows 10/11, Windows presents a solid blue modal:
> *"This app can't run on your PC. To find a version for your PC, check with the software publisher."*

### Root Cause
Docker provides separate installers for **x86_64 (AMD64 / Intel)** and **ARM64**. If the ARM64 binary (`Machine Type: 0xAA64`) is mistakenly downloaded onto a standard x86_64 Intel/AMD PC, Windows NT kernel rejects execution due to CPU instruction architecture incompatibility.

### Solution
Download the **x86_64 / AMD64** installer directly:
- **Direct Official AMD64 Link:** `https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe`
- Verify host architecture in PowerShell:
  ```powershell
  $env:PROCESSOR_ARCHITECTURE  # Must return 'AMD64'
  ```

---

## 2. Problem: Windows WSL2 Bind Mount Permissions

### Symptom
On Windows host workstations running Docker Desktop with WSL2 backend, bind mounts from Windows NTFS drives (`C:\...` mounted to `/var/www/html`) can suffer from file permission mismatches (`storage/logs/laravel.log` or `storage/framework/views` not writable by `www-data`).

### Root Cause
Windows NTFS file permissions do not map 1:1 to Linux POSIX file ownership (`uid:gid`). Containers running as `www-data` in Alpine Linux (UID 82) may fail to write to `storage/framework/views` or `storage/logs`.

### Solution / Prevention
1. **Container Entrypoint (`docker/php/entrypoint.sh`):**
   Automatically creates and applies permissive permissions on startup:
   ```bash
   mkdir -p /var/www/html/storage/framework/cache/data \
            /var/www/html/storage/framework/sessions \
            /var/www/html/storage/framework/views \
            /var/www/html/storage/logs \
            /var/www/html/bootstrap/cache
   chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
   ```
2. **Production Multi-Stage (`docker/php/Dockerfile.prod`):**
   Files are copied into the container filesystem directly (`COPY --chown=www-data:www-data . .`), bypassing host NTFS bind mounts entirely.
3. **Execution Commands:**
   Always run artisan commands via `docker compose exec app ...` so commands run inside the Linux container context.

---

## Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
- [[Docker_Local_Development_and_Testing_Workflow]]
