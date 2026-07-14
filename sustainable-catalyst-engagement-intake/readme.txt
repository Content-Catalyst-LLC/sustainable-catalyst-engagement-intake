=== Sustainable Catalyst Contact and Engagement Platform ===
Contributors: content-catalyst
Tags: contact, advisory, engagement, lifecycle, sender portal, microsoft teams, proposals, privacy
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed contact-to-engagement platform with adaptive intake, advisory lifecycle operations, secure sender collaboration, Teams coordination, proposals, privacy, reliability, and launch governance.

== Description ==

Version 1.1.0 is the Advisory Operations and Engagement Lifecycle release. It preserves the v1.0.3 public-launch gate and adds thirteen audited lifecycle stages, structured qualification, internal-only notes, follow-up tasks, Teams/proposal/engagement linkage, sender-safe status publishing, service routes, communication templates, privacy integration, and operational metrics.

The platform connects:

* Adaptive general contact and service-specific engagement inquiry experiences
* Protected document intake and quarantine
* Human review, fit assessment, and structured advisory qualification
* Governed lifecycle stages from inquiry through active engagement and completion
* Private internal notes, ownership, priority, next actions, and assigned tasks
* Secure Sender Portal status, messages, uploads, meetings, and proposal notices
* Human-approved Microsoft Teams scheduling and optional Microsoft Graph calendar creation
* Versioned proposals and controlled engagement handoff
* Aggregate lifecycle, response, qualification, proposal, acceptance, and workload metrics
* Reliability, accessibility, abuse protection, privacy, retention, and incident controls
* Canonical Workflow Core projections and signed internal handoffs
* Runtime readiness, live validation, pilot evidence, backups, and typed launch governance

The plugin slug, text domain, existing tables, public URLs, and existing shortcodes remain compatible.

== Recommended public entry ==

Use the unified entry point on the primary Contact page:

[sc_contact_engagement_platform]

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
* current v1.1.0 database and migration evidence
* recent successful live validation
* externally confirmed inbox evidence
* completed controlled-pilot evidence
* current database and protected-storage backup evidence
* no critical events, public-launch blockers, overdue lifecycle tasks, or overdue next actions
* typed human promotion to Production

Repository validation does not replace live WordPress-host testing.

== Installation ==

1. Back up the WordPress database and protected storage.
2. Upgrade the existing plugin or install v1.1.0.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Contact & Engagement → Platform Overview.
5. Complete the database and v1.1.0 lifecycle migration repairs if shown.
6. Inspect backfilled inquiries in Contact & Engagement → Advisory Lifecycle.
7. Run Live Validation with a monitored recipient.
8. Confirm inbox delivery and repeat controlled pilot checks for v1.1.0.
9. Resolve public-launch and lifecycle operational blockers.
10. Record current database and protected-storage backup evidence.
11. Require 100%, zero failures, and zero warnings before Production.

== Changelog ==

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
