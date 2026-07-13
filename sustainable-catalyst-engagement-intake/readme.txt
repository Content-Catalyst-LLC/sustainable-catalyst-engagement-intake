=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: microsoft graph, microsoft teams, calendar, scheduling, proposals, sender portal, privacy, quarantine
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.9.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optional Microsoft Graph calendar-backed Teams creation with encrypted credentials, idempotent retries, reconciliation, circuit breaking, human controls, manual fallback, proposals, privacy, and protected intake.

== Description ==

Version 0.9.1 adds an optional Microsoft Graph Reliability connector to the human-approved Teams Scheduling and Proposal Workflow introduced in v0.9.0.

The existing manual Microsoft Teams URL workflow remains available at all times.

The connector is designed for:

* One Microsoft Entra tenant
* Application-only client-credentials authentication
* Microsoft Graph v1.0
* Microsoft global cloud
* One configured Microsoft 365 organizer mailbox
* The organizer default calendar or one configured calendar ID
* Human-triggered calendar event creation
* Microsoft Teams online meetings attached to Outlook calendar events

== Required Microsoft configuration ==

The Entra application requires:

* Microsoft Graph application permission `Calendars.ReadWrite`
* Administrator consent
* A client secret
* An organizer user principal name
* Calendar access for the configured organizer

Because application `Calendars.ReadWrite` can otherwise authorize broad mailbox access, production deployments should use Exchange Online Application RBAC to scope the application to the intended organizer mailbox or mailbox set.

Legacy Application Access Policies are not the preferred new deployment model.

v0.9.1 does not support sovereign-cloud Graph endpoints.

== Credential security ==

The connector stores:

* Tenant identifier
* Client identifier
* Client secret
* Organizer user
* Optional calendar identifier
* Secret expiry metadata

The credential vault is stored separately from ordinary plugin settings.

The client secret is protected using:

* Sodium secretbox when available
* OpenSSL AES-256-GCM fallback

Encryption keys are derived from WordPress salts.

The client secret is never redisplayed.

Cached access tokens are also encrypted and stored in short-lived site transients.

Rotating or clearing credentials invalidates the previous token cache.

== Human-triggered event creation ==

A Graph event can be created only when:

1. A sender has accepted one approved meeting time.
2. The local meeting state is `accepted_pending_link`.
3. An authorized staff member enters `GRAPH <MEETING-OFFER-NUMBER>`.
4. The connector is enabled and healthy.
5. Credentials are complete.
6. The meeting still has the same eligible local state.

The Graph connector does not automatically create events merely because a sender selected a time.

== Idempotency ==

Each meeting receives one persistent Graph `transactionId`.

The durable create operation uses:

* The persistent transaction ID
* A unique local idempotency key
* A SHA-256 request hash
* An encrypted request payload
* An optimistic queue claim

A retry reuses the same payload, idempotency key, and transaction ID.

This protects against duplicate events after timeouts or ambiguous transport failures.

== Durable operation queue ==

The connector stores create, reconcile, and delete operations in:

`{prefix}sc_ei_graph_operations`

Operation states include:

* pending
* processing
* retry_wait
* succeeded
* permanent_failure
* canceled

The queue records:

* Attempt count
* Maximum attempts
* Scheduling and retry times
* Lock token and lock time
* HTTP method and endpoint path
* Response status
* Graph error code
* Retry-After delay
* Microsoft request ID
* Client request ID
* Encrypted request payload
* Redacted response snapshot
* Human actor
* Audit context

== Retry behavior ==

Retryable responses include common throttling and transient service codes.

The connector:

* Honors `Retry-After`
* Uses bounded exponential backoff with jitter when no retry delay is supplied
* Limits total attempts
* Recovers stale processing locks
* Runs an hourly catch-up job
* Opens a circuit breaker after repeated failures
* Allows a human to reset the circuit
* Allows a human to retry a permanent failure using the same idempotency data

== Teams link reconciliation ==

The event is created with:

* `isOnlineMeeting = true`
* `onlineMeetingProvider = teamsForBusiness`
* UTC start and end
* The persistent `transactionId`

The connector reads the supported `onlineMeeting.joinUrl`.

If the event exists but the join URL is still initializing, the connector queues a reconciliation operation.

The local meeting is finalized only after a valid Microsoft Teams join URL is available.

Reconciliation cannot reopen canceled, completed, declined, or superseded local meetings.

== Sender attendee setting ==

Sender attendee inclusion is disabled by default.

When enabled:

