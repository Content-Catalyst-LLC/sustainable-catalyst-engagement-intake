# Sustainable Catalyst Engagement Intake

**Version:** 0.6.0  
**Release:** Privacy and Retention Center

v0.6.0 adds an auditable lifecycle layer:

```text
Private intake
→ review and communication
→ privacy state
→ policy preview
→ queue
→ legal-hold and dependency review
→ approval
→ typed execution
→ verification
→ non-personal tombstone
```

## Public shortcodes

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
```

```text
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
```

## Privacy Center

Open:

```text
Engagement Intake → Privacy Center
```

Views:

- Overview
- Privacy Requests
- Consent Ledger
- Legal Holds
- Retention Queue
- Policies
- Operating Method

## Safety model

The release enforces:

```text
preview is not queue
queue is not approval
approval is not execution
execution is not assumed verification
```

The daily cron only queues candidates.

Approval is mandatory.

Irreversible execution requires:

```text
EXECUTE <action-id>
```

Private-document execution verifies physical absence before marking the action complete.

## Privacy requests

Cases support:

- access/export
- erasure
- restriction
- correction
- consent withdrawal
- objection
- portability
- other privacy requests

Each case records:

- requester
- request type
- source
- identity-verification state
- received date
- due date
- assigned owner
- request summary
- resolution
- completion state

Completing or denying a case requires a resolution summary.

## Consent and authorization ledger

The append-oriented ledger records:

- privacy notice acknowledgment
- inquiry processing
- calendar invitation
- participant contact authorization
- communication permission
- private document processing
- other authorization

Actions include:

- granted
- renewed
- withdrawn
- corrected
- expired
- not applicable

Evidence includes:

- notice or consent version
- recorded processing basis
- source
- evidence note
- subject email SHA-256
- occurrence time
- internal actor

Withdrawing a recorded authorization restricts the inquiry until reviewed.

## Legal holds

A hold can cover:

```text
entire inquiry and related records
specific private document
```

A hold requires:

- reason
- authority
- placement actor and time
- review date

Release requires an explicit reason.

Any active related hold blocks inquiry erasure. A document hold also blocks its file deletion and related inquiry lifecycle actions.

## Versioned retention policies

Default policy families:

```text
unaccepted_inquiry
withdrawn_inquiry
closed_inquiry
accepted_inquiry
private_attachment
communication_content
```

A policy version records:

- key and immutable version
- name
- target type
- inquiry status scope
- retention days
- anchor field
- action
- recorded basis
- description
- system/custom origin
- author and timestamps

Creating a version archives the prior active version. Existing queued actions keep their original policy key and version.

## Queue behavior

A deterministic candidate includes:

```text
inquiry
target type and ID
policy key and version
action
due date
deduplication key
hold state
minimal snapshot
```

The queue prevents duplicate actions with a unique deduplication key.

States:

```text
queued
blocked_hold
blocked_dependency
approved
executing
executed
failed
canceled
skipped
```

## Execution behavior

### Private document

```text
approved action
→ recheck every related hold
→ delete from protected storage
→ verify physical absence
→ mark attachment tombstone
→ record SHA-256, size, actor, and verification
```

### Communication content

```text
approved action
→ redact subject and body
→ redact parties, CC, provider IDs, errors, hashes, dedupe data, and metadata
→ redact related delivery-event context
→ retain categorical channel, direction, status, type, and timestamps
```

### Inquiry personal data

```text
approved action
→ confirm no active related hold
→ confirm no undeleted private document
→ transaction
→ redact communications and events
→ redact review narratives and snapshots
→ redact consent evidence and email hashes
→ redact privacy-request identifiers and narratives
→ redact released-hold narratives and authorities
→ redact retention snapshots and failure narratives
→ redact inquiry contact, project, scheduling, and link data
→ mark privacy state erased
→ preserve reference and non-personal tombstone
→ commit
```

### Archive only

Accepted engagement records default to archive review rather than automatic erasure.

## WordPress privacy tools

The exporter includes:

- inquiry lifecycle state
- review history
- communications and transport events
- private document metadata
- consent events
- legal holds
- retention actions
- privacy requests

The eraser does not delete synchronously.

It:

1. creates or reuses an open erasure request
2. marks the inquiry erasure requested
3. queues each active private document
4. queues the inquiry erasure dependency
5. records an audit event
6. reports that data remains pending reviewed execution

## Processing restrictions

Sender-facing email is suppressed when the inquiry is:

```text
restricted
erasure_requested
erased
```

Internal case-management and preservation records remain available to authorized users.

## Fixed safety controls

These cannot be disabled in v0.6.0:

```text
daily cron is queue-only
approval before execution is required
non-personal tombstones are retained
```

Optional:

```text
require approver to differ from proposer
```

## Data inventory

The private inventory counts:

- inquiries
- attachments
- reviews
- communications
- communication events
- templates
- privacy requests
- consent events
- legal holds
- retention policies
- retention actions
- audit events
- active private document bytes

Authorized users can export a private JSON inventory containing aggregate counts, active policies, lifecycle settings, metrics, and the last queue run.

## Migration from v0.5.0

New tables:

```text
{prefix}sc_ei_privacy_requests
{prefix}sc_ei_consent_events
{prefix}sc_ei_legal_holds
{prefix}sc_ei_retention_policies
{prefix}sc_ei_retention_actions
```

New inquiry fields:

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

Migration is non-destructive. It does not queue or execute existing records merely because the plugin was upgraded.

## Legal boundary

This plugin provides technical controls and evidence.

It does not determine:

- applicable jurisdiction
- legal basis
- mandatory retention period
- preservation obligations
- identity-verification sufficiency
- whether an erasure exception applies
- whether a legal hold should be placed or released

Obtain appropriate legal and professional review for production policies.
