# CI/CD: NPM Lockfile Sync, Node 22 Engine & Static Analysis Fixes

Status: VERIFIED
Last Updated: 2026-09-25
Tags: #problem-solution #ci-cd #npm #typescript #larastan #pint #nodejs

## Problem
In GitHub Actions CI/CD Pipeline run `#5` (commit `23eaac4`), the workflow failed in 33 seconds on the `lint` job during the `Install NPM Dependencies` step (`npm ci`):
```text
npm warn EBADENGINE Unsupported engine {
npm warn EBADENGINE   package: 'concurrently@10.0.5',
npm warn EBADENGINE   required: { node: '>=22' },
npm warn EBADENGINE   current: { node: 'v20.20.2', npm: '10.8.2' }
npm warn EBADENGINE }
npm error code EUSAGE
npm error `npm ci` can only install packages when your package.json and package-lock.json are in sync.
npm error Missing: @types/react@19.3.0 from lock file
```

---

## Root Causes
1. **Conflicting Optional CLI Dependency (`@laravel/multiplex`):**
   - `package.json` included `@laravel/multiplex` under `optionalDependencies`.
   - `@laravel/multiplex` depended on `ink@7.1.1`, which enforced peer dependencies on React 19 (`react: >=19.2.0`, `@types/react: >=19.2.0`).
   - The application root runs React 18 (`react@^18.3.1`, `@types/react@^18.3.31`). When `npm ci` resolved peer trees in CI, it detected `@types/react@19.3.0` missing from `package-lock.json` and halted with `EUSAGE`.
2. **Node.js Engine Incompatibility (`concurrently@10.0.5`):**
   - CI had `actions/setup-node@v4` set to `node-version: 20`. `concurrently@10.0.5` requires Node.js `>=22`.
3. **Subsequent Upstream Blockers Caught Pre-Push:**
   - **TypeScript (`tsc --noEmit`):** `resources/js/components/ProductGrid.tsx` declared state as `'all' | 'in_stock' | 'under_150'`, but code checked and set `'under_80'`, triggering TS2367 and TS2345 type errors.
   - **Larastan (`phpstan`):** `SellerController.php` dynamically assigned attributes (`order_number`, `order_status`, `sku`, etc.) on `OrderItem` instances without class PHPDoc property annotations, and used redundant nullsafe operators.

---

## Solution & Remediation
1. **Pruned Unused Dependency & Regenerated Lockfile:**
   - Removed `"@laravel/multiplex"` from `package.json`.
   - Executed `npm install` to regenerate `package-lock.json` cleanly without React 19 peer conflicts.
   - Verified with local `npm ci` (audited 73 packages, 0 vulnerabilities).
2. **Upgraded to Node.js 22 LTS in CI/CD:**
   - Updated `node-version: 22` in `.github/workflows/ci.yml` across both `lint` and `test` jobs.
   - Updated Stage 1 of `docker/php/Dockerfile.prod` to `FROM node:22-alpine AS frontend`.
3. **Fixed TypeScript Type Mismatch:**
   - Updated `activeFilter` union type in `ProductGrid.tsx` to `'all' | 'in_stock' | 'under_80'`.
   - Verified `npx tsc --noEmit` exited with code 0.
4. **Resolved Larastan Static Analysis Errors:**
   - Added class-level `@property` PHPDoc annotations to `app/Models/OrderItem.php`.
   - Safely accessed `variant_details` in `SellerController.php` and cleaned up property access.
   - Verified `vendor/bin/phpstan analyse --memory-limit=1G` exited with 0 errors.
   - Verified `vendor/bin/pint --format agent` formatted all PHP files cleanly.
5. **Verified Vite Production Bundle:**
   - Ran `npm run build` locally, successfully compiling assets in ~9s.

---

## Related Links
- [[CORE_MEMORY]]
- [[GitHub_Actions_CI_CD_Automation_and_Zero_Config_Fallback]]
- [[Docker_Local_Development_and_Testing_Workflow]]
