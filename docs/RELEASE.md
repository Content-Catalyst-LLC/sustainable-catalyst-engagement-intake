# Release Procedure — v1.6.0

## Identity

- Plugin version: `1.6.0`
- Database version: `1.6.0`
- Platform evidence schema: `1.6.0`
- Analytics schema: `1.1.0`
- Service Intelligence schema: `1.0.0`
- Release: Engagement Analytics and Service Intelligence

## Release gate

1. Lint all plugin and test PHP files.
2. Run every repository test suite.
3. Validate JavaScript syntax.
4. Scan for common secret patterns.
5. Regenerate and verify `release-manifest.json`.
6. Package installable and repository archives.
7. Verify ZIP integrity and SHA-256 checksums.
8. Re-extract the repository archive and rerun all tests.
9. Confirm the plugin trees in both archives are identical.
10. Test the Mac updater against a disposable Git remote.

## Live rollout

1. Retain the v1.5.0 ZIP and current database and protected-storage backups.
2. Install v1.6.0 and clear all caches.
3. Verify the database and service-intelligence migration journals.
4. Run Live Validation.
5. Verify personal-data evidence rejection, aggregate finding creation, human review, event history, evidence hash, snapshot storage, and cleanup.
6. Save one controlled aggregate snapshot and review one controlled finding.
7. Re-record version-bound validation, inbox, backup, and pilot evidence.
8. Require 100%, zero required failures, and zero warnings before Production.

Code rollback does not remove migrated aggregate-intelligence tables or records.
