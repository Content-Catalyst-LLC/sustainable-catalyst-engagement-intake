=== Sustainable Catalyst Contact and Engagement Platform ===
Contributors: content-catalyst
Tags: contact, engagement, workflow, sender portal, microsoft teams, proposals, analytics, privacy, accessibility
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed contact-to-engagement platform with adaptive intake, secure sender collaboration, human review, Teams scheduling, proposals, engagement handoff, analytics, reliability, privacy, and signed internal integrations.

== Description ==

Version 1.0.0 is the first stable Unified Contact and Engagement Platform release.

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

== Stable 1.0 platform layer ==

The new Platform Overview provides:

* Product-wide operational status
* Launch readiness and production gate
* Database and schema integrity
* Idempotent v1.0 migration journal
* Immutable readiness snapshots with SHA-256 hashes
* Public entry and sender portal configuration checks
* Reliability, privacy, storage, HTTPS, cron, analytics, engagement, and Workflow Core health
* Registered internal adapter visibility
* Human-controlled setup, pilot, production, and maintenance states

Production status can be recorded only by an authorized typed action after all required readiness checks pass.

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
2. Upgrade the existing plugin or install v1.0.0.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open Contact & Engagement → Diagnostics.
5. Confirm plugin and database version 1.0.0.
6. Confirm Platform schema 1.0.0 and all inherited schema versions.
7. Confirm the platform snapshot and migration tables.
8. Open Contact & Engagement → Platform Overview.
9. Verify the v1.0 migration journal.
10. Configure the public entry, sender portal, privacy, and support URLs.
11. Run a readiness snapshot.
12. Test the unified public shortcode and every existing intake route in staging.
13. Test sender portal, review, scheduling, proposal, engagement, privacy, analytics, reliability, and Workflow Core paths.
14. Move from Setup to Pilot, then to Production only after the required checks pass.

== Changelog ==

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
