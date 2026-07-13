# Teams Scheduling and Proposal Workflow

## Purpose

Move an engagement from reviewed inquiry to a controlled meeting and proposal process without automating legal acceptance, calendar booking, payment, or engagement activation.

## Meeting workflow

```text
draft
→ offered
→ accepted_pending_link or scheduled
→ scheduled
→ completed or canceled
```

Alternative paths:

```text
offered → alternative_requested
offered → declined
offered → expired
draft/offered → superseded
```

## Offer construction

An offer contains:

- title
- purpose
- timezone
- duration
- one to configured maximum slots
- preparation notes
- optional Teams URL
- expiration
- administrative note

Slots are sanitized, converted to UTC, sorted, and deduplicated.

## Offer publication

Publishing requires:

```text
PUBLISH <MEETING-OFFER-NUMBER>
```

Publication is a human action. It makes the offer visible in the sender portal and creates an audit event.

## Sender response

The sender can accept one exact slot, request an alternative, or decline.

The update uses:

```text
meeting ID
row version
status = offered
```

This prevents stale or repeated responses.

## Finalization

Finalization requires:

```text
SCHEDULE <MEETING-OFFER-NUMBER>
```

and a valid Microsoft Teams URL.

Finalization updates the sender-visible meeting record and inquiry scheduling fields.

It does not create a Microsoft 365 event.

## Proposal workflow

```text
draft
→ published
→ accepted_pending_contract or declined
→ contracted
```

Administrative paths:

```text
draft/published/accepted_pending_contract → withdrawn
published → expired
other proposal → superseded
```

## Version workflow

A proposal has:

```text
current_version_id
pending_version_id
```

The current version remains sender-visible.

A new draft version is saved to `pending_version_id`.

Publishing atomically promotes the pending version to current and clears the pending pointer.

Versions are never edited in place.

## Proposal response boundary

Sender acceptance requires:

```text
ACCEPT <PROPOSAL-NUMBER>
```

plus authority and non-contract acknowledgment.

It means:

```text
intent to proceed to external contracting
```

It does not mean:

```text
signed agreement
payment authorization
active work authorization
automatic inquiry acceptance
```

## External contract attestation

An authorized manager records:

```text
CONTRACT <PROPOSAL-NUMBER>
```

with a reference to the externally executed agreement.

Only this human administrative action changes the proposal to `contracted`.

## Expiration

Hourly cleanup expires only:

```text
offered meeting offers past expiration
published proposals past expiration
```

Accepted, scheduled, contracted, completed, declined, canceled, withdrawn, and superseded records are preserved.

## Incident handling

For an incorrect published offer or proposal:

1. withdraw, cancel, or supersede the record
2. create a corrected draft
3. review the sender-visible boundary
4. publish with typed confirmation
5. preserve prior versions and events
6. do not delete history to conceal the mistake
