# Release Procedure — v1.3.0

## Release identity

- Plugin version: `1.3.0`
- Database version: `1.3.0`
- Platform evidence schema: `1.3.0`
- Portal schema: `1.5.0`
- Workflow schema: `1.2.0`
- Calendar schema: `1.0.0`
- Support schema: `1.0.1`
- Release: Microsoft Teams and Calendar Coordination

## Migration

The v1.3.0 migration is nondestructive and idempotent. It expands the existing meeting-offer table, adds the meeting-reminder table, records `v1_3_0_microsoft_teams_calendar_coordination`, and preserves all existing inquiry, lifecycle, support, portal, document, proposal, engagement, communication, and Microsoft Graph records.

## Required validation

1. Lint all plugin and test PHP files.
2. Run every repository test suite.
3. Check JavaScript syntax.
4. Scan the release tree for common secret patterns.
5. Generate and verify the release manifest.
6. Verify installable and repository ZIP CRCs and file parity.
7. Install over v1.2.1 or later on a backed-up WordPress site.
8. Complete database and v1.3.0 calendar migration repairs if requested.
9. Run Live Validation and verify meeting creation, slot acceptance, sender projection, rescheduling, reminder idempotency, cancellation, link revocation, and cleanup.
10. Complete controlled advisory and support meeting tests, inbox confirmation, and backup evidence.

## Production gate

Production requires 100% readiness, zero failures, zero warnings, current version-bound live validation, backup, inbox, and pilot evidence, no unresolved calendar or Graph blockers, and typed human promotion. Repository tests do not establish live-host readiness.

## Rollback

Retain the prior plugin ZIP, database backup, and protected-storage backup. Code rollback to v1.2.1 requires review because v1.3.0 adds calendar fields and a reminder table. Do not drop or rewrite those records during rollback; restore code only after validating compatibility and retain the database backup for full recovery.
