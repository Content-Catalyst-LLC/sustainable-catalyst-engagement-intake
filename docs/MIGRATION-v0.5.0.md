# Migration to v0.5.0

## Database version

```text
SC_EI_DB_VERSION = 0.5.0
```

## New tables

```text
{prefix}sc_ei_communications
{prefix}sc_ei_communication_events
{prefix}sc_ei_communication_templates
```

## New inquiry fields

```text
communication_status
next_follow_up_at
last_communication_at
last_outbound_at
last_inbound_at
last_notification_at
communication_count
unread_inbound_count
do_not_email
do_not_email_reason
communication_version
```

Existing inquiries receive database defaults only:

```text
communication_status = open
counts = 0
do_not_email = false
communication_version = 0
```

No historical communication is fabricated.

## Template seed

Activation inserts the default template set only when a template key does not already exist.

Existing versioned templates are never overwritten by activation.

## New capabilities

```text
sc_intake_view_communications
sc_intake_compose_communications
sc_intake_send_communications
sc_intake_record_inbound
sc_intake_manage_templates
sc_intake_manage_notifications
sc_intake_export_communications
```

Reviewers receive view, compose, send, record, and export capability.

Managers receive all communication and notification capabilities.

Administrators receive all capabilities.

## Settings defaults

```text
communication_sender_name = WordPress site name
communication_sender_email = WordPress admin email
communication_reply_to_email = WordPress admin email

sender_acknowledgment_enabled = false
internal_new_inquiry_enabled = false
review_due_reminders_enabled = false
follow_up_reminders_enabled = false
escalation_notifications_enabled = false

review_reminder_lead_hours = 24
notification_batch_limit = 25
```

Automation remains off even when the default admin address is present.

## Cron

New hourly hook:

```text
sc_ei_notification_reminders
```

The cron is scheduled on activation and removed on deactivation or uninstall.

The hook does nothing when reminder policies are disabled.

## Upgrade sequence

1. Back up the database.
2. Back up protected storage.
3. Upgrade to v0.5.0.
4. Confirm three communication tables.
5. Confirm inquiry communication fields.
6. Confirm active templates.
7. Confirm capabilities.
8. Confirm notification cron.
9. Review sender settings.
10. Run a transport test before enabling automation.

## Rollback

v0.4.0 does not expose communication tables or inquiry communication fields.

Preserve:

- communication tables
- inquiry table
- audit table
- settings
- protected storage

Do not drop communication history merely to make the older interface appear clean.
