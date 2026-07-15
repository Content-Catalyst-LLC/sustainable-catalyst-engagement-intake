# Release Procedure — v2.0.0

## Identity

- Plugin version: `2.0.0`
- Database version: `2.0.0`
- Platform evidence schema: `2.0.0`
- Unified platform schema: `2.0.0`
- Migration: `v2_0_0_integrated_advisory_support_institutional_platform`

## Repository gate

Run `tools/package-release.sh`. The gate lints all PHP, runs every repository suite, checks JavaScript syntax, scans for common secrets, regenerates the release manifest, packages both archives, verifies ZIP integrity, and writes SHA-256 checksums.

## Live gate

1. Back up the database and protected storage.
2. Install v2.0.0 and clear all caches.
3. Verify database and migration readiness.
4. Backfill canonical dossiers.
5. Run Live Validation using a monitored external email address.
6. Confirm dossier relationships and timeline include the temporary support, meeting, proposal, engagement, workspace, billing, and communication records.
7. Confirm a private handoff is rejected and a safe replay is idempotent.
8. Confirm all temporary dossier and handoff records are removed.
9. Re-record inbox, backup, pilot, and validation evidence.
10. Require 100%, zero failures, and zero warnings before Production.
