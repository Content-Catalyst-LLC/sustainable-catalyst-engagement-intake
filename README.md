# Sustainable Catalyst Engagement Intake

**Version:** 0.9.0  
**Release:** Teams Scheduling and Proposal Workflow

## Public surfaces

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
[sc_sender_portal title="Secure Sender Portal"]
```

## Workflow architecture

```text
public intake
→ administrative review
→ human fit assessment
→ secure sender portal
→ human-published Teams offer
→ sender response
→ human-finalized Teams record
→ human-authored proposal
→ sender intent response
→ external contract attestation
```

No workflow stage automatically creates the next legally or operationally significant stage.

## Teams scheduling

Meeting offer states:

```text
draft
offered
accepted_pending_link
scheduled
alternative_requested
declined
completed
canceled
expired
superseded
```

Staff creates one or more time slots. Slots are stored in UTC and displayed using the offer timezone.

Sender responses:

```text
accept a selected slot
request an alternative
decline
```

A selected slot becomes `accepted_pending_link` unless a valid Teams URL is already present.

Only an authorized staff member can finalize:

```text
SCHEDULE <MEETING-OFFER-NUMBER>
```

The URL must pass the existing Microsoft Teams URL validator.

The system never calls Microsoft Graph or creates a Microsoft 365 calendar event.

## Authenticated ICS

A sender can download an ICS file only when:

```text
portal session active
+ view_meetings permission
+ inquiry ownership
+ meeting status scheduled
+ selected start and end present
+ valid Microsoft Teams URL
```

The file uses `METHOD:PUBLISH`; it is not a server-created calendar invitation.

## Proposal model

Proposal record:

```text
status
sender response
current published version
pending unpublished version
currency and total
expiration
publication metadata
external contract metadata
```

Proposal version:

```text
version number
structured content
version note
SHA-256 content hash
creator
created timestamp
```

## Stable published revisions

Published and draft content are separated:

```text
current_version_id → sender-visible published version
pending_version_id → administrative unpublished revision
```

Creating a new version does not hide or mutate the published version.

Publishing performs:

```text
pending_version_id → current_version_id
pending_version_id → null
status → published
publication audit
```

## Sender proposal response

Acceptance confirmation:

```text
ACCEPT <PROPOSAL-NUMBER>
```

Decline confirmation:

```text
DECLINE <PROPOSAL-NUMBER>
```

Acceptance requires:

```text
authority attestation
non-contract acknowledgment
typed confirmation
active portal session
respond_proposals permission
unrestricted privacy state
unexpired published proposal
optimistic row version
```

Acceptance results in:

```text
accepted_pending_contract
```

It does not create an active engagement or executed agreement.

## External contract recording

Only authorized staff can use:

```text
CONTRACT <PROPOSAL-NUMBER>
```

Required:

```text
external contract reference
administrative note
record-contract capability
accepted_pending_contract state
```

The record means an agreement was executed outside the plugin.

The plugin does not perform:

```text
electronic signature
contract generation
payment collection
invoice creation
automatic acceptance
```

## Proposal print view

The print-friendly view requires the sender portal session and proposal ownership.

Headers include:

```text
Cache-Control: no-store
Referrer-Policy: no-referrer
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Content-Security-Policy with default-src none
```

No inline script is used.

## New tables

```text
{prefix}sc_ei_meeting_offers
{prefix}sc_ei_proposals
{prefix}sc_ei_proposal_versions
{prefix}sc_ei_workflow_events
```

## New capabilities

```text
sc_intake_view_workflow
sc_intake_manage_workflow
sc_intake_create_meeting_offers
sc_intake_publish_meeting_offers
sc_intake_finalize_meetings
sc_intake_create_proposals
sc_intake_publish_proposals
sc_intake_record_contracts
sc_intake_export_workflow
```

Reviewers can view workflow records and prepare drafts.

Managers can publish offers, finalize meetings, publish proposals, and record externally executed contracts.

## Portal permissions

```text
view_meetings
respond_meetings
view_proposals
respond_proposals
```

Privacy restrictions preserve read access while blocking new meeting and proposal responses.

## Privacy

Workflow export includes:

- meeting slots and responses
- final Teams record
- proposal metadata
- all proposal versions
- version hashes
- sender response
- external contract state
- workflow events

Approved erasure redacts personal narratives while preserving limited categorical lifecycle evidence.

## Production checklist

1. Back up database and protected storage.
2. Upgrade to v0.9.0.
3. Clear all caches.
4. Confirm DB 0.9.0.
5. Confirm portal schema 1.2.0.
6. Confirm workflow schema 1.0.0.
7. Confirm four workflow tables.
8. Confirm workflow capabilities.
9. Confirm hourly cleanup.
10. Test Teams offer publication.
11. Test sender slot response.
12. Test final Teams URL.
13. Test authenticated ICS.
14. Test initial proposal publication.
15. Test unpublished revision stability.
16. Test revision publication.
17. Test typed acceptance and decline.
18. Test contract attestation.
19. Test expiration.
20. Test privacy export and erasure.
