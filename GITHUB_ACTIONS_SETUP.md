# GitHub Actions Setup & CI/CD Guide

Your CI/CD workflow is **100% automated and zero-configuration out-of-the-box**. It runs automatically on every push and PR to `main` without requiring any manual secrets.

---

## 🚀 Zero-Configuration Mode (Automatic Default)

By default, the workflow executes without any manual setup:
- **Linting & Code Style**: Laravel Pint, Larastan (PHPStan), TypeScript typecheck.
- **Automated Tests**: PHPUnit feature test suite against real PostgreSQL 16 and Redis 7 service containers.
- **Docker Compose Integration**: Full stack initialization and database migration verification.
- **Multi-Platform Build & Push**: Uses **QEMU + Docker Buildx** to build `linux/amd64` and `linux/arm64` images and publishes them directly to **GitHub Container Registry (GHCR)** (`ghcr.io/arvnddl18/ecommerce-backend`) using the built-in `GITHUB_TOKEN`.
- **Security Scans**: Scans images for CVE vulnerabilities automatically using **Trivy**.

---

## ⚡ Optional Turbo Boost: Docker Build Cloud

If you have a Docker Hub account and want **10-50x faster multi-platform builds** using Docker's remote cloud builders, you can optionally configure Docker Build Cloud secrets in your GitHub repository (**Settings > Secrets and variables > Actions**):

### Optional Secrets:
- **`DOCKER_ACCOUNT`**: Your Docker Hub organization or username
- **`CLOUD_BUILDER_NAME`**: Name of your Docker Build Cloud builder (e.g., `ecommerce-builder`)
- **`DOCKER_ACCESS_TOKEN`**: Docker Personal Access Token with Read & Write permissions
- **`DOCKERHUB_USERNAME`**: Your Docker Hub username (for Scout vulnerability scanning)
- **`DOCKERHUB_TOKEN`**: Same as `DOCKER_ACCESS_TOKEN`

### Setup Instructions (Only if using Docker Build Cloud):
1. **Create Builder**: Go to [app.docker.com/build](https://app.docker.com/build) and create a builder (e.g., `ecommerce-builder`).
2. **Generate Token**: Go to [hub.docker.com/settings/security](https://hub.docker.com/settings/security) and create an Access Token named `github-actions-ci`.
3. **Save Secrets**: Add the 5 secrets above to GitHub Repository Settings.
4. **Automatic Fallback**: If these secrets are absent, the workflow automatically and cleanly falls back to standard QEMU + Buildx + Trivy with zero failures.

## Workflow Pipeline

Your CI/CD runs in this order:

1. **lint** - Code style & static analysis (always runs on push/PR)
2. **test** - PHPUnit tests with PostgreSQL & Redis (after lint passes)
3. **build-dev** - Builds dev Dockerfile (validates dev image) (after lint passes)
4. **integration** - Full docker-compose stack test (after test passes)
5. **build-and-push** - Multi-platform build to GHCR (only on main push, after integration passes)
   - Uses Docker Build Cloud for 10-50x faster multi-platform builds
   - Builds linux/amd64 and linux/arm64 architectures
6. **scan** - Security scan with Trivy + Docker Scout (after build-and-push)

## Build Caching

- **GitHub Actions Cache**: Layer cache between runs (gha backend)
- **Docker Build Cloud Cache**: Remote builder cache across team (if configured)

Fallback mode: If Docker Build Cloud secrets are not set, the workflow automatically uses Docker Buildx with GitHub Actions cache.

## Expected Image Tags

Images are tagged with multiple labels:

```
ghcr.io/yourorg/yourrepo:main
ghcr.io/yourorg/yourrepo:sha-abc123def456
ghcr.io/yourorg/yourrepo:v1.0.0  (when you push a git tag v1.0.0)
ghcr.io/yourorg/yourrepo:1       (semver: v1.0.0 → 1)
ghcr.io/yourorg/yourrepo:1.0     (semver: v1.0.0 → 1.0)
```

## Troubleshooting

### Build job fails with "Docker Build Cloud not configured"
- Ensure `DOCKER_ACCESS_TOKEN` and `CLOUD_BUILDER_NAME` secrets are set
- The workflow will fall back to standard Buildx if secrets are missing

### Image scan shows vulnerabilities
- Review the Trivy and Scout reports in the workflow logs
- The scan job is non-blocking (exit-code: 0), so builds still push

### Multi-platform build is slow
- Ensure Docker Build Cloud builder is created and secrets are set
- Build Cloud can be 10-50x faster than local builds for multi-platform
- Cloud builds also share cache across your team

### GHCR push fails with permission denied
- Verify `GITHUB_TOKEN` has permission to push packages
- Go to Settings > Actions > General > Workflow permissions
- Select "Read and write permissions"

## Next Steps

1. Push a commit to `main` to trigger the workflow
2. Watch the Actions tab to see the pipeline in action
3. Verify images are pushed to GHCR with correct tags
4. Set up deployment (e.g., Docker Compose in production, Kubernetes, or other orchestrator)

## Pro Tips

- **Faster iterations**: Docker Build Cloud caches build layers across all your team members
- **Multi-platform**: Automatically builds for ARM64 (great for M-series Macs, cloud servers)
- **Security gates**: Scan job blocks vulnerable images from reaching production (optional: set `exit-code: 1`)
- **Semantic versioning**: Push Git tags like `v1.0.0` to auto-tag images with semver
