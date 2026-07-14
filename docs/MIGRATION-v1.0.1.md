# Migration to v1.0.1

## Scope

v1.0.1 is a code-only production reliability patch.

- Plugin version: `1.0.1`
- Database version: `1.0.0`
- Platform schema: `1.0.0`
- Data migration: none

## Upgrade procedure

1. Back up the WordPress database and protected engagement storage.
2. Install the v1.0.1 ZIP over v1.0.0 and activate it.
3. Clear PHP opcode, WordPress, host, CDN, and browser caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Open **Contact & Engagement → Diagnostics**.
6. Confirm both pages render without a fatal error.
7. Confirm the stored plugin version is `1.0.1` and the database version remains `1.0.0`.
8. Run a readiness snapshot and a controlled test inquiry before public rollout.

## Fixed runtime path

The platform repository no longer accesses `SC_EI_Hardening_Repository::WATCHDOG_HOOK` directly. It calls the public typed accessor `SC_EI_Hardening_Repository::watchdog_hook()` while the hook constant remains private.

## Rollback

The patch does not alter tables or stored records. A code rollback to v1.0.0 is technically data-compatible, but v1.0.0 should not be reactivated because its Platform Overview and Diagnostics runtime path can fatal.
