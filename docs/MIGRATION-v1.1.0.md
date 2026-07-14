# Migration to v1.1.0

## Release

**Sustainable Catalyst Contact and Engagement Platform v1.1.0 — Advisory Operations and Engagement Lifecycle**

## Migration type

The upgrade is **nondestructive and idempotent**.

Database version advances from `1.0.0` to `1.1.0`. Existing inquiry, attachment, review, fit, portal, meeting, proposal, engagement, communication, privacy, audit, analytics, hardening, and Workflow Core data is preserved.

## New data structures

The migration adds lifecycle fields to the inquiry table and creates:

```text
{prefix}sc_ei_lifecycle_events
{prefix}sc_ei_lifecycle_notes
{prefix}sc_ei_lifecycle_tasks
```

Existing inquiries are backfilled from legacy statuses. No existing status field is removed. The lifecycle layer continues to maintain the compatible legacy status projection used by earlier components.

## Migration journal

```text
v1_1_0_advisory_operations_engagement_lifecycle
```

The journal records the previous and target lifecycle schema versions, database state, completion time, and nondestructive migration context.

## Upgrade procedure

1. Back up the WordPress database and protected document storage.
2. Install v1.1.0 over v1.0.3.
3. Clear all application, object, host, CDN, browser, and PHP opcode caches.
4. Open Platform Overview and run the bounded database repair if requested.
5. Verify the v1.1.0 lifecycle migration journal.
6. Open Advisory Lifecycle and inspect a sample of backfilled inquiries.
7. Confirm existing meetings, proposals, documents, and engagement links remain available.
8. Assign owners and next actions where operationally appropriate.
9. Resolve overdue tasks and next actions.
10. Run v1.1.0 Live Validation and record fresh launch evidence.

## Rollback boundary

A plugin-code rollback does not remove the new tables or columns. This is intentional to prevent data loss. Restore the pre-upgrade database backup only when a complete database rollback is operationally necessary and has been reviewed.
