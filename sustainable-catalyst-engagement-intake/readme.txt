=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: workflow, intake, integrations, sender portal, microsoft graph, analytics, privacy, security
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.12.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private engagement intake with a canonical workflow projection, idempotent human commands, signed cross-plugin handoffs, durable internal-adapter delivery, reliability, privacy, and protected document intake.

== Description ==

Version 0.12.0 adds Workflow Core Integration to the complete Sustainable Catalyst inquiry-to-engagement platform.

The Workflow Core derives one canonical case projection from the authoritative inquiry, review, fit, meeting, proposal, privacy, and engagement records. It does not replace or rewrite those records.

Highlights:

* Canonical case stage and state projection
* Projection version and SHA-256 fingerprint
* Consistency blockers and warnings
* Idempotent human command ledger
* HMAC-signed, versioned handoff packages
* Operational-minimum data classification by default
* Capability-gated internal-private handoffs
* Durable outbox with optimistic claims, bounded retries, and stale-claim recovery
* Explicit internal WordPress adapter registry
* Read-only Workflow Core REST resources
* Audit-driven deferred synchronization
* Privacy export and integrity-preserving erasure tombstones
* Reliability, Diagnostics, review-packet, and uninstall integration
* No arbitrary webhook URLs or direct external HTTP delivery
* No inbound command execution
* No automated inquiry, fit, proposal, contract, or engagement decisions

== Workflow Core boundary ==

The core can synchronize state, prepare a signed handoff, dispatch to a registered internal adapter, and record an acknowledgment.

It cannot:

* accept or reject an inquiry
* finalize fit
* publish a proposal
* record a contract
* activate an engagement
* create an external project
* invoice or collect payment
* execute an unverified inbound command

== Installation ==

1. Back up the WordPress database and protected storage.
2. Upgrade to v0.12.0.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Engagement Intake → Diagnostics.
5. Confirm database version 0.12.0.
6. Confirm Workflow Core schema 1.0.0.
7. Confirm the four Workflow Core tables and columns.
8. Confirm the Workflow Core sync and outbox schedules.
9. Open Engagement Intake → Workflow Core.
10. Run SYNC WORKFLOW CORE.
11. Review blocked and warning cases.
12. Register and stage-test any target plugin adapter.
13. Prepare one operational-minimum handoff.
14. Dispatch and verify acknowledgment.
15. Test export, privacy erasure, recovery, and uninstall behavior.

== Changelog ==

= 0.12.0 =
* Added canonical Workflow Core case projections.
* Added idempotent human command ledger.
* Added signed and versioned cross-plugin handoffs.
* Added operational-minimum and internal-private classifications.
* Added durable internal-adapter outbox delivery.
* Added bounded retries and stale-claim recovery.
* Added registered internal adapter discovery.
* Added read-only REST resources.
* Added audit-driven deferred synchronization.
* Added consistency blockers and human resolution records.
* Added integrity-preserving privacy erasure tombstones.
* Added review, privacy, reliability, Diagnostics, and uninstall integration.
* Preserved every human-control boundary.

= 0.11.0 =
* Added Reliability, Accessibility, and Security Hardening.

= 0.10.0 =
* Added Inquiry Analytics and Operational Intelligence.

= 0.9.2 =
* Added Proposal and Engagement Handoff.