* Calendar consent must be recorded.
* The sender is added as a required event attendee.
* Microsoft 365 can send the attendee a calendar invitation as part of the human-triggered event creation.

This is distinct from the plugin email notification system.

== Remote deletion ==

Authorized staff can enter:

`DELETE GRAPH <MEETING-OFFER-NUMBER>`

Deleting an organizer event can cause Microsoft 365 to send cancellation notices to attendees.

Remote deletion is therefore:

* Disabled unless permitted in connector settings
* Capability gated
* Nonce protected
* Typed-confirmation protected
* Audited
* Queued with retry controls

Local meeting cancellation never silently deletes the remote event. It marks Graph follow-up as required.

== Manual fallback ==

The original v0.9.0 manual finalization remains available:

`SCHEDULE <MEETING-OFFER-NUMBER>`

Staff can paste a valid Teams URL even when:

* Graph is disabled
* Credentials are incomplete
* The circuit is open
* Microsoft Graph is unavailable
* A permanent Graph failure occurred
* The deployment does not use Microsoft 365

== Privacy and export ==

Graph operation records are included in:

* Private data inventory
* WordPress privacy export
* Workflow export
* Redacted Graph operations export
* Diagnostics
* Approved erasure

Exports never include:

* Client secret
* Access token
* Decrypted request payload
* Encryption key

Approved erasure removes:

* Encrypted operation payload
* Graph error narratives
* Response snapshot
* Event identifiers
* Calendar UID
* Join URL
* Web link
* Graph event context

A remote Microsoft 365 event is not automatically deleted during privacy erasure. It must be reviewed and handled separately under the organization’s Microsoft 365 retention and legal obligations.

== Installation ==

1. Back up the WordPress database and protected storage.
2. Upgrade from v0.9.0 to v0.9.1.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Engagement Intake → Diagnostics.
5. Confirm database version `0.9.1`.
6. Confirm workflow schema `1.1.0`.
7. Confirm Graph schema `1.0.0`.
8. Confirm the Graph operations table and meeting linkage fields.
9. Confirm the hourly Graph catch-up event.
10. Create or identify the Microsoft Entra application.
11. Grant application `Calendars.ReadWrite`.
12. Grant administrator consent.
13. Scope mailbox access with Exchange Application RBAC.
14. Open Engagement Intake → Microsoft Graph.
15. Save encrypted tenant, client, secret, organizer, and calendar settings.
16. Enable the connector.
17. Run `TEST GRAPH`.
18. Test one accepted meeting in staging.
19. Confirm one remote calendar event.
20. Confirm the Teams join URL reconciliation.
21. Test a throttled or simulated retry.
22. Test manual fallback.
23. Test remote cancellation behavior.
24. Test privacy and redacted export behavior.

== Changelog ==

= 0.9.1 =
* Added optional Microsoft Graph application connector.
* Added authenticated encryption for client credentials.
* Added encrypted token caching.
* Added client-secret expiry and fingerprint metadata.
* Added application-only token acquisition.
* Added correct `https://graph.microsoft.com/.default` scope.
* Added global v1.0 Graph endpoint restriction.
* Added configurable organizer mailbox and calendar.
* Added human-triggered event creation.
* Added persistent Graph transaction IDs.
* Added encrypted durable operation payloads.
* Added idempotent create operations.
* Added optimistic queue claims.
* Added stale-lock recovery.
* Added Retry-After support.
* Added bounded exponential backoff with jitter.
* Added maximum attempts.
* Added circuit breaking.
* Added one-time token refresh after 401.
* Added Graph request and client-request IDs.
* Added Teams join URL reconciliation.
* Added local-state race protection.
* Added remote cancellation.
* Added manual retry preserving idempotency.
* Added manual linkage reset.
* Added connector health tests.
* Added Graph queue administration and redacted export.
* Added Graph privacy, erasure, workflow, inquiry, and Diagnostics integration.
* Preserved the manual Teams URL workflow.
* Preserved no automatic contract, signature, invoice, payment, or engagement activation.

= 0.9.0 =
* Added Teams Scheduling and Proposal Workflow.

= 0.8.1 =
* Added Portal Authentication and Recovery Patch.

= 0.8.0 =
* Added Secure Sender Portal.

= 0.7.0 =
* Added Human-Controlled Fit Assessment.

= 0.6.0 =
* Added Privacy and Retention Center.

= 0.5.0 =
* Added Notifications and Communication History.

= 0.4.0 =
* Added Administrative Review Workspace.
