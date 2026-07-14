=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: engagement, proposal, onboarding, microsoft teams, microsoft graph, sender portal, privacy, quarantine
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.9.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private engagement intake with controlled proposal-to-engagement handoff, immutable commercial snapshots, onboarding readiness, typed activation, Teams scheduling, optional Microsoft Graph reliability, secure sender portal, privacy governance, and protected uploads.

== Description ==

Version 0.9.2 completes the controlled operational path from a contracted proposal to an active engagement.

The release preserves a contracted proposal as an immutable, SHA-256-verified handoff snapshot. It then creates a separate engagement record in `handoff_pending`, seeds onboarding requirements, assigns ownership, evaluates readiness, and requires a second typed human action before activation.

Portal proposal acceptance remains intent to proceed. The externally executed agreement remains the binding commercial record.

== Handoff workflow ==

`contracted proposal → handoff_pending → ready_for_setup → active → paused/completed/canceled`

Alternative pre-activation path:

`handoff_pending or ready_for_setup → canceled`

Exactly one engagement can be linked to one contracted proposal.

== Immutable commercial snapshot ==

The atomic handoff captures:

* Inquiry reference and project context
* Proposal number and exact published version
* Proposal content hash
* Scope and deliverables
* Exclusions and assumptions
* Timeline
* Fee summary and payment terms
* Proposal boundaries
* Currency and total value
* Sender intent evidence
* Contract reference and contract-recording metadata
* Fixed no-automation boundaries

The snapshot receives its own SHA-256 content hash. It is not edited during normal operations.

Approved privacy erasure replaces the personal payload with a limited tombstone and writes a new valid tombstone hash while retaining the original proposal content hash as commercial provenance.

== Onboarding readiness ==

Default requirements include:

* Verify external contract reference
* Verify immutable proposal snapshot
* Confirm engagement owner
* Confirm kickoff plan
* Review access and data requirements
* Review delivery workspace requirements

Authorized staff can add further required or optional items.

An engagement cannot be marked ready until required checks pass. Activation reruns readiness to prevent stale approvals.

== Human controls ==

Create handoff:

`HANDOFF <PROPOSAL-NUMBER>`

Mark ready:

`READY <ENGAGEMENT-NUMBER>`

Activate:

`ACTIVATE <ENGAGEMENT-NUMBER>`

Lifecycle controls:

* `PAUSE <ENGAGEMENT-NUMBER>`
* `RESUME <ENGAGEMENT-NUMBER>`
* `COMPLETE <ENGAGEMENT-NUMBER>`
* `CANCEL <ENGAGEMENT-NUMBER>`

Every transition requires capability, nonce, current-state validation, optimistic row versioning, typed confirmation, and audit evidence.

== Sender portal ==

The Secure Sender Portal can show:

* Engagement number and title
* Lifecycle state
* Sender-safe summary
* Contract reference
* Engagement owner
* Proposed start and target end
* Kickoff state
* Activation date
* Sender-visible onboarding requirements

It does not expose internal notes, private evidence, participant assignments, readiness rationale, integration details, or private event context.

Existing portal invitations retain their stored permission set. Reissue access when `view_engagements` is required.

== Integration handoff ==

Private JSON handoff packages can prepare data for Workbench or Decision Studio.

The export records handoff status but does not automatically:

* Create a Workbench project
* Create a Decision Studio packet
* Create an external project
* Provision user accounts
* Generate an invoice
* Collect payment
* Generate or sign a contract
* Begin delivery

== Privacy and governance ==

Engagements, snapshots, requirements, and events participate in:

* Private data inventory
* WordPress privacy export
* Review packets
* Capability-gated REST records
* Private engagement handoff export
* Approved inquiry erasure
* Diagnostics
* Uninstall cleanup when full data deletion is explicitly enabled

== Installation ==

1. Back up the WordPress database and protected storage.
2. Upgrade from v0.9.1 to v0.9.2.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Engagement Intake → Diagnostics.
5. Confirm database version `0.9.2`.
6. Confirm portal schema `1.3.0`.
7. Confirm workflow schema `1.1.0`.
8. Confirm Graph schema `1.0.0`.
9. Confirm engagement schema `1.0.0`.
10. Confirm all four engagement tables and fields.
11. Open Engagement Intake → Engagements.
12. Test one contracted proposal handoff in staging.
13. Verify the snapshot hash.
14. Complete required onboarding items.
15. Mark the handoff ready.
16. Activate the engagement.
17. Test pause, resume, completion, and cancellation boundaries.
18. Reissue portal access where the engagement view is required.
19. Test private export and approved erasure.

== Changelog ==

= 0.9.2 =
* Added Proposal and Engagement Handoff.
* Added one-engagement-per-contracted-proposal protection.
* Added atomic handoff creation and rollback.
* Added immutable contracted-proposal snapshots.
* Added proposal and handoff SHA-256 hashes.
* Added ownership and internal participant records.
* Added onboarding requirements and evidence references.
* Added readiness evaluation and blocking checks.
* Added typed readiness and activation actions.
* Added active, paused, completed, and canceled lifecycle states.
* Added typed lifecycle transitions.
* Added sender-safe engagement portal view.
* Added private Workbench and Decision Studio handoff metadata without provisioning.
* Added private JSON handoff export.
* Added engagement event ledger.
* Added REST, review packet, privacy export, erasure, Diagnostics, and uninstall integration.
* Preserved no automatic activation, provisioning, invoice, payment, signature, contract generation, or project creation.

= 0.9.1 =
* Added Microsoft Graph Reliability Patch.

= 0.9.0 =
* Added Teams Scheduling and Proposal Workflow.

= 0.8.1 =
* Added Portal Authentication and Recovery Patch.

= 0.8.0 =
* Added Secure Sender Portal.
