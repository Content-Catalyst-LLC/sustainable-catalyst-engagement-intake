# Migration to v0.10.0

## Versions

```text
SC_EI_VERSION = 0.10.0
SC_EI_DB_VERSION = 0.10.0
SC_EI_PORTAL_SCHEMA_VERSION = 1.3.0
SC_EI_WORKFLOW_SCHEMA_VERSION = 1.1.0
SC_EI_GRAPH_SCHEMA_VERSION = 1.0.0
SC_EI_ENGAGEMENT_SCHEMA_VERSION = 1.0.0
```

## New tables

```text
{prefix}sc_ei_engagements
{prefix}sc_ei_engagement_snapshots
{prefix}sc_ei_engagement_requirements
{prefix}sc_ei_engagement_events
```

## Existing records

No engagement is created automatically during migration.

All v0.9.1 inquiries, portal records, Graph operations, Teams meetings, proposals, communications, reviews, fit assessments, privacy cases, retention records, and uploads are preserved.

## Portal permission

New permission:

```text
view_engagements
```

Existing access rows retain their stored permissions. Reissue access where the sender should see the Engagement section.

## New capabilities

```text
sc_intake_view_engagements
sc_intake_create_engagement_handoffs
sc_intake_manage_engagements
sc_intake_activate_engagements
sc_intake_complete_engagements
sc_intake_export_engagements
```

## Upgrade sequence

1. Back up database and protected storage.
2. Upgrade from v0.9.1.
3. Clear all caches.
4. Confirm DB 0.10.0.
5. Confirm portal schema 1.3.0.
6. Confirm engagement schema 1.0.0.
7. Confirm all four new tables.
8. Confirm unique `engagements.proposal_id` index.
9. Confirm capabilities.
10. Open Engagement Intake → Engagements.
11. Test one contracted proposal handoff.
12. Verify snapshot integrity.
13. Test readiness and activation.
14. Reissue one portal invitation and verify sender-safe visibility.
15. Test export and approved erasure.

## Rollback

Before temporarily returning to v0.9.1:

1. preserve all four engagement tables
2. preserve schema version metadata
3. document active engagement states
4. understand that v0.9.1 will not display engagement records
5. do not drop or edit immutable snapshot records
6. continue using external contract and operational systems as the source of truth during rollback
