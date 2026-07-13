# Data Model

## Inquiry record

Core fields:

- Internal numeric ID
- Public UUID
- Human-readable reference
- Inquiry type
- Status
- Contact identity
- Organization and role
- Subject and message
- Project summary and desired outcome
- Service interest
- Budget range
- Desired start and deadline
- Relevant links
- Consent version and timestamp
- Assigned WordPress user
- Created, updated, and closed timestamps

## Attachment metadata

The attachment table began as a foundation in v0.1.0 and now supports the full v0.3.x protected-document lifecycle.

Fields include:

- Inquiry relationship
- UUID
- Original and randomized stored filenames
- Protected relative path
- Declared and detected MIME types
- Extension and signature type
- Size and SHA-256 digest
- Validator version
- Document category, notes, and confidentiality classification
- Quarantine, validation, scan, integrity, and storage states
- Scanner provider and message
- Scanner attempt count, last scanner time, and last scanner actor
- Last verification time, user, source, and message
- Retention date
- Approval, rejection, replacement, download, upload, and deletion metadata
- Additional sanitized metadata

## Audit record

Events can reference an inquiry, an attachment, or both.

Examples:

- `plugin_activated`
- `inquiry_created`
- `status_changed`
- `internal_note`
- `personal_data_erased`
- `attachment_quarantined`
- `attachment_integrity_checked`
- `attachment_downloaded`
- `attachment_status_changed`
- `attachment_retention_updated`
- `attachment_deleted`
- `storage_reconciliation_completed`
- `storage_repair_completed`
- `manual_retention_cleanup_completed`
- Future: `message_sent`, `retention_scheduled`, `inquiry_deleted`


## Microsoft Teams communication and scheduling fields

- Preferred contact method
- Teams email
- Phone number
- IANA time zone
- City and country
- Meeting request
- Preferred weekdays
- Preferred time windows
- Preferred duration
- Participant count
- Participant emails
- Accessibility needs
- Calendar invitation consent
- Scheduling notes
- Scheduling status
- Teams meeting URL
- Scheduled UTC start and end
- Scheduled time zone
- Calendar event ID


## Conversion routing fields

- Form variant
- Source page
- Entry CTA
- Conversion route
- Guidance flags
- Referring form URL in private metadata


## Scanner operational metadata

v0.3.2 adds:

```text
scan_attempts
last_scanned_at
last_scanned_by
```

These fields distinguish the current scanner result from its operational history. Detailed result changes remain in the audit ledger.
