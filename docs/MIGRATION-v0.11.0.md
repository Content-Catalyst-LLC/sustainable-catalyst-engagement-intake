# Migration to v0.11.0

## Versions

```text
SC_EI_VERSION = 0.11.0
SC_EI_DB_VERSION = 0.11.0
SC_EI_HARDENING_SCHEMA_VERSION = 1.0.0
```

All prior domain schema versions remain unchanged.

## New tables

```text
{prefix}sc_ei_health_events
{prefix}sc_ei_rate_limits
```

No existing inquiry, review, fit, portal, scheduling, proposal, engagement, analytics, privacy, communication, or attachment row is rewritten during migration.

## New schedules

```text
sc_ei_hardening_watchdog
sc_ei_hardening_prune
```

## New options

```text
sc_ei_hardening_schema_version
sc_ei_last_hardening_watchdog
sc_ei_hardening_lock_*
```

## New capabilities

```text
sc_intake_view_reliability
sc_intake_manage_reliability
sc_intake_export_reliability
```

## Upgrade sequence

1. Back up database and private storage.
2. Upgrade to v0.11.0.
3. Clear all caches.
4. Confirm DB 0.11.0.
5. Confirm hardening schema 1.0.0.
6. Confirm both hardening tables.
7. Confirm capability migration.
8. Confirm watchdog and prune schedules.
9. Run the manual watchdog.
10. Review unresolved events.
11. Test public and portal throttling.
12. Test incident pause and recovery.
13. Test accessibility behaviors.
14. Export a redacted report.

## Rollback

Before returning temporarily to v0.10.0:

1. resume public writes if paused
2. preserve both hardening tables
3. preserve the last watchdog option
4. understand that v0.10.0 will not display or prune hardening data
5. keep server and CDN protections active
6. return to v0.11.0 after the compatibility issue is resolved

Do not drop health or rate-limit records merely to restore the older interface.
