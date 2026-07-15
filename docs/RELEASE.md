# Release Procedure — v1.4.1

## Release identity

- Plugin version: `1.4.1`
- Database version: `1.4.0`
- Platform evidence schema: `1.4.1`
- Proposal-governance schema: `1.0.1`
- Release: Proposal Versioning, Approval, and Engagement Conversion Reliability Patch

## Required validation

1. Lint all plugin and test PHP files.
2. Run every repository suite, including executable approval-integrity checks.
3. Check JavaScript syntax and scan for common secret formats.
4. Generate and verify the release manifest and ZIP CRCs.
5. Install over v1.4.0 on a backed-up WordPress site.
6. Verify the base v1.4.0 migration and v1.4.1 patch journal.
7. Run Live Validation and confirm idempotent approval and conversion replay.
8. Test one proposal revision, one SOW approval, one sender proposal response, and one engagement conversion.
9. Confirm no duplicate approval or engagement records are created.

## Production gate

Production requires 100% readiness, zero failures, zero warnings, valid immutable approval hashes, no missing sender or conversion receipts, no stale active SOWs, current version-bound evidence, and typed human promotion.

## Rollback

Retain the v1.4.0 plugin ZIP and current database and protected-storage backups. v1.4.1 adds no tables or columns. Code rollback does not remove v1.4.1 migration evidence; do not delete proposal, SOW, approval, or engagement records during rollback.
