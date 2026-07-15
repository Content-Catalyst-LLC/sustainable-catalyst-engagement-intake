=== Sustainable Catalyst Contact and Engagement Platform ===
Contributors: content-catalyst
Tags: contact, support, help desk, product support, advisory, engagement, sender portal, known issues, privacy
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed contact-to-engagement platform with adaptive intake, advisory lifecycle operations, secure sender collaboration, Teams coordination, proposals, privacy, reliability, and launch governance.

== Description ==

Version 1.3.0 is Microsoft Teams and Calendar Coordination. It adds governed meeting records, explicit meeting types, agendas and preparation requests, organizer and participant context, time-zone-safe scheduling, rescheduling history, reviewable idempotent reminders, cancellation safety, post-meeting follow-up, and sender-safe calendar views while preserving the existing advisory, support, privacy, and production-readiness boundaries.

The platform connects:

* Adaptive general contact and service-specific engagement inquiry experiences
* Focused product-support intake and private support-case operations
* Typed integration with public Knowledge Base, known issues, Feature Suggestions, and releases
* Privacy-safe, nonpersonal documentation-gap and product-friction signals
* Protected document intake and quarantine
* Human review, fit assessment, and structured advisory qualification
* Governed lifecycle stages from inquiry through active engagement and completion
* Private internal notes, ownership, priority, next actions, and assigned tasks
* Secure Sender Portal status, messages, uploads, meetings, and proposal notices
* Human-approved Microsoft Teams scheduling, calendar coordination, reminders, rescheduling, cancellation, and optional Microsoft Graph event creation
* Versioned proposals and controlled engagement handoff
* Aggregate lifecycle, response, qualification, proposal, acceptance, and workload metrics
* Reliability, accessibility, abuse protection, privacy, retention, and incident controls
* Canonical Workflow Core projections and signed internal handoffs
* Runtime readiness, live validation, pilot evidence, backups, and typed launch governance

The plugin slug, text domain, existing tables, public URLs, and existing shortcodes remain compatible.

== Recommended public entry ==

Use the unified entry point on the primary Contact page:

[sc_contact_engagement_platform]

Use the focused support entry on `/support/`:

[sc_support_request]

Service-specific links can use the same Contact page:

/contact/?engagement=advisory
/contact/?engagement=ai-assurance
/contact/?engagement=evidence-systems
/contact/?engagement=knowledge-architecture
/contact/?engagement=technical-storytelling
/contact/?engagement=responsible-ai
/contact/?engagement=collaboration
/contact/?engagement=media
/contact/?engagement=technical
/contact/?engagement=partnership
/contact/?engagement=workshop
/contact/?engagement=monthly-advisory

Existing shortcodes remain supported:

* [sc_contact_hub]
* [sc_contact_form]
* [sc_engagement_inquiry]
* [sc_sender_portal]

== Advisory lifecycle ==

Contact & Engagement → Advisory Lifecycle provides:

* New Inquiry, Under Review, Needs Information, Qualified, Meeting Requested, Meeting Scheduled, Proposal in Preparation, Proposal Sent, Accepted, Active Engagement, Completed, Declined, and Archived stages
* Typed human transition confirmation and allowed-transition rules
* Owner, priority, next action, due date, and qualification context
* Internal notes and sensitive-note marking
* Assigned tasks with idempotent reminders
* Linked Teams meeting offers, proposals, and engagements
* Deliberately published Sender Portal stage, summary, and next step
* Lifecycle events, audit history, privacy export, approved erasure, and retention support

The platform does not automatically accept, reject, qualify, schedule, publish, contract, or activate an engagement.

== Production readiness ==

Production requires:

* 100% readiness
* zero required failures and zero warnings
* current v1.3.0 calendar, v1.2.1 reliability-patch, v1.2.0 support, v1.1.1 persistence-patch, v1.1.0 lifecycle, and database evidence
* recent successful live validation
* externally confirmed inbox evidence
* completed controlled-pilot evidence
* current database and protected-storage backup evidence
* no critical events, public-launch blockers, overdue lifecycle work, or unresolved high-priority support cases
* typed human promotion to Production

Repository validation does not replace live WordPress-host testing.

== Installation ==

1. Back up the WordPress database and protected storage.
2. Upgrade the existing plugin or install v1.3.0.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Contact & Engagement → Platform Overview.
5. Complete database, lifecycle, support, reliability-patch, and v1.3.0 calendar migration repairs if shown.
6. Inspect Advisory Lifecycle, Support Cases, and Calendar Coordination.
7. Run Live Validation with a monitored recipient.
8. Confirm inbox delivery and repeat controlled advisory, support, and calendar pilot checks for v1.3.0.
9. Resolve public-launch and lifecycle operational blockers.
10. Record current database and protected-storage backup evidence.
11. Require 100%, zero failures, and zero warnings before Production.

== Changelog ==

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
