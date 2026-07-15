# Migration to v2.0.0

v2.0.0 is a nondestructive database migration.

## Added tables

- `sc_ei_engagement_dossiers`
- `sc_ei_dossier_relationships`
- `sc_ei_dossier_events`
- `sc_ei_platform_handoffs`

The actual table prefix follows the WordPress installation.

## Release identity

- Plugin: `2.0.0`
- Database: `2.0.0`
- Platform evidence: `2.0.0`
- Unified platform schema: `2.0.0`
- Migration journal: `v2_0_0_integrated_advisory_support_institutional_platform`

## Upgrade sequence

1. Back up the database and protected document storage.
2. Replace the current plugin with v2.0.0.
3. Clear all application, host, CDN, browser, and PHP opcode caches.
4. Open Platform Overview and repair the database contract if requested.
5. Verify the v2.0.0 migration journal.
6. Open Command Center and run the bounded dossier backfill.
7. Run Live Validation.
8. Confirm no missing dossiers, orphaned relationships, stale handoffs, or unresolved failed handoffs remain.
9. Re-record version-bound production evidence.

The migration does not delete or rewrite existing inquiry, support, meeting, proposal, engagement, workspace, billing, communication, privacy, or audit records.
