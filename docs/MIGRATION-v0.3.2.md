# Migration to v0.3.2

## Database

`SC_EI_DB_VERSION` advances from `0.3.1` to `0.3.2`.

New attachment fields:

```text
scan_attempts
last_scanned_at
last_scanned_by
```

A new index is added:

```text
last_scanned_at
```

Existing inquiry, attachment, storage, SHA-256, quarantine, retention, Teams, conversion, privacy, and audit records are preserved.

## Capabilities

Administrators and existing Engagement Managers receive:

```text
sc_intake_manage_scanner
sc_intake_bulk_file_actions
sc_intake_view_file_audit
```

Engagement Reviewers do not receive scanner, bulk file, download, release, retention, export, or deletion capabilities by default.

## Settings

New settings:

```text
scanner_test_freshness_hours = 24
scanner_bulk_retry_limit = 25
```

Existing `require_external_scanner` remains unchanged during migration.

Newly turning it on after the upgrade requires a current clean readiness test. An already-enabled fail-closed policy is not silently disabled when readiness later degrades.

## Existing attachments

No file is moved or rescanned automatically.

For existing records:

- prior scanner status and provider remain intact
- attempt count may be zero until a new upload or administrative retry
- last scan time and actor may be empty
- storage reconciliation remains separate and read-only

## Upgrade checklist

1. Back up the database.
2. Back up the locked private storage directory.
3. Activate v0.3.2.
4. Confirm database version 0.3.2.
5. Confirm scanner columns are green in Diagnostics.
6. Open Quarantine Operations.
7. Review storage and scanner attention counts.
8. Run the benign scanner test when an integration is configured.
9. Review role capabilities.
10. Test single and bulk scanner retry in staging.
11. Review CSV export permissions.
12. Confirm bulk deletion requires `REJECT SELECTED`.

## Rollback

v0.3.1 does not expose the new operational queue or scanner history fields. A rollback should preserve the v0.3.2 database and private storage directory until the reason is resolved.

Do not delete the scanner readiness option or attachment scanner metadata merely to force an older release to appear healthy.
