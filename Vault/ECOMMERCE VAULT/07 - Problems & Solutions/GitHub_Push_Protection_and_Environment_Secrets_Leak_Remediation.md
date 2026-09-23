# GitHub Push Protection Secret Leak & Git History Remediation

Status: VERIFIED
Last Updated: 2026-09-24
Tags: #problem-solution #git #security #stripe #push-protection #devops #docker

## Problem
During a `git push` to `origin/main`, GitHub rejected the ref update with an error:
```
remote: error: GH013: Repository rule violations found for refs/heads/main.
remote: - GITHUB PUSH PROTECTION
remote:     Resolve the following violations before pushing again
remote:     - Push cannot contain secrets
remote:       —— Stripe Test API Secret Key ————————————————————————
remote:        locations:
remote:          - commit: d98a25dcb16fb5bdfdf226c435cef144aa53f6cd
remote:            path: .env.docker:67
```
Even though the working tree or subsequent commits may have attempted to remove or overwrite the secret, Git's push mechanism transfers all commit objects in the branch history range (`origin/main..HEAD`). Because the specific local commit object (`d98a25d`) contained the active test secret key, GitHub Secret Scanning blocked the entire push.

## Root Cause
1. **Tracked Environment Template With Live Credentials:** `.env.docker` was originally tracked in Git as a Docker environment template. A developer or script populated `.env.docker` with active Stripe test keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) rather than retaining placeholder samples (`pk_test_sample`, `sk_test_sample`).
2. **Git Commit History Retains Snapshots:** When `git commit` was executed, Git permanently baked the secret key string into commit object `d98a25d`. Simply making a new commit that deletes the secret does not remove it from previous commits; GitHub scans the entire commit chain being pushed.
3. **Incomplete `.gitignore` Coverage for Docker Env Variants:** `.gitignore` ignored `.env`, `.env.backup`, and `.env.production`, but omitted `.env.docker.local`, leading to accidental staging of local docker configs.

## Solution & Remediation
1. **Replaced Real Keys with Safe Placeholders in `.env.docker`:**
   Restored dummy placeholder values in `.env.docker`:
   ```env
   # Stripe Payment Gateway
   STRIPE_KEY=pk_test_sample
   STRIPE_SECRET=sk_test_sample
   STRIPE_WEBHOOK_SECRET=whsec_sample
   STRIPE_CURRENCY=php
   ```
   Active testing secrets are restricted to `.env` (which is excluded from Git via `.gitignore`).
2. **Local History Rewind Without Loss (`git reset --soft`):**
   Because the problematic commit was only present locally and had never been accepted by `origin/main`, a soft reset cleanly restored all working tree and staging changes to the unpushed state without losing any modifications:
   ```bash
   git reset --soft origin/main
   ```
3. **Sanitized Staged Index Verification:**
   Re-staged the cleansed `.env.docker` and verified with regex inspection that zero live Stripe key strings (`sk_test_*`, `whsec_*`) existed across all staged diffs:
   ```bash
   git diff --staged | grep -E "sk_test_|whsec_"
   ```
4. **Enhanced `.gitignore`:**
   Added `.env.docker.local` to `.gitignore` to support local Docker configurations without risking accidental Git staging.
5. **Recommitted Clean Tree:**
   Packaged the complete feature set, tests, and documentation into a single clean commit with verified zero secret exposure.

## Prevention & Best Practices
- **Never Put Real Keys in Tracked Files:** All `.env.*` files tracked in Git must contain ONLY placeholder values (`sample`, `your_key_here`).
- **Leverage `.env.example` Pattern:** Developers copy `.env.example` or `.env.docker` to untracked `.env` or `.env.docker.local`.
- **Pre-Commit Secret Verification:** Run secret scanning or diff grep before committing any `.env` modification.

## Related Links
- [[CORE_MEMORY]]
- [[Payment_Pipeline]]
- [[Developer_and_System_Preferences]]
- [[Completed_Milestones_Log]]
