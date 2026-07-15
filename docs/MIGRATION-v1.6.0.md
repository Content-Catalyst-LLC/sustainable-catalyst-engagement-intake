# Migration to v1.6.0

The v1.6.0 migration is nondestructive.

## Identity

- Plugin version: `1.6.0`
- Database version: `1.6.0`
- Platform evidence schema: `1.6.0`
- Analytics schema: `1.1.0`
- Service Intelligence schema: `1.0.0`
- Migration key: `v1_6_0_engagement_analytics_service_intelligence`

## Added tables

- `{prefix}sc_ei_service_intelligence_findings`
- `{prefix}sc_ei_service_intelligence_events`

Existing inquiries, support cases, meetings, proposals, engagements, workspaces, documents, communications, privacy records, and audit history are preserved.

## Post-upgrade validation

1. Repair the database contract if requested.
2. Verify the v1.6.0 migration journal.
3. Open Analytics & Intelligence.
4. Run Live Validation.
5. Confirm the personal-data fixture is rejected, the aggregate finding enters human review, its evidence hash verifies, and the snapshot is stored.
6. Save a current aggregate snapshot and resolve any overdue finding reviews before Production.
