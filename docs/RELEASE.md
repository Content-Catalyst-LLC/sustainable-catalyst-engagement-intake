# Release Procedure — v1.7.0

## Identity

- Plugin version: `1.7.0`
- Database version: `1.7.0`
- Platform evidence schema: `1.7.0`
- Portal schema: `1.8.0`
- Billing schema: `1.0.0`
- Release: Billing, Invoicing, and Payment Handoffs

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

1. Retain the v1.6.0 ZIP and current database and protected-storage backups.
2. Install v1.7.0 and clear all caches.
3. Verify the database and billing migration journals.
4. Run Live Validation.
5. Confirm sensitive payment metadata is rejected, invoice issue is versioned, handoff replay is idempotent, settlement updates the invoice, and Sender Portal projection excludes internal metadata.
6. Create one controlled billing profile and invoice linked to a test engagement.
7. Verify the external HTTPS handoff and sender-safe invoice view.
8. Re-record version-bound validation, inbox, backup, and pilot evidence.
9. Require 100%, zero required failures, and zero warnings before Production.

Code rollback does not remove migrated billing tables or records.
