# Release Procedure — v1.3.1

## Release identity

- Plugin version: `1.3.1`
- Database version: `1.3.0`
- Platform evidence schema: `1.3.1`
- Calendar schema: `1.0.1`
- Support schema: `1.0.1`
- Release: Scheduling, Reminder, and Time-Zone Reliability Patch

## Migration

The patch is nondestructive and idempotent. It records `v1_3_1_scheduling_reminder_timezone_reliability`, verifies the v1.3.0 calendar tables, repairs bounded reminder-state inconsistencies, and preserves all existing records. No database table or column is added or removed.

## Required validation

1. Lint every plugin and test PHP file.
2. Run every repository suite, including executable daylight-saving tests.
3. Check JavaScript syntax.
4. Scan for common secret formats.
5. Generate and verify the release manifest.
6. Verify ZIP CRCs and exact plugin parity between archives.
7. Install over v1.3.0 or later on a backed-up WordPress site.
8. Run the v1.3.1 calendar reliability repair if requested.
9. Run Live Validation and confirm strict time-zone evidence, rescheduling, cancellation, reviewed cancellation notice, stale-reminder closure, and cleanup.
10. Complete controlled advisory and support meeting tests.

## Production gate

Production requires 100% readiness, zero failures, zero warnings, current version-bound live validation, backup, inbox and pilot evidence, no reminder-integrity or Microsoft Graph blockers, and typed human promotion.

## Rollback

Retain the v1.3.0 plugin ZIP and current database and protected-storage backups. Because v1.3.1 does not change the database schema, code rollback is possible after confirming no active v1.3.1 validation operation is running. Do not delete reminder or migration evidence during rollback.
