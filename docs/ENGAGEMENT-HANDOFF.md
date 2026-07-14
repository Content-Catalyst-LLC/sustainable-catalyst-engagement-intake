# Proposal and Engagement Handoff

## Purpose

Convert a contracted proposal into an auditable operational record without treating portal acceptance or contract recording as automatic permission to begin work.

## Source eligibility

A proposal is eligible only when:

```text
status = contracted
current published version exists
content hash exists
external contract reference exists
no engagement already references the proposal
inquiry has not been erased
```

## Handoff transaction

The handoff creates:

1. `handoff_pending` engagement
2. immutable contracted-proposal snapshot
3. snapshot linkage
4. default onboarding requirements
5. handoff event
6. snapshot event

A failure rolls back all six operations.

## Commercial record boundary

```text
binding commercial record = separately executed external agreement
portal proposal acceptance = intent to proceed
engagement handoff = internal operational preparation
engagement activation = human authorization to begin the recorded engagement
```

The plugin is not an electronic signature or contract-generation service.

## Readiness

Required items must be `complete` or `waived`. A waiver requires a note and human actor.

Readiness also checks proposal state, exact source version, owner, contract reference, snapshot integrity, and privacy restrictions.

## Lifecycle

### Handoff pending

The snapshot and checklist exist. Delivery is not active.

### Ready for setup

All required readiness checks passed through a typed human action. Delivery is still not active.

### Active

An authorized manager reran readiness and entered the typed activation confirmation.

### Paused

Delivery is temporarily stopped with a recorded reason.

### Completed

Delivery is recorded complete with a completion note.

### Canceled

The handoff or engagement is closed with a reason. Historical records remain governed by retention and privacy policy.

## Integration handoff

The private JSON package can support later Workbench or Decision Studio ingestion. v0.10.0 only prepares and exports data. It does not create projects, packets, accounts, repositories, invoices, or payments.

## Incident handling

### Duplicate handoff attempt

Open the existing engagement linked to the contracted proposal. Do not remove the unique constraint.

### Snapshot integrity failure

Do not mark the handoff ready. Compare the stored payload and hash, review database or migration changes, and preserve evidence.

### Proposal state changed after handoff

Readiness fails until the source proposal is restored to its correct contracted state or the handoff is canceled through a documented decision.

### Privacy restriction appears

Activation fails closed. Review the privacy case before proceeding.
