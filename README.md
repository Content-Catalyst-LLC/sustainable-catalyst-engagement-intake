# Sustainable Catalyst Engagement Intake

**Version:** 0.5.0  
**Release:** Notifications and Communication History

v0.5.0 adds a private communication operating layer:

```text
Public intake
→ protected inquiry
→ quarantine and review
→ communication draft
→ human review and explicit send
→ transport event history
→ follow-up
```

## Public shortcodes

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
```

```text
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
```

## Communications workspace

Open:

```text
Engagement Intake → Communications
```

Views:

- All History
- Drafts
- Failed
- Inbound
- Follow-up Due
- Notifications
- Templates
- Notification Policy

Each inquiry has a private communication thread containing:

- version-locked drafts
- reviewed send preview
- outbound email
- inbound email records
- Teams message and meeting records
- phone, video, and in-person records
- internal notes
- delivery and change events
- follow-up state
- unread inbound count
- do-not-email control
- transport attempts and failures

## Delivery truthfulness

The plugin records:

```text
accepted = WordPress mail transport accepted the message
failed   = WordPress mail transport returned failure
```

It does not claim:

```text
delivered
in inbox
opened
read
clicked
```

Those states require a separately configured mail provider and verified webhook integration, neither of which is bundled in v0.5.0.

## Human-controlled send sequence

```text
compose
→ save draft
→ review recipient, subject, body, privacy classification, and suppression
→ check explicit send confirmation
→ send
→ record accepted or failed transport event
```

Saving a draft never sends it.

Accepted, received, recorded, canceled, and suppressed communication records are immutable through normal editing.

## Plain text and no attachments

All plugin email is plain text.

The communication layer never accepts an attachment argument and never copies quarantined documents into email.

Private documents remain available only through the authenticated document workflow.

## Automated notifications

All automated policies default to `false`:

```text
sender_acknowledgment_enabled
internal_new_inquiry_enabled
review_due_reminders_enabled
follow_up_reminders_enabled
escalation_notifications_enabled
```

Automation cannot be enabled with an invalid:

- sender name
- sender email
- reply-to email

Enabled reminders run through an hourly WordPress cron event with:

- a 30-minute cron lock
- configurable batch limit
- stable deduplication keys
- immutable records and events
- normal mail failure history
- no document attachments

## Default notification templates

- sender acknowledgment
- internal new inquiry
- internal review due
- internal follow-up due
- internal escalation

Sender-facing operational templates include:

- general response
- request more information
- Teams fit-call invitation
- Teams confirmation
- paid consultation invitation
- referral
- decline

Templates are versioned. A new save archives the previous active version instead of rewriting history.

## Template variables

Only allowlisted variables are rendered, including:

```text
{contact_name}
{first_name}
{reference}
{organization}
{subject}
{inquiry_type}
{service_interest}
{review_stage}
{fit_decision}
{recommended_next_step}
{teams_duration}
{teams_meeting_url}
{scheduled_start}
{scheduled_timezone}
{site_name}
{site_url}
{sender_name}
{reply_email}
{reviewer_name}
{review_due}
{next_follow_up}
```

Unknown variables are rejected. Templates do not execute PHP, shortcodes, JavaScript, or arbitrary expressions.

## Communication state

Inquiry-level fields:

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

States:

```text
open
waiting_on_sender
waiting_on_internal
follow_up_due
paused
closed
```

## Draft concurrency

Each draft has `row_version`.

When two sessions edit the same draft, the first successful save increments the version. The stale second save is rejected.

## Mail send locking

A short-lived option lock prevents the same communication from being sent twice concurrently.

A stale lock can be reclaimed after five minutes.

## Inbound and external interaction logging

v0.5.0 does not ingest email or Teams messages automatically.

Authorized users can record:

- inbound email
- outbound email completed elsewhere
- Teams message
- Teams meeting
- phone
- video
- in-person
- internal note
- other interaction

An inbound record can be marked as needing a response, which sets the thread to `waiting_on_internal`.

## Email suppression

`do_not_email` blocks email to the inquiry contact address.

A reason is required. The send attempt becomes `suppressed` and remains in history.

Internal notifications to other authorized recipients are not blocked by sender suppression.

## Private communication export

Authorized users can export communication history as CSV.

The export includes message content and is therefore private. Spreadsheet formula-leading characters are neutralized.

## Privacy

WordPress privacy export includes:

- inquiry communication state
- communication records
- delivery and change events
- sender and recipient information
- message content
- templates used
- attempts and error states

Privacy erasure removes:

- communication subjects and bodies
- sender and recipient names and emails
- CC recipients
- provider message IDs
- transport error messages
- message hashes
- deduplication keys
- event context
- suppression rationale
- review narratives
- scheduling personal data
- physical uploaded documents

Categorical event type, status, channel, timestamps, and internal actor IDs may remain for accountability.

## Microsoft Teams boundary

Microsoft Teams is the only live meeting platform represented in the public workflow.

v0.5.0 can:

- store Teams preferences
- store approved Teams meeting links and times
- create Teams message and meeting history records
- use Teams information in reviewed templates

It cannot:

- authenticate with Microsoft Graph
- send Teams messages
- create meetings
- read Teams replies
- synchronize calendars

## Review packet

The private review packet now includes communication history in addition to inquiry, review, attachment metadata, and audit history.

Physical documents remain excluded.

## Existing safety layers retained

- human administrative review
- no automated fit score
- no automatic inquiry status inference
- secure document quarantine
- scanner readiness and retries
- atomic protected storage
- SHA-256 verification
- retention controls
- privacy export and erasure
- Microsoft Teams-only meeting boundary
