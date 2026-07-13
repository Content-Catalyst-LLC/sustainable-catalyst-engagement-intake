# Migration to v0.6.0

## Versions

```text
SC_EI_VERSION = 0.6.0
SC_EI_DB_VERSION = 0.6.0
SC_EI_PRIVACY_SCHEMA_VERSION = 1.0.0
```

## New tables

```text
{prefix}sc_ei_privacy_requests
{prefix}sc_ei_consent_events
{prefix}sc_ei_legal_holds
{prefix}sc_ei_retention_policies
{prefix}sc_ei_retention_actions
```

## New inquiry fields

```text
privacy_status
retention_policy_key
retention_until
legal_hold_count
privacy_restriction_reason
last_privacy_review_at
last_privacy_review_by
personal_data_erased_at
privacy_version
```

## Backfill

Existing inquiries receive:

```text
privacy_status = active
retention_policy_key = unaccepted_inquiry
retention_until = created_at + configured unaccepted period
legal_hold_count = 0
privacy_version = 0
```

The backfill does not erase or queue existing records.

## Default policies

Activation seeds one active version for each missing key:

```text
unaccepted_inquiry
withdrawn_inquiry
closed_inquiry
accepted_inquiry
private_attachment
communication_content
```

Existing policy keys are not overwritten.

## New capabilities

```text
sc_intake_view_privacy_center
sc_intake_manage_privacy_requests
sc_intake_manage_consent
sc_intake_manage_legal_holds
sc_intake_manage_retention_policies
sc_intake_approve_retention_actions
sc_intake_execute_retention_actions
sc_intake_export_privacy_data
```

Reviewers receive view access.

Managers and administrators receive lifecycle-management capabilities.

## Retention behavior change

The existing hook:

```text
sc_ei_cleanup_expired_attachments
```

remains scheduled for compatibility.

Before v0.6.0, its implementation could delete expired private attachments.

In v0.6.0, it is queue-only:

```text
candidate scan
→ queue action
→ no deletion
```

## WordPress eraser behavior change

The WordPress privacy eraser now:

- creates or reuses an erasure case
- marks the inquiry erasure requested
- queues document actions
- queues inquiry redaction
- respects legal holds
- reports items retained pending review

It does not erase synchronously.

## Settings repair

v0.6.0 repairs inherited v0.5.0 code that placed notification-sanitization logic inside `default_settings()` and referenced an undefined `$value`.

The repair restores:

```text
default_settings() = pure defaults
sanitize_settings() = validation and normalization
```

## Upgrade sequence

1. Back up database and protected storage.
2. Upgrade to v0.6.0.
3. Confirm database version 0.6.0.
4. Confirm privacy schema 1.0.0.
5. Confirm five new tables.
6. Confirm new inquiry fields.
7. Confirm default policies.
8. Confirm privacy capabilities.
9. Confirm daily cron is queue-only.
10. Open Privacy Center and review inventory.
11. Generate a preview.
12. Confirm no action executed.
13. Test hold placement and blocking.
14. Test approval and typed execution in staging.
15. Test WordPress exporter and eraser.
16. Review all configured periods with appropriate counsel.

## Rollback

v0.5.0 does not expose privacy lifecycle tables or fields.

Preserve:

- all privacy lifecycle tables
- inquiries
- attachments
- reviews
- communications
- audit records
- protected storage
- plugin settings

Do not drop lifecycle evidence merely to make the older interface appear clean.
