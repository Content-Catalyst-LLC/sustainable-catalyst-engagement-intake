# Microsoft Teams and Calendar Coordination

## Operating model

Microsoft Teams is the only live meeting platform supported by the Contact and Engagement Platform. Scheduling is review-first. The public site may collect availability context, but it does not expose a live calendar or create meetings automatically.

## Meeting lifecycle

```text
draft
→ published / availability requested
→ accepted_pending_link
→ scheduled
→ completed or canceled
```

Expired and superseded offers remain historical records. Rescheduling updates the confirmed UTC interval, preserves the prior interval, increments the reschedule counter, and records the authorized actor and time.

## Meeting types

- Advisory discovery
- Sustainable AI Assurance review
- Product-support troubleshooting
- Research collaboration
- Institutional discussion
- Media or interview
- Workshop planning
- Proposal review
- Engagement review
- Project closeout
- Other approved meeting

## Time zones

Every scheduled meeting requires an IANA time-zone identifier. Local times are converted to UTC for storage. Sender-facing displays convert the confirmed UTC time into the approved meeting time zone.

## Microsoft Teams links

Only approved Microsoft Teams hosts are accepted. Canceled, expired, and superseded meetings must not expose an active Teams or Graph join URL. Local cancellation revokes the sender-visible link immediately; remote Graph deletion remains a separate authorized operation.

## Reminders

The platform creates idempotent reminder records for invitation, 24-hour, one-hour, rescheduled, canceled, and post-meeting events. Background processing may mark reminders ready for review. It does not send them automatically. An authorized administrator reviews the communication and links a sent communication record to the reminder.

## Sender Portal

The sender allowlist contains only approved meeting status, time, time zone, active link, agenda, preparation requests, sender summary, sender next step, post-meeting summary, cancellation reason, and reschedule count. Organizer and participant details, internal notes, decisions, open questions, Graph internals, and follow-up ownership remain private.

## Post-meeting workflow

Authorized administrators may record completion, no-show state, private notes, a sender-visible summary, decisions, open questions, follow-up owner and due date, and a linked lifecycle task. Overdue follow-up without a task is a production-readiness blocker.

## Microsoft Graph

Graph integration is optional and human-triggered. The local meeting remains the canonical engagement record. Stable transaction identifiers, idempotent queue operations, reconciliation, and explicit cancellation controls prevent duplicate or stale remote events.
