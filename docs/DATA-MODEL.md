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


## Administrative review current state

The inquiry table stores the current administrative review state:

```text
assigned_user_id
assignment_at
assignment_by
review_stage
review_priority
review_due_at
fit_decision
fit_confidence
risk_level
evidence_readiness
scope_clarity
recommended_next_step
review_summary
decision_rationale
information_gaps
conflict_notes
review_checklist
escalation_status
escalation_reason
review_started_at
last_reviewed_at
last_reviewed_by
decision_at
review_completed_at
review_version
```

This denormalized current state supports operational queues and filters.

## Immutable review snapshots

`{prefix}sc_ei_reviews` records every successful structured review save.

The table is append-only through the normal plugin workflow. Privacy erasure can remove narrative personal data while retaining categorical accountability.

Snapshot fields include:

```text
inquiry_id
public_id
reviewer_user_id
event_type
from_stage
to_stage
priority
fit_decision
fit_confidence
risk_level
evidence_readiness
scope_clarity
recommended_next_step
summary
rationale
information_gaps
conflict_notes
checklist_json
escalation_status
escalation_reason
assigned_user_id
due_at
inquiry_status
review_version
snapshot_json
created_at
```

`review_version` on the inquiry row is used for optimistic concurrency.


## Inquiry communication state

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

## Communication records

`{prefix}sc_ei_communications` stores:

```text
inquiry_id
public_id
thread_key
reply_to_id
direction
channel
communication_type
status
subject
body_text
sender_user_id
sender_name
sender_email
recipient_name
recipient_email
cc_json
template_key
template_version
is_automated
requires_approval
approved_by
approved_at
provider
provider_message_id
attempt_count
last_attempt_at
accepted_at
failed_at
error_code
error_message
occurred_at
scheduled_for
privacy_classification
message_hash
dedupe_key
metadata_json
row_version
created_at
updated_at
deleted_at
```

## Communication events

`{prefix}sc_ei_communication_events` is append-only through normal operations and stores status transitions and transport context.

## Communication templates

`{prefix}sc_ei_communication_templates` stores each template version.

The compound unique key is:

```text
template_key + version
```


## v0.6.0 inquiry privacy state

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

## Privacy requests

Tracks request type, identity state, source, deadlines, assignment, summaries, resolution, and completion.

## Consent events

Append-oriented evidence for notice and authorization actions.

## Legal holds

Tracks active or released preservation controls for an inquiry or a specific private document.

## Retention policies

Immutable versions identified by:

```text
policy_key + version
```

## Retention actions

Tracks proposal, due date, policy version, hold/dependency state, approval, execution, verification, failures, and tombstone snapshots.


## v0.7.0 fit assessment state

Inquiry fields:

```text
fit_assessment_status
current_fit_assessment_id
fit_assessment_updated_at
fit_assessment_finalized_at
fit_assessment_version
```

Assessment records store:

```text
version and parent
assessor
workflow state
human recommendation
confidence
service route
scope boundary
advisory signal
material concern count
second-review requirement and disposition
summary and rationale
conditions and limitations
referral notes
attestation
assistance disclosure
submission and finalization state
row version
```

Criterion items store one row per criterion per assessment.

Second-review records are append-only through normal operation.


## v0.8.0 portal data model
```text
portal_access
portal_sessions
portal_events
```

Access stores invitation lifecycle, permission, terms, lockout, and revocation state.

Sessions store credential hash, expiry, activity, and hashed client fingerprints.

Events store categorical action evidence and sanitized context.

Raw credentials and plaintext network identifiers are excluded.


## v0.8.1 recovery data model

New table:

```text
portal_recovery_requests
```

It stores hashed matching evidence, sender reason, rate and deduplication state, human review state, and timestamps.

Unmatched public attempts are represented only as sanitized portal security events.

The session table remains unchanged; active v0.8.0 sessions migrate at the cookie layer.


## v0.9.0 workflow data model

New tables:

```text
meeting_offers
proposals
proposal_versions
workflow_events
```

A meeting offer stores slot JSON, sender response, selected UTC interval, Teams URL, publication, finalization, and lifecycle state.

A proposal stores workflow state and pointers to its current published and pending unpublished versions.

A proposal version stores structured content and a SHA-256 hash.

Workflow events preserve actor type, actor identifier, target, state transition, and sanitized context.
