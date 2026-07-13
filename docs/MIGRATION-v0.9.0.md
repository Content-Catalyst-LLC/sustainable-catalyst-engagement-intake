# Migration to v0.9.0

## Versions

```text
SC_EI_VERSION = 0.9.0
SC_EI_DB_VERSION = 0.9.0
SC_EI_PORTAL_SCHEMA_VERSION = 1.2.0
SC_EI_WORKFLOW_SCHEMA_VERSION = 1.0.0
```

## New tables

```text
{prefix}sc_ei_meeting_offers
{prefix}sc_ei_proposals
{prefix}sc_ei_proposal_versions
{prefix}sc_ei_workflow_events
```

## Existing data

The migration preserves all v0.8.1:

- inquiries
- portal invitations and sessions
- portal recovery requests
- secure messages
- protected documents
- review history
- fit assessments
- privacy requests
- consent
- legal holds
- retention actions
- audit records

No meeting offer or proposal is created automatically.

## New portal permissions

Existing portal access records retain their stored permission JSON.

To grant meeting and proposal access to an already-issued portal record, issue a fresh invitation with:

```text
view_meetings
respond_meetings
view_proposals
respond_proposals
```

Fresh invitations use the updated default permission set.

## New capabilities

```text
sc_intake_view_workflow
sc_intake_manage_workflow
sc_intake_create_meeting_offers
sc_intake_publish_meeting_offers
sc_intake_finalize_meetings
sc_intake_create_proposals
sc_intake_publish_proposals
sc_intake_record_contracts
sc_intake_export_workflow
```

## Scheduled event

```text
sc_ei_workflow_cleanup
```

Runs hourly and expires stale published offers and proposals.

It does not delete workflow records.

## Upgrade sequence

1. Back up database and protected storage.
2. Upgrade from v0.8.1.
3. Clear PHP opcode, WordPress object, page, reverse-proxy, CDN, and browser caches.
4. Confirm DB 0.9.0.
5. Confirm portal schema 1.2.0.
6. Confirm workflow schema 1.0.0.
7. Confirm four new tables.
8. Confirm workflow capabilities.
9. Confirm hourly cleanup.
10. Review existing portal permissions.
11. Reissue invitations where meeting or proposal permissions are required.
12. Test one complete meeting workflow.
13. Test one complete proposal workflow.
14. Test privacy export and erasure in staging.

## Rollback

Before temporarily returning to v0.8.1:

1. preserve all workflow tables
2. cancel or document active offers where necessary
3. preserve proposal versions and event history
4. understand that v0.8.1 will not expose the new workflow workspace
5. do not drop workflow tables merely to restore the earlier interface
