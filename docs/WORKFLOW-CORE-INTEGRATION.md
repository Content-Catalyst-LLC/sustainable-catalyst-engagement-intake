# Workflow Core Integration

## Purpose

Workflow Core provides one private integration contract across Engagement Intake and cooperating Sustainable Catalyst plugins without merging public and private data stores or transferring decision authority.

## Source-of-truth rule

The following remain authoritative:

- inquiry and administrative review records
- fit assessments and human recommendations
- portal access and communications
- Teams meeting and Microsoft Graph records
- proposals and externally recorded contracts
- engagement handoff and lifecycle records
- privacy, consent, retention, and legal-hold records

The canonical case projection reads those records. It does not write their decision fields.

## Projection consistency

A case can be:

```text
consistent
warning
blocked
stale
```

Examples of blockers:

- privacy processing restriction
- engagement without contracted proposal
- missing contract reference
- permanent Teams-link creation failure
- incomplete required engagement readiness items

Examples of warnings:

- overdue review
- contracted proposal without engagement handoff
- inquiry status lagging proposal state

A human consistency resolution records that the projection was reviewed. It does not silently repair authoritative records.

## Contract and signature

```text
schema: sc-engagement-workflow-handoff/1.0
hash: SHA-256(canonical JSON)
signature: HMAC-SHA-256(schema | target | hash, WordPress-derived key)
```

Changing the target, payload, hash, or signature invalidates verification.

## Internal adapter model

Adapters register programmatically inside WordPress. The administration interface never accepts an arbitrary URL.

The adapter receives:

```php
array $event
array $payload
```

It returns:

```php
array( 'acknowledged' => true )
```

or a `WP_Error` for bounded retry.

## Delivery guarantees

The outbox is at-least-once. Target plugins must use the event key, handoff public ID, and content hash for idempotent import.

The source prevents duplicate local outbox rows through a unique event key. It cannot guarantee exactly-once execution inside another plugin.

## Private-data rule

Operational-minimum packages omit direct contact identity and narrative content.

Internal-private packages require:

```text
sc_intake_export_workflow_core_private
```

Use internal-private only for an authorized target with a documented purpose and compatible retention controls.

## Recovery

- stale processing claims return to retry waiting
- failed adapter calls use bounded exponential backoff
- handoffs expire after the configured period
- dispatch can be triggered manually
- failed handoffs remain auditable
- no automatic destructive repair occurs
