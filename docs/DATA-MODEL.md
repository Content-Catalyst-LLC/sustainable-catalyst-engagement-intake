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

The table is installed in v0.1.0 so later releases do not need to redesign the data model.

Fields include:

- Inquiry relationship
- UUID
- Original and stored filenames
- Protected relative path
- MIME type and extension
- Size and SHA-256 digest
- Quarantine, validation, and scan states
- Retention date
- Upload and deletion timestamps
- Additional metadata

## Audit record

Events can reference an inquiry, an attachment, or both.

Examples:

- `plugin_activated`
- `inquiry_created`
- `status_changed`
- `internal_note`
- `personal_data_erased`
- Future: `attachment_uploaded`, `attachment_downloaded`, `message_sent`, `retention_scheduled`, `inquiry_deleted`


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
