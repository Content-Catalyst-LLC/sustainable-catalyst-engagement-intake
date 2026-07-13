# Microsoft Graph Reliability

## Supported deployment

v0.9.1 supports:

```text
Microsoft global cloud
Microsoft Graph v1.0
single tenant
application-only authentication
one configured organizer mailbox
default or named organizer calendar
```

It does not support sovereign cloud endpoints in this release.

## Entra application

Configure:

```text
Supported account type: single tenant
Microsoft Graph application permission: Calendars.ReadWrite
Admin consent: granted
Client credential: secret
```

Do not grant Mail permissions, Teams chat permissions, OnlineMeetings permissions, or other unrelated permissions for this connector.

## Exchange mailbox scope

Application `Calendars.ReadWrite` is broad unless further scoped.

Use Exchange Online Application RBAC to assign the calendar role to the application and restrict the resource scope to the intended organizer mailbox or mailbox set.

Application Access Policies are a legacy scoping model and are not recommended for new deployments.

## Credential rotation

1. Create a new Entra client secret.
2. Open Engagement Intake → Microsoft Graph.
3. Enter the new secret.
4. Update its expiry date.
5. Type `SAVE GRAPH SETTINGS`.
6. Run `TEST GRAPH`.
7. Remove the old secret in Entra after successful validation.

Saving a replacement:

```text
encrypts the new secret
changes the secret fingerprint
clears the old token cache
clears the new token cache
resets the connector circuit
```

## Human-triggered creation

Graph event creation requires:

```text
accepted_pending_link
+ create_graph_events capability
+ valid nonce
+ GRAPH <OFFER-NUMBER>
```

The background worker never discovers accepted meetings and creates events by itself.

It processes only operations that a human already queued.

## Request identity

Each create payload contains:

```text
transactionId = graph_transaction_id
```

The local queue contains:

```text
idempotency_key = SHA-256(create|meeting-id|transaction-id)
request_hash = SHA-256(canonical payload)
```

## Failure classification

Retryable:

```text
network transport
408
425
429
500
502
503
504
join URL still initializing
circuit temporarily open
```

Permanent:

```text
invalid credentials after bounded retries
invalid permission
invalid organizer
invalid calendar
invalid payload
local state no longer eligible
maximum attempts exhausted
```

## Backoff

```text
Retry-After when supplied
otherwise base × 2^(attempt-1) + jitter
capped at configured maximum
```

## Circuit breaker

The connector tracks consecutive failures.

At the configured threshold:

```text
open_until = now + cooldown
```

New Graph calls fail closed while the circuit is open.

Manual Teams URL finalization remains available.

## Join URL reconciliation

Create-event success can precede availability of the Teams join URL.

The connector stores the event ID and schedules a GET reconciliation.

It reads:

```text
onlineMeeting.joinUrl
```

A valid Teams URL is copied into the sender-safe local Teams URL field.

## Remote cancellation

Deleting an organizer event can send cancellation notices to attendees.

Before deletion:

1. review local meeting state
2. review attendee inclusion
3. confirm legal or retention obligations
4. type `DELETE GRAPH <OFFER-NUMBER>`

Local cancellation does not automatically delete the remote event.

## Incident response

### Credential compromise

1. Disable the connector.
2. Remove or expire the Entra secret.
3. Clear the encrypted token cache.
4. Reset the circuit.
5. Enter a replacement secret.
6. Run the health test.
7. Review Graph operations and request IDs.

### Duplicate-event concern

1. Do not reset the transaction ID immediately.
2. Retry the same failed operation.
3. Reconcile an existing event ID when available.
4. Inspect the organizer calendar.
5. Reset linkage only after confirming the remote event is absent or intentionally deleted.

### Persistent throttling

1. Respect the queued retry time.
2. Do not repeatedly run manual processing.
3. Review Retry-After and request IDs.
4. Confirm no other application is saturating the mailbox.
5. Leave manual Teams finalization available.

### Remote/local divergence

1. Run `RECONCILE <OFFER-NUMBER>`.
2. Compare remote and local UTC times.
3. Review cancellation state.
4. Delete or repair the remote event as a separate human action.
5. Record why local linkage is reset.
