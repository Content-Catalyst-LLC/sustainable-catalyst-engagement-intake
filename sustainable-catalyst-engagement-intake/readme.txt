=== Sustainable Catalyst Contact and Engagement Platform ===
Contributors: content-catalyst
Tags: contact, support, help desk, product support, advisory, engagement, sender portal, known issues, privacy
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 1.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed contact-to-engagement platform with adaptive intake, advisory lifecycle operations, secure sender collaboration, Teams coordination, proposals, privacy-safe analytics, service intelligence, reliability, and launch governance.

== Description ==

Version 1.7.0 is Billing, Invoicing, and Payment Handoffs. It adds engagement-linked billing profiles, versioned invoices, line items, governed issue and void transitions, external HTTPS payment-provider handoffs, replay-safe provider status events, and a sender-safe invoice view.

The platform does not collect or store card numbers, CVV/CVC values, bank-account numbers, routing numbers, payment credentials, provider secrets, or payment tokens. Payment collection remains with approved external providers. Invoice and payment records are operational engagement records and do not replace accounting, tax, legal, or banking systems.

The platform retains its contact, advisory, support, scheduling, proposal, secure workspace, analytics, privacy, and reliability layers.

== Recommended public entry ==

Use the unified entry point on the primary Contact page:

[sc_contact_engagement_platform]

Use the focused support entry on `/support/`:

[sc_support_request]

Use the Sender Portal only for authorized existing senders:

[sc_sender_portal]

Client workspaces are created by authorized staff from existing engagements. They are not open public registrations.

== Secure client workspace ==

Contact & Engagement → Client Workspace provides:

* Draft, Active, Paused, Completed, and Archived workspace stages
* Explicit sender and staff membership
* Sender-safe workspace summary and next step
* Milestones with due dates and publication control
* Deliverables with publication, acceptance, and change-request state
* Protected-document relationships limited to the same inquiry
* Staff and sender collaboration updates
* Workspace audit history and privacy controls

Internal assignments, email hashes, event context, private notes, and unpublished records remain excluded from the Sender Portal.

== Production readiness ==

Production requires:

* 100% readiness
* zero required failures and zero warnings
* current database, service-intelligence, workspace, proposal, calendar, support, lifecycle, and patch migration evidence
* recent successful live validation
* externally confirmed inbox evidence
* completed controlled-pilot evidence
* current database and protected-storage backup evidence
* no critical events, overdue service-intelligence reviews, workspace blockers, overdue lifecycle work, or unresolved high-priority support cases
* typed human promotion to Production

Repository validation does not replace live WordPress-host testing.

== Installation ==

1. Back up the WordPress database and protected storage.
2. Upgrade the existing plugin or install v1.7.0.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Contact & Engagement → Platform Overview.
5. Complete database and v1.7.0 billing migration repairs if shown.
6. Inspect Analytics & Intelligence, Client Workspace, Proposal Governance, Calendar Coordination, Support Cases, and Advisory Lifecycle.
7. Run Live Validation with a monitored recipient.
8. Save one controlled aggregate snapshot and review one test service-intelligence finding without creating an automatic service action.
9. Resolve workspace and public-launch operational blockers.
10. Record current database and protected-storage backup evidence.
11. Require 100%, zero failures, and zero warnings before Production.

== Changelog ==

= 1.7.0 =
* Added engagement-linked billing profiles, versioned invoices, line items, human-reviewed invoice transitions, and immutable invoice snapshots.
* Added privacy-safe HTTPS handoffs to external payment providers with idempotent creation and replay-safe status events.
* Added Sender Portal invoices and payment links through an explicit public allowlist.
* Added billing administration, reviewable communication templates, privacy export, readiness, diagnostics, Live Validation, and operational metrics.
* Added six nondestructive billing tables; advanced plugin, database, and platform versions to 1.7.0, Portal schema to 1.8.0, and introduced Billing schema 1.0.0.

