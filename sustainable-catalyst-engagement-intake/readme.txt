=== Sustainable Catalyst Contact and Engagement Platform ===
Contributors: content-catalyst
Tags: contact, support, help desk, advisory, engagement, sender portal, client workspace, billing, analytics
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An integrated advisory, product-support, and institutional engagement platform with canonical dossiers, secure collaboration, governed approvals, billing handoffs, analytics, privacy, and production controls.

== Description ==

Version 2.0.1 retains the v2.0 integrated platform and adds protected database recovery. It creates interrupted proposal-approval and platform-handoff tables before metadata checks, prevents missing-table log storms, and fails closed if database write access remains unavailable.

Version 2.0.0 unifies the mature Contact and Engagement subsystems behind a canonical engagement dossier. Each inquiry can be related to its support case, lifecycle, meetings, proposals, Statements of Work, engagement, client workspace, protected files, communications, invoices, and privacy-safe cross-product handoffs without duplicating those underlying records.

The new Integrated Engagement Command Center provides one operational view of route, phase, health, relationships, activity, and typed handoffs. The v2 REST contract exposes authorized dossier and handoff resources while retaining existing v1 endpoints and legacy shortcodes.

The platform remains human-governed. It does not merge unrelated cases automatically, make advisory or support decisions, schedule meetings, accept proposals, activate engagements, collect payments, or send external communications without the existing authorized workflows.

== Recommended public entry ==

Use the unified entry point on the primary Contact page:

[sc_contact_engagement_platform]

Use the focused support entry on `/support/`:

[sc_support_request]

Use the Sender Portal only for authorized existing senders:

[sc_sender_portal]

The Command Center and client workspaces are administrative or invitation-only surfaces, not public registration systems.

== Integrated engagement command center ==

Contact & Engagement → Command Center provides:

* Canonical dossier reference, route, phase, health, owner, and sender-safe next step
* Relationships to support, meetings, proposals, SOWs, engagements, workspaces, invoices, documents, communications, and tasks
* Unified activity timeline across governed subsystems
* Privacy-safe, replay-safe typed handoff receipts
* Integrity metrics for missing or orphaned dossiers and stale or failed handoffs
* Bounded backfill and individual dossier refresh actions

== Production readiness ==

Production remains unavailable until the existing gate reaches 100% with zero failures and zero warnings. v2.0.0 adds checks for:

* Four unified-platform tables and required columns
* Completed v2.0.0 migration journal
* Canonical dossier coverage for non-erased inquiries
* No orphaned dossier relationships
* No stale or unresolved typed handoffs
* Successful v3 Live Validation of dossier, timeline, relationships, privacy rejection, handoff replay, and cleanup

== Installation ==

1. Back up the WordPress database and protected engagement-document storage.
2. Upgrade the existing plugin or install v2.0.0.
3. Clear WordPress, object, hosting, CDN, browser, and PHP opcode caches.
4. Open Contact & Engagement → Platform Overview.
5. Repair the database contract and verify the v2.0.0 integrated-platform migration if requested.
6. Open Contact & Engagement → Command Center and run the bounded dossier backfill.
7. Run Live Validation and confirm the temporary dossier, relationships, timeline, privacy boundary, idempotent handoff, and cleanup all pass.
8. Re-record version-bound inbox, backup, validation, and pilot evidence.
9. Require 100%, zero required failures, and zero warnings before Production.

== Changelog ==

= 2.0.1 =
* Added protected recovery for missing `proposal_approvals` and `platform_handoffs` tables after interrupted or disk-full upgrades.
* Creates recovery-critical tables with native `CREATE TABLE IF NOT EXISTS` before normal `dbDelta()` reconciliation.
* Added table-existence guards so absent tables return failed contract evidence without issuing repeated `SHOW COLUMNS` errors.
* Added a five-minute database-upgrade lock and a fail-closed runtime pause to prevent unbounded migration loops.
* Activation now stops cleanly with an administrator-facing recovery message if database writes remain unavailable.
* Database and platform schema versions remain 2.0.0; plugin version advances to 2.0.1.

= 2.0.0 =
* Added canonical engagement dossiers with route, phase, health, ownership, sender-safe summaries, and content hashes.
* Added typed relationships across inquiries, support cases, meetings, proposals, SOWs, engagements, workspaces, invoices, attachments, communications, and lifecycle tasks.
* Added a unified cross-module activity timeline and Integrated Engagement Command Center.
* Added privacy-safe, idempotent `sc-engagement-platform-handoff/2.0` receipts and authorized v2 REST resources.
* Added export, approved redaction, retention, diagnostics, readiness repair, and Live Validation coverage.
* Added four nondestructive tables and advanced plugin, database, platform evidence, and unified-platform schema versions to 2.0.0.

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
