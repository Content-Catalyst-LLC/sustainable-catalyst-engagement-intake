# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.0.2  
**Release:** Production Readiness and Live Validation

v1.0.2 turns the production gate into a runtime-backed launch process. It adds guided repair actions, verified page and shortcode contracts, cron callback evidence, rendered accessibility evidence, an administrator-run live validation suite, recent backup attestation, and a stricter 100% production requirement. The database remains at 1.0.0; the platform evidence schema advances to 1.0.1.

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

1. Back up the WordPress database and protected document storage.
2. Install v1.0.2 over the existing plugin.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Use the guided repair center until configuration and runtime checks pass.
6. Confirm the Contact page contains `[sc_contact_engagement_platform]`.
7. Confirm the Sender Portal page contains `[sc_sender_portal]`.
8. Configure support email and a published Privacy Policy URL.
9. Run **Live Validation** with a monitored email recipient and confirm inbox delivery manually.
10. Back up the database and protected storage, then record the backup attestation.
11. Require 100%, zero failures, zero warnings, fresh validation evidence, and fresh backup evidence before recording Production.
12. Keep human review, fit, proposal, contract, scheduling, and engagement decisions manual.