= 1.6.0 =
* Expanded privacy-safe analytics across inquiries, support cases, meetings, proposals, engagements, client workspaces, milestones, deliverables, and product-intelligence signals.
* Added minimum-cohort-suppressed product, component, service, weekly, funnel, timing, collaboration, and operational metrics.
* Added SHA-256-hashed aggregate snapshots and version-bound freshness evidence.
* Added human-reviewed service-intelligence findings with typed confirmation, optimistic locking, event history, retention, and compensating rollback.
* Added direct personal-data key and value rejection, no sender ranking, no content-body analytics, and no automated decisions.
* Added two nondestructive service-intelligence tables; advanced plugin, database, and platform versions to 1.6.0 and Analytics schema to 1.1.0.


= 1.5.0 =
* Added engagement-linked secure client workspaces with explicit sender and staff membership.
* Added governed workspace stages, sender-safe milestones, deliverables, document metadata, updates, and next steps.
* Added controlled deliverable publication, acceptance, and change-request records.
* Added workspace-specific Sender Portal messages, privacy export/redaction, audit history, reviewable templates, readiness, repairs, and Live Validation.
* Added seven workspace tables; advanced database and platform versions to 1.5.0, Portal schema to 1.7.0, and introduced Workspace schema 1.0.0.

= 1.4.1 =
* Added synchronous proposal-response receipt commits with rollback.
* Added replay-safe proposal and SOW approvals.
* Added current-version SOW checks and publication reconciliation.
* Added bounded version insert retries and conversion repair.
* Added approval-integrity and conversion-consistency readiness evidence.
* Database remains 1.4.0; platform schema advances to 1.4.1 and proposal schema to 1.0.1.

= 1.4.0 =
* Added governed proposal status, current-version enforcement, immutable sender actions, and deliberate proposal decisions.
* Added versioned Statements of Work with internal approval and sender review.
* Added typed change requests with scope, schedule, and fee impacts.
* Added idempotent engagement conversion requiring a contracted proposal and sender-approved SOW.
* Added Proposal Governance administration, Sender Portal SOW views, reviewable templates, privacy, readiness, and Live Validation coverage.
* Advanced database and platform evidence versions to 1.4.0 and introduced proposal schema 1.0.0.

= 1.3.1 =
* Rejects nonexistent and ambiguous daylight-saving local times instead of silently normalizing meetings.
* Repairs cancellation and post-meeting reminder eligibility, stale reminder state, and terminal notice evidence.
* Requires a reviewed accepted outbound communication before a reminder can be marked sent.
* Adds compensating reschedule and completion behavior so partial calendar updates do not become canonical.
* Adds the nondestructive v1.3.1 scheduling, reminder, and time-zone reliability journal.

= 1.3.0 =
* Added governed Microsoft Teams and calendar coordination over the existing meeting-offer and Microsoft Graph foundation.
* Added explicit meeting types, organizers, participant lists, agendas, preparation requests, sender-safe summaries, calendar references, and related-document identifiers.
* Added time-zone-safe rescheduling history, duplicate-event safeguards, canceled-link revocation, post-meeting notes, decisions, open questions, and follow-up tasks.
* Added idempotent invitation, 24-hour, one-hour, reschedule, cancellation, and post-meeting reminder records; reminders remain reviewable and are not sent automatically.
* Added Calendar Coordination administration, Sender Portal allowlist projection, privacy export/redaction, readiness, cron, migration, uninstall, and Live Validation coverage.
* Advanced the database version to 1.3.0, Portal schema to 1.5.0, Workflow schema to 1.2.0, Platform schema to 1.3.0, and introduced Calendar schema 1.0.0.

