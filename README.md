# Sustainable Catalyst Engagement Intake

**Version:** 0.9.1  
**Release:** Microsoft Graph Reliability Patch

## Connector model

```text
sender accepts approved time
→ authorized staff queues Graph create
→ encrypted durable operation
→ app-only Microsoft Graph v1.0
→ Outlook calendar event with Teams meeting
→ join URL reconciliation
→ sender-safe finalized meeting
```

The manual Teams URL path remains fully supported.

## Microsoft prerequisites

```text
single-tenant Microsoft Entra application
Microsoft Graph application Calendars.ReadWrite
administrator consent
client secret
Microsoft 365 organizer mailbox
Exchange Online Application RBAC scope
```

Application RBAC should limit the service principal to the intended organizer mailbox or mailbox set.

## OAuth

```text
POST /{tenant}/oauth2/v2.0/token
grant_type=client_credentials
scope=https://graph.microsoft.com/.default
```

The token is cached only in an authenticated encrypted site transient.

## Secret storage

```text
sc_ei_graph_credentials
```

The separate option contains an authenticated encryption envelope.

Preferred algorithm:

```text
sodium secretbox
```

Fallback:

```text
OpenSSL AES-256-GCM
```

No client secret or access token appears in the settings UI, diagnostics export, workflow export, or operation export.

## Calendar event creation

The connector uses:

```text
POST /users/{organizer}/calendar/events
```

or:

```text
POST /users/{organizer}/calendars/{calendar-id}/events
```

Payload controls:

```text
isOnlineMeeting = true
onlineMeetingProvider = teamsForBusiness
start/end timezone = UTC
transactionId = persistent meeting UUID
sensitivity = private
```

The event also receives two legacy single-value extended properties containing the local offer number and public meeting ID.

## Idempotency

```text
graph_transaction_id
idempotency_key
request_hash
encrypted payload
```

A repeated create operation uses the same Graph `transactionId`.

A manually retried permanent failure uses the same operation identity and payload.

## Durable operation table

```text
{prefix}sc_ei_graph_operations
```

Operations:

```text
create
reconcile
delete
```

States:

```text
pending
processing
retry_wait
succeeded
permanent_failure
canceled
```

## Reliability

```text
optimistic queue claim
15-minute stale-lock recovery
Retry-After support
exponential backoff with jitter
bounded maximum attempts
circuit breaker
hourly catch-up
one-time token refresh after 401
request-id capture
client-request-id capture
manual queue processing
manual same-operation retry
```

## Reconciliation

The supported sender join link comes from:

```text
onlineMeeting.joinUrl
```

The connector does not use deprecated `onlineMeetingUrl`.

If the event is created before the Teams join URL becomes available:

```text
created_pending_join_url
→ reconcile_queued
→ retry_wait
→ synced
```

## Local state protection

A Graph create operation is permitted only while:

```text
meeting.status = accepted_pending_link
```

Reconciliation can update remote metadata but cannot reopen:

```text
canceled
completed
declined
superseded
```

## Attendees

Default:

```text
graph_include_sender_attendee = false
```

When enabled:

```text
calendar_invite_consent = true
```

is required before the sender is added.

Microsoft 365 can send attendee invitations or cancellation notices as a consequence of the human-triggered calendar action.

## Human controls

```text
SAVE GRAPH SETTINGS
TEST GRAPH
RESET GRAPH CIRCUIT
CLEAR GRAPH TOKEN
PROCESS GRAPH QUEUE
GRAPH <OFFER-NUMBER>
RECONCILE <OFFER-NUMBER>
DELETE GRAPH <OFFER-NUMBER>
RESET GRAPH <OFFER-NUMBER>
RETRY GRAPH <OPERATION-ID>
```

## Capabilities

```text
sc_intake_view_graph
sc_intake_manage_graph_settings
sc_intake_create_graph_events
sc_intake_reconcile_graph_events
sc_intake_cancel_graph_events
sc_intake_export_graph_operations
```

Reviewers receive view access.

Managers can create, reconcile, cancel, retry, and export Graph operations.

Only administrators receive credential-management access.

## Manual fallback

```text
SCHEDULE <OFFER-NUMBER>
```

remains available and independent of the Graph connector.

## Privacy boundary

Graph privacy export includes operation status, attempts, timestamps, HTTP status, safe error code, request ID, and client request ID.

It excludes decrypted payloads and all credentials.

Approved erasure redacts local Graph identifiers and personal operation narratives.

Remote Microsoft 365 deletion remains a separate reviewed action.

## Upgrade checklist

1. Back up database and protected storage.
2. Upgrade to v0.9.1.
3. Clear caches.
4. Confirm DB 0.9.1.
5. Confirm workflow schema 1.1.0.
6. Confirm Graph schema 1.0.0.
7. Confirm Graph operation table.
8. Confirm expanded meeting fields.
9. Confirm capabilities.
10. Confirm Graph catch-up cron.
11. Configure Entra application permission.
12. Scope mailbox access.
13. Save encrypted credentials.
14. Enable connector.
15. Test calendar access.
16. Create one staging event.
17. Reconcile join URL.
18. Test manual fallback.
19. Test retry and circuit controls.
20. Test redacted export and privacy erasure.
