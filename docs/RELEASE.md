# Release Procedure — v1.4.0

## Release identity

- Plugin version: `1.4.0`
- Database version: `1.4.0`
- Platform evidence schema: `1.4.0`
- Proposal-governance schema: `1.0.0`
- Release: Proposals, Statements of Work, and Engagement Approvals

## Required validation

1. Lint all plugin and test PHP files.
2. Run every repository suite.
3. Check JavaScript syntax.
4. Scan for common secret formats.
5. Generate and verify the release manifest.
6. Verify ZIP CRCs and exact plugin parity.
7. Install over v1.3.1 on a backed-up WordPress site.
8. Run database and proposal migration repairs if requested.
9. Run Live Validation and verify cleanup.
10. Complete a controlled proposal revision, SOW approval, sender decision, external contract, engagement conversion, and change request.

## Production gate

Production requires 100% readiness, zero failures, zero warnings, current version-bound validation, backup, inbox and pilot evidence, no unapplied approved change requests, and typed human promotion.

## Rollback

Retain the v1.3.1 plugin ZIP and current database and protected-storage backups. Code rollback does not remove v1.4.0 tables. Do not delete governance records during rollback; restore the backed-up database only through the established recovery process.