= 1.2.1 =
* Made public support intake and linked support-case creation recoverable.
* Added retry-safe case, event, relationship, and signal persistence with protected failure evidence.
* Added strict product/source validation, stable handoff IDs, receipt records, and replay idempotency for `sc-product-support-handoff/1.0`.
* Added Knowledge Base, known-issue, Feature Suggestion, product-release, and handoff-receipt relationship checks.
* Strengthened Sender Portal isolation, support operations metrics, readiness, and Live Validation.
* Added a nondestructive v1.2.1 patch journal while keeping database schema 1.2.0.

= 1.2.0 =
* Added private product-support cases tied to canonical inquiries.
* Added governed support stages, product/version/component diagnostics, environment and reproduction evidence, severity, assignment, and sender-safe updates.
* Added support case events, typed relationships, and privacy-safe aggregated product-intelligence signals.
* Added the `sc-product-support-handoff/1.0` contract and capability-protected REST endpoints.
* Added `[sc_support_request]`, `/contact/?engagement=support`, a Support Cases workspace, Sender Portal support status, communication templates, privacy integration, readiness, watchdog, cron, and Live Validation.
* Preserved all v1.1.1 persistence and human-control boundaries.

= 1.1.1 =
* Fixed strict-mode inquiry creation by initializing `qualification_score` to the non-null database default of zero.
* Added protected reliability events for failed inquiry inserts while keeping raw database errors out of public responses.
* Added inquiry-column verification to Platform Readiness, Live Validation, and the reliability watchdog.
* Prevented the stored database version from advancing until required tables and write-path columns verify successfully.
* Added a nondestructive v1.1.1 persistence reliability journal and repair path.
* Added executable inquiry-persistence regression coverage with a strict fake database adapter.

= 1.1.0 =
* Added thirteen governed advisory lifecycle stages with allowed transitions.
* Added typed, human-confirmed transitions, reasons, ownership requirements, optimistic locking, lifecycle events, and audit records.
* Added nondestructive inquiry lifecycle fields and backfill from legacy statuses.
* Added lifecycle event, internal-note, and follow-up-task tables.
* Added structured qualification, ownership, priority, next actions, and sender-safe summaries.
* Added an Advisory Lifecycle workspace linked to Teams offers, proposals, and engagement records.
* Added sender-safe portal stage, summary, and next-step publishing while preserving internal-note and qualification isolation.
* Added first-class routes for Advisory, Sustainable AI Assurance, Knowledge Architecture, Technical Storytelling, Responsible AI Workflows, collaboration, workshops, and monthly advisory support.
* Added reviewable lifecycle communication templates and opt-in internal task reminders.
* Added lifecycle metrics, privacy export, approved erasure, retention, diagnostics, readiness, cron, and uninstall integration.
* Advanced database version to 1.1.0 and platform evidence schema to 1.1.0.

= 1.0.3 =
* Added canonical routed Contact-page entry URLs and browser-tab draft recovery.
* Added runtime upload-attack rejection, external inbox evidence, controlled pilot evidence, and operational blockers.
* Added a nondestructive v1.0.3 migration journal and advanced the platform evidence schema to 1.0.2.

= 1.0.2 =
* Added guided production-readiness repairs, runtime evidence, live validation, and backup attestation.
* Made Production require 100%, zero failures, zero warnings, fresh validation, and fresh backup evidence.

= 1.0.1 =
* Fixed the Platform Overview and Diagnostics fatal error caused by private watchdog-hook access.
* Added a private/protected constant visibility regression test and corrected release packaging.

= 1.0.0 =
* Promoted Engagement Intake into the Unified Contact and Engagement Platform.
* Added Platform Overview, unified public entry, migration provenance, launch states, and human-controlled Production gating.
* Preserved existing shortcodes, records, URLs, schemas, and human-control boundaries.

= 0.12.0 =
* Added canonical Workflow Core projections, commands, signed handoffs, and durable internal-adapter delivery.

= 0.11.0 =
* Added reliability, accessibility, and security hardening.

= 0.10.0 =
* Added inquiry analytics and operational intelligence.

= 0.9.2 =
* Added proposal and engagement handoff.
