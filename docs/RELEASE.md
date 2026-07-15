# Release Procedure — v1.5.0

## Identity

- Plugin version: `1.5.0`
- Database version: `1.5.0`
- Platform evidence schema: `1.5.0`
- Portal schema: `1.7.0`
- Workspace schema: `1.0.0`
- Release: Secure Client Workspace and Collaboration

## Release gate

1. Lint all plugin and test PHP files.
2. Run every repository test suite.
3. Validate JavaScript syntax.
4. Scan for common secret patterns.
5. Regenerate and verify `release-manifest.json`.
6. Package the installable and repository archives.
7. Verify ZIP integrity and SHA-256 checksums.
8. Re-extract the repository archive and rerun all tests.
9. Confirm the plugin trees in both archives are identical.
10. Test the Mac repository updater against a disposable Git remote.

## Live rollout

1. Retain the v1.4.1 ZIP and current database and protected-storage backups.
2. Install v1.5.0 over the current plugin.
3. Verify the database and workspace migration journals.
4. Run Live Validation.
5. Create one controlled client workspace from an existing test engagement.
6. Verify member isolation, milestone visibility, deliverable publication, sender response, workspace messaging, and document metadata.
7. Confirm internal events and staff information remain absent from the Sender Portal.
8. Re-record version-bound validation, inbox, backup, and pilot evidence.
9. Require 100%, zero required failures, and zero warnings before Production.

Code rollback does not remove migrated workspace tables or records. Do not delete workspace records during rollback.
