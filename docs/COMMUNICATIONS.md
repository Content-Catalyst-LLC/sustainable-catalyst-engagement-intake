# Communication Operations

## Core truth model

The plugin distinguishes:

```text
draft
approved
sending
accepted
failed
canceled
received
recorded
suppressed
```

`accepted` means only that `wp_mail()` returned success.

## Compose and send

### Draft

Drafts can be changed by authorized users and use optimistic version locking.

### Reviewed send

Before sending, verify:

- correct inquiry
- recipient name and address
- CC recipients
- message type
- subject
- complete plain-text body
- privacy classification
- sender suppression
- absence of confidential document content that belongs in protected storage

### Retry

Retry is available only for failed email records.

The attempt count and each failure remain in the event history.

### Cancel

Canceling preserves the communication and events but removes it from normal active history views.

## Manual interaction records

Use manual recording for:

- inbound email
- email sent outside WordPress
- Microsoft Teams message
- Microsoft Teams meeting
- phone
- video
- in-person
- internal conversation
- other channel

Record a summary sufficient for operational continuity without copying unnecessary sensitive information.

## Inbound response state

An inbound record can be marked `needs response`.

This:

- increments unread inbound
- sets communication state to waiting on internal
- does not notify the sender
- does not create an automatic reply

## Follow-up states

```text
open
waiting_on_sender
waiting_on_internal
follow_up_due
paused
closed
```

A next follow-up date can be set independently.

## Email suppression

Set do-not-email when:

- sender requested no email
- address is invalid or bouncing
- privacy or legal restriction applies
- contact identity is uncertain
- a security or abuse concern applies
- correspondence should move to another approved channel

A reason is required.

## Private CSV export

The export contains message bodies and addresses.

Treat it as confidential.

Do not upload it to public spreadsheets, public repositories, analytics tools, or unapproved AI systems.

## No mailbox ingestion

v0.5.0 does not read Gmail, Outlook, Exchange, Teams, or an IMAP mailbox.

Inbound records are manual.

## No document attachments

Never attach quarantined documents to outgoing email.

Use controlled document access and future sender-portal functionality instead.
