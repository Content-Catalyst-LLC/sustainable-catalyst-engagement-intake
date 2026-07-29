# Migration v2.0.1 — Interrupted Migration and Database Recovery

This is a nondestructive reliability patch for installations where a disk-full or interrupted v2.0.0 upgrade left `proposal_approvals` or `platform_handoffs` absent.

## Automatic recovery

On activation or database upgrade, the plugin now:

1. Checks the two recovery-critical tables without querying columns on an absent table.
2. Creates a missing table with native `CREATE TABLE IF NOT EXISTS` using the canonical v2.0 schema.
3. Runs the existing WordPress `dbDelta()` reconciliation across the complete schema.
4. Verifies the required database contract before advancing the stored database version.
5. Stops activation cleanly if database writes remain unavailable.

## Runtime containment

A five-minute migration lock prevents overlapping upgrade attempts. If an already-active plugin update cannot recover the tables, the plugin pauses its runtime and shows an administrator notice rather than repeatedly issuing `SHOW COLUMNS` queries and expanding `error_log`.

## Data handling

No existing tables are dropped or renamed. No existing rows are modified by the targeted table-creation step. Plugin version advances to `2.0.1`; database and subsystem schema versions remain `2.0.0` or their prior values.

## Verification

After activation, run:

```bash
wp db query "SHOW TABLES LIKE 'eu3_sc_ei_proposal_approvals';"
wp db query "SHOW TABLES LIKE 'eu3_sc_ei_platform_handoffs';"
wp option get sc_ei_db_version
```

Use the actual WordPress table prefix when it differs from `eu3_`.
