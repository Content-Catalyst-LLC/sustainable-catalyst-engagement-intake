# Sustainable Catalyst Engagement Intake

**Version:** 1.0.1  
**Release:** Production Validation and Migration Reliability Patch

v1.0.1 repairs the stable platform diagnostics runtime path, preserves the v1.0.0 database and schema contract, and adds regression coverage for cross-class constant visibility. The underlying Workflow Core and governed contact-to-engagement architecture remain unchanged.

## Core model

```text
authoritative inquiry, review, fit, meeting, proposal, privacy, and engagement records
→ canonical case projection
→ idempotent human command
→ signed versioned handoff
→ durable outbox
→ registered internal WordPress adapter
→ explicit acknowledgment
```

The core derives state. It does not replace the domain repositories.

## Canonical cases

New table:

```text
{prefix}sc_ei_workflow_cases
```

Each case records:

- inquiry and public case identifiers
- canonical stage and state
- terminal state
- owner and priority
- source update timestamp
- projection version and SHA-256 hash
- blocker and pending-work counts
- last event and transition timestamps
- stale-after threshold
- consistency status and notes
- optimistic row version

Stages cover intake, review, fit, consultation, proposal, contracted, engagement handoff, active engagement, completed, and closed.

## Idempotent commands

New table:

```text
{prefix}sc_ei_workflow_commands
```

A command key is derived from:

```text
command type
+ case ID
+ expected projection hash
+ canonical payload hash
```

Repeating the same command returns the existing record rather than duplicating work. Commands use optimistic claims and record success or failure without mutating authoritative business decisions.

Supported commands include synchronization, handoff preparation, outbox dispatch, acknowledgment, cancellation, and consistency review.

## Signed handoff contract

New table:

```text
{prefix}sc_ei_workflow_handoffs
```

Contract schema:

```text
sc-engagement-workflow-handoff/1.0
```

Every package receives:

- canonical JSON ordering
- SHA-256 content hash
- HMAC-SHA-256 signature derived from WordPress salts
- target binding
- contract version
- data classification
- expiry timestamp
- human preparer and audit record

Default classification:

```text
operational_minimum
```

Private personal data is excluded by default. `internal_private` requires the dedicated private-export capability.

## Durable outbox

New table:

```text
{prefix}sc_ei_workflow_outbox
```

The outbox provides:

- unique event keys
- payload hashes
- optimistic claims
- attempt limits
- bounded exponential retry
- stale-claim recovery
- dispatched and acknowledged states
- redacted failure metadata

Dispatch supports registered internal WordPress adapters only.

There is no arbitrary URL field, no generic webhook posting, no direct `wp_remote_*` delivery, and no inbound command endpoint.

## Adapter API

A cooperating internal plugin registers a target callback:

```php
SC_EI_Workflow_Core_Service::register_adapter(
    'workbench',
    static function ( array $event, array $payload ): array {
        // Validate and import into the target plugin.
        return array( 'acknowledged' => true );
    }
);
```

Available target keys include:

```text
workbench
decision_studio
site_intelligence
research_librarian
platform_core
generic_internal
```

Targets can be extended with `sc_ei_workflow_core_handoff_targets`.

## Workflow Core workspace

```text
Engagement Intake → Workflow Core
```

The workspace provides:

- canonical case filtering
- consistency blockers and warnings
- typed case synchronization
- signed handoff preparation
- adapter registration visibility
- integrity verification
- handoff export
- dispatch and acknowledgment
- handoff cancellation
- command ledger
- outbox history
- runtime and schedule status

## Human controls

```text
SYNC WORKFLOW CORE
SYNC CASE <REFERENCE>
HANDOFF <REFERENCE> <TARGET>
DISPATCH OUTBOX
ACK HANDOFF <HANDOFF-ID>
CANCEL HANDOFF <HANDOFF-ID>
RESOLVE CASE <REFERENCE>
SAVE WORKFLOW CORE SETTINGS
```

Actions require capabilities, nonces, current-state checks, typed confirmation, and audit records.

## Fixed boundaries

Workflow Core does not:

- accept or reject inquiries
- rank senders
- finalize fit assessments
- publish proposals
- create or attest contracts
- activate engagements
- create external projects
- send arbitrary webhooks
- expose public write APIs
- execute inbound commands
- invoice, sign, or collect payment

## Privacy

Workflow Core records are included in WordPress privacy export and the private data inventory.

Approved erasure replaces personal command, handoff, and outbox payloads with integrity-preserving tombstones. Handoff hashes and signatures are recomputed for the replacement payload so the local ledger does not retain deliberately invalid integrity records.

## REST

Capability-gated read-only resources:

```text
GET /wp-json/sc-engagement-intake/v1/workflow-core/cases
GET /wp-json/sc-engagement-intake/v1/workflow-core/cases/{id}
```

No REST command endpoint is included.

## Upgrade checklist

1. Back up database and protected storage.
2. Upgrade to v0.12.0.
3. Clear all caches.
4. Confirm DB 0.12.0.
5. Confirm Workflow Core schema 1.0.0.
6. Confirm four new tables.
7. Confirm role capabilities.
8. Confirm sync and outbox cron hooks.
9. Run `SYNC WORKFLOW CORE`.
10. Review consistency warnings.
11. Register target adapters in staging.
12. Prepare, dispatch, acknowledge, and export one handoff.
13. Test privacy erasure and rollback behavior.
