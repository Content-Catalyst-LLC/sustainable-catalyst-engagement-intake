# Migration to v0.3.1

## Database

`SC_EI_DB_VERSION` advances from `0.3.0` to `0.3.1`.

New attachment fields:

```text
storage_status
last_verified_at
last_verified_by
last_verification_source
last_verification_message
```

A new `storage_status` index is added.

Existing inquiry, attachment, audit, Teams, conversion, privacy, and retention data is preserved.

## Filesystem

The migration does not move or rename existing `.qtn` files.

Existing v0.3.0 records initially use the database default `unverified`. Running Storage Reconciliation updates active records to their observed state.

## First production run

1. Back up the database and locked storage directory.
2. Upgrade and activate v0.3.1.
3. Open Engagement Intake → Diagnostics.
4. Confirm database 0.3.1 and new fields.
5. Run Storage Probe.
6. Run Storage Reconciliation.
7. Investigate any missing, changed, misplaced, or orphaned file.
8. Preview retention cleanup.
9. Test public forms before relying on live submissions.

## Rollback

v0.3.0 does not understand the new verification fields or reliability actions. A rollback should preserve the v0.3.1 database and private storage until the reason for rollback is resolved.
