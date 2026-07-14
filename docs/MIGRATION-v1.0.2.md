# Migration to v1.0.2

v1.0.2 is a nondestructive production-readiness upgrade.

- Plugin version: `1.0.2`
- Database version: `1.0.0`
- Platform evidence schema: `1.0.1`
- No tables are removed or reset.
- Existing inquiries, uploads, portal access, proposals, engagements, settings, and audit records are preserved.

## Upgrade

1. Back up the database and protected document storage.
2. Install the v1.0.2 plugin ZIP over v1.0.1.
3. Reactivate only if WordPress does not preserve activation.
4. Clear application, object, opcode, host, CDN, and browser caches.
5. Open Platform Overview and confirm the v1.0.2 patch migration is complete.
6. Use the repair center to refresh any stale version, migration, storage, or cron evidence.
7. Configure published Contact and Sender Portal pages with their required shortcodes.
8. Run live validation and manually confirm test-message delivery.
9. Attest fresh database and protected-storage backups.
10. Keep the platform in Setup or Pilot until the production gate reaches 100% with no failures or warnings.

## Rollback

The plugin code may be rolled back to v1.0.1 because the database contract remains at 1.0.0. Preserve the database and protected storage. A rollback will leave the harmless v1.0.2 migration-journal and evidence options in place unless data is explicitly removed during uninstall.
