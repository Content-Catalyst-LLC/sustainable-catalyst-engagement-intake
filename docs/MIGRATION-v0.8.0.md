# Migration to v0.8.0

## Versions

```text
SC_EI_VERSION = 0.8.0
SC_EI_DB_VERSION = 0.8.0
SC_EI_PORTAL_SCHEMA_VERSION = 1.0.0
```

## New tables

```text
{prefix}sc_ei_portal_access
{prefix}sc_ei_portal_sessions
{prefix}sc_ei_portal_events
```

## Inquiry fields

```text
portal_status
portal_access_id
portal_last_activity_at
portal_message_count
portal_document_count
portal_last_sender_message_at
sender_withdrawal_status
sender_withdrawal_requested_at
sender_withdrawal_reason
portal_version
```

## Communication fields

```text
portal_visibility
portal_published_at
portal_published_by
portal_source
```

Existing communications default to hidden.

## Backfill

Existing inquiries receive:

```text
portal_status = inactive
portal_message_count = 0
portal_document_count = 0
sender_withdrawal_status = none
portal_version = 0
```

No access record, invitation, session, or portal publication is created.

## New capabilities

```text
sc_intake_view_sender_portal
sc_intake_manage_sender_portal
sc_intake_issue_portal_invites
sc_intake_post_portal_messages
sc_intake_revoke_portal_access
sc_intake_manage_portal_settings
sc_intake_export_portal_audit
```

## Scheduled event

```text
sc_ei_portal_cleanup
```

Runs hourly and marks stale invitations and sessions expired.

It does not delete events or inquiry records.

## Upgrade sequence

1. Back up database and protected storage.
2. Upgrade to v0.8.0.
3. Confirm DB version and portal schema.
4. Confirm three portal tables.
5. Confirm inquiry and communication fields.
6. Confirm capabilities.
7. Create a private page with `[sc_sender_portal]`.
8. Save its URL in Sender Portal settings.
9. Exclude it from caches and public navigation.
10. Confirm hourly cleanup.
11. Test invitation, activation, session, message, upload, privacy, withdrawal, and revocation workflows in staging.

## Rollback

v0.7.0 does not expose portal records.

Preserve the portal tables and new inquiry/communication fields during a temporary rollback.

Revoke active sessions before rollback.

Do not drop access or audit history merely to restore the older interface.
