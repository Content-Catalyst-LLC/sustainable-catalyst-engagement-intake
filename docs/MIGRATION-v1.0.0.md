# Migration to v1.0.0

## Release

Unified Contact and Engagement Platform

## Before upgrading

1. Back up the WordPress database.
2. Back up protected engagement storage.
3. Record the installed plugin version and active shortcodes.
4. Confirm no long-running document scan, Graph operation, privacy erasure, or engagement transition is active.
5. Use staging before production.

## Database changes

Two tables are added through WordPress `dbDelta`:

```text
{prefix}sc_ei_platform_snapshots
{prefix}sc_ei_platform_migrations
```

No existing table is renamed or removed.

## Schema versions

```text
Database:       1.0.0
Platform:       1.0.0
Portal:         1.3.0
Workflow:       1.1.0
Graph:          1.0.0
Engagement:     1.0.0
Analytics:      1.0.0
Hardening:      1.0.0
Workflow Core:  1.0.0
```

## Idempotent migration

The migration journal key is:

```text
v1_0_0_unified_contact_engagement_platform
```

A completed migration is not repeated. An incomplete or failed record remains visible for human review and can be verified through Platform Overview.

The migration does not:

- delete data
- reset shortcodes
- revoke sender sessions
- rotate Graph secrets
- alter proposal or engagement decisions
- change launch state to production
- provision external systems

## After upgrading

1. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
2. Open Contact & Engagement → Diagnostics.
3. Confirm database and plugin version `1.0.0`.
4. Confirm both platform tables and all columns.
5. Open Contact & Engagement → Platform Overview.
6. Run `VERIFY PLATFORM MIGRATION`.
7. Configure the public entry URL, portal URL, privacy URL, and support email.
8. Run `SNAPSHOT PLATFORM`.
9. Test `[sc_contact_engagement_platform]` on staging.
10. Test all legacy shortcodes.
11. Complete a full inquiry-to-engagement staging path.
12. Exercise privacy export/erasure, incident pause, Graph fallback, scheduled jobs, analytics, and Workflow Core adapter behavior.
13. Record Pilot or Production state only after required checks pass.

## Rollback

A code rollback should use a database backup taken immediately before the upgrade. Do not manually drop the two platform tables while v1.0.0 remains active. The new tables are additive; older plugin code will normally ignore them, but a tested database-and-code rollback is the supported recovery path.
