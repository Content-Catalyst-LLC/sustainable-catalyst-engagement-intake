# Release Procedure — v1.2.1

## Release identity

- Plugin version: `1.2.1`
- Database version: `1.2.0`
- Platform evidence schema: `1.2.1`
- Support schema: `1.0.1`
- Release: Support Operations and Cross-Product Reliability Patch

## Migration

The v1.2.1 migration is nondestructive and idempotent. It records the runtime reliability contract under `v1_2_1_support_operations_cross_product_reliability`. It does not add, remove, or rewrite database tables. Existing v1.2.0 support cases, links, events, signals, inquiries, lifecycle records, portal sessions, documents, and communications are preserved.

## Required validation

1. Lint all plugin and test PHP files.
2. Run every repository test suite.
3. Check JavaScript syntax.
4. Scan the release tree for common secret patterns.
5. Generate and verify the release manifest.
6. Verify installable and repository ZIP CRCs and file parity.
7. Install over v1.2.0 or later on a backed-up WordPress site.
8. Complete the v1.2.1 patch migration repair if requested.
9. Run Live Validation and verify support-case creation, governed triage, portal isolation, signal privacy, relationship creation, handoff replay, and cleanup.
10. Confirm external inbox delivery and repeat controlled public support intake.

## Production gate

Production requires 100% readiness, zero failures, zero warnings, current version-bound live validation, backup, inbox, and pilot evidence, no unresolved support operations blockers, and typed human promotion. Repository tests do not establish live-host readiness.

## Rollback

Retain the prior plugin ZIP, database backup, and protected-storage backup. Because v1.2.1 makes no structural database change, code rollback to v1.2.0 is possible after reviewing any records created while v1.2.1 was active. Do not delete support records as part of rollback.
