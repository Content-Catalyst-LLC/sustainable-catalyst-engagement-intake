# Release Notes — v0.5.0

## Purpose

Create a private, auditable correspondence layer that connects inquiry intake and human review to sender communication without turning the plugin into an uncontrolled email automation system.

## Operating boundary

The communication layer is designed around four rules:

1. saving is not sending
2. transport acceptance is not delivery
3. private documents are not email attachments
4. automated policies are opt-in

## Manual email workflow

An authorized user:

1. opens the inquiry communication thread
2. selects or writes a plain-text message
3. saves a version-locked draft
4. reviews the rendered recipient, subject, body, and privacy classification
5. confirms the send
6. receives either an accepted or failed mail-transport result
7. reviews immutable delivery events and attempts

## Automatic policy workflow

An enabled policy:

1. evaluates a specific trigger
2. creates a deduplicated immutable communication
3. renders a versioned template
4. uses the same mail transport and event recording
5. records accepted, failed, or suppressed state
6. never attaches a file
7. never changes inquiry fit or status

## Communication history

History includes messages sent from WordPress and manually recorded interactions completed elsewhere.

This allows Teams, phone, video, and in-person activity to be represented without claiming a live external integration.

## Follow-up

Each inquiry can hold:

- communication state
- next follow-up date
- last communication timestamps
- unread inbound count
- do-not-email state and reason

Internal follow-up reminders can be enabled separately.

## Templates

Templates are plain text and versioned.

The template used by a message is retained by key and version even after a new version becomes active.

## Migration

v0.5.0 creates three tables and adds communication fields to inquiries.

Migration does not:

- send email
- enable automation
- create historical messages
- mark email delivered
- import mailboxes
- connect Microsoft Graph

## Recommended live verification

1. Back up database and protected storage.
2. Upgrade to v0.5.0.
3. Confirm database and communication table migration.
4. Confirm templates were seeded.
5. Configure a domain-authorized sender and reply-to address.
6. Keep automated policies off.
7. Run the transport test.
8. Confirm actual receipt separately.
9. Create and edit a draft.
10. Open the same draft in two sessions and confirm stale-save rejection.
11. Send a test message.
12. Test an invalid mail transport in staging and confirm failure history.
13. Test retry.
14. Test cancel.
15. Enable do-not-email and confirm suppression.
16. Record inbound email and Teams meeting history.
17. Set follow-up and test the hourly reminder in staging.
18. Export communication CSV.
19. Test privacy export.
20. Test privacy erasure in staging.
