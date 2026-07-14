=== Sustainable Catalyst Contact and Engagement Platform ===
Contributors: content-catalyst
Tags: contact, engagement, workflow, sender portal, microsoft teams, proposals, analytics, privacy, accessibility
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed contact-to-engagement platform with adaptive intake, secure sender collaboration, human review, Teams scheduling, proposals, engagement handoff, analytics, reliability, privacy, and signed internal integrations.

== Description ==

Version 1.0.2 is the Production Readiness and Live Validation release. It replaces assumption-based readiness checks with runtime evidence, guided repairs, a controlled live validation suite, recent backup attestation, and a strict 100% production gate.

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
* Administrator-only live validation with temporary inquiry, portal, file, duplicate-control, mail, and cleanup checks
* Recent database and protected-storage backup attestation
* A production gate requiring 100%, zero failures, zero warnings, fresh successful validation, and fresh backup evidence

WordPress mail acceptance does not prove inbox delivery; administrators must confirm the live-validation message manually.

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
2. Upgrade the existing plugin or install v1.0.2.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Contact & Engagement → Platform Overview.
5. Complete guided repairs and configure published Contact, Sender Portal, and Privacy pages.
6. Run Live Validation with a monitored email recipient.
7. Confirm the validation message reached the recipient.
8. Complete and attest database and protected-storage backups.
9. Require 100%, zero failures, and zero warnings.
10. Record Pilot and complete controlled public tests before recording Production.

== Changelog ==

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
