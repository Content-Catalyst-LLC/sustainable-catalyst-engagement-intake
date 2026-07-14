=== Sustainable Catalyst Contact and Engagement Platform ===
Contributors: content-catalyst
Tags: contact, engagement, workflow, sender portal, microsoft teams, proposals, analytics, privacy, accessibility
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed contact-to-engagement platform with adaptive intake, secure sender collaboration, human review, Teams scheduling, proposals, engagement handoff, analytics, reliability, privacy, and signed internal integrations.

== Description ==

Version 1.0.3 is the Pilot Findings and Public Launch Hardening release. It adds routed public entry contracts, browser-tab draft recovery, runtime upload-attack rejection probes, external inbox evidence, controlled pilot evidence, operational blockers, and a stricter public-launch gate.

It connects:

* Adaptive general contact and engagement inquiry experiences
* Protected document intake and quarantine
* Administrative review and human-controlled fit assessment
* Secure sender portal, authentication recovery, messages, and uploads
* Human-approved Microsoft Teams scheduling and optional Microsoft Graph calendar creation
* Versioned proposals and external-contract recording
* Controlled proposal-to-engagement handoff and onboarding readiness
* Inquiry analytics and operational intelligence
* Reliability, accessibility, abuse protection, and incident controls
* Canonical Workflow Core projections and signed cross-plugin handoffs
* Platform-wide readiness, migration provenance, and launch governance

The plugin folder, database prefixes, text domain, public URLs, and existing shortcodes remain compatible with the v0.x series.

== Recommended public entry ==

Use the unified entry point on the primary Contact page:

[sc_contact_engagement_platform]

It routes visitors to the appropriate existing intake experience and secure sender portal. It does not create a second submission pipeline.

Existing shortcodes remain supported:

* [sc_contact_hub]
* [sc_contact_form]
* [sc_engagement_inquiry]
* [sc_sender_portal]

== Production readiness and live validation ==

Platform Overview now provides:

* Runtime-backed version, database, migration, storage, HTTPS, incident, and Workflow Core checks
* Published local Contact and Sender Portal page verification with required shortcodes
* Scheduled job and registered callback verification
* Initialized internal-adapter registry evidence
* Rendered accessibility evidence
* Guided repair actions for bounded operational issues
* Administrator-only live validation with temporary inquiry, portal, file, duplicate-control, mail, upload-rejection, routing, and cleanup checks
* Canonical routed entry URLs through the published Contact page
* Browser-tab draft recovery that excludes files, tokens, nonces, and consent state
* External inbox delivery evidence recorded separately from WordPress transport acceptance
* Controlled pilot evidence requiring at least five completed inquiries and a complete launch checklist
* Operational blocker evidence for failed communications, quarantine, portal lockouts, overdue work, and critical events
* Recent database and protected-storage backup attestation
* A production gate requiring 100%, zero failures, zero warnings, and fresh validation, inbox, pilot, and backup evidence

WordPress mail acceptance does not prove inbox delivery. Administrators must confirm receipt separately and record the external evidence.

== Human-control boundary ==

The platform does not automatically:

* accept or reject an inquiry
* decide fit
* rank senders
* publish a proposal
* create or attest a contract
* activate an engagement
* create an invoice or collect payment
* electronically sign an agreement
* provision an external project
* send arbitrary webhooks
* execute unverified inbound commands
* switch the platform to production

== Installation ==

1. Back up the WordPress database and protected storage.
2. Upgrade the existing plugin or install v1.0.3.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Contact & Engagement → Platform Overview.
5. Complete guided repairs and configure published Contact, Sender Portal, and Privacy pages.
6. Run Live Validation with a monitored email recipient.
7. Confirm the validation message reached the recipient and record external inbox evidence.
8. Complete at least five controlled inquiries and every pilot checklist item.
9. Resolve all operational blockers.
10. Complete and attest database and protected-storage backups.
11. Require 100%, zero failures, and zero warnings before the typed Production action.

== Changelog ==

= 1.0.3 =
* Added canonical routed Contact-page entry URLs for Advisory, Sustainable AI Assurance, collaboration, media, technical, partnership, workshop, and monthly-advisory inquiries.
* Added browser-tab draft recovery without persisting files, tokens, nonces, or consent state.
* Added runtime acceptance of a safe text file and rejection of a disguised executable upload.
* Added external inbox delivery evidence separately from WordPress mail-transport acceptance.
* Added controlled pilot evidence requiring at least five completed inquiries and all launch checklist items.
* Added an operational blocker dashboard and production checks for failed communications, quarantine, portal lockouts, overdue work, and critical events.
* Added a nondestructive v1.0.3 migration journal and advanced the platform evidence schema to 1.0.2 while preserving database version 1.0.0.

= 1.0.2 =
* Added guided production-readiness repairs and runtime evidence.
* Verified published page/shortcode contracts and cron callbacks.
* Added administrator-only live validation for inquiry, duplicate controls, portal tokens, protected files, mail acceptance, and cleanup.
* Added recent backup attestation.
* Made Production require 100%, zero failures, zero warnings, fresh successful validation, and fresh backup evidence.
* Preserved database version 1.0.0 and advanced the platform evidence schema to 1.0.1.

= 1.0.1 =
* Fixed a fatal error on Platform Overview and Diagnostics caused by cross-class access to a private watchdog hook constant.
* Added a typed watchdog hook accessor while preserving repository encapsulation.
* Made the platform readiness version check compatible with stable patch releases.
* Added a private/protected constant visibility regression test.
* Corrected the installable ZIP packaging version.

= 1.0.0 =
* Promoted Engagement Intake into the Unified Contact and Engagement Platform.
* Added Platform Overview and unified administration navigation.
* Added the [sc_contact_engagement_platform] public entry shortcode.
* Added immutable platform-readiness snapshots.
* Added an idempotent migration journal and schema provenance hash.
* Added setup, pilot, production, and maintenance launch states.
* Added a human-controlled production readiness gate.
* Added product-wide status and aggregate operational reporting.
* Added platform read-only REST status.
* Added platform Diagnostics, Reliability, privacy inventory, cron, and uninstall integration.
* Preserved every existing shortcode, table, record, URL route, schema, and human-control boundary.

= 0.12.0 =
* Added canonical Workflow Core projections, commands, signed handoffs, and durable internal-adapter delivery.

= 0.11.0 =
* Added reliability, accessibility, and security hardening.

= 0.10.0 =
* Added inquiry analytics and operational intelligence.

= 0.9.2 =
* Added proposal and engagement handoff.
