=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: intake, reliability, accessibility, security, sender portal, analytics, privacy, quarantine
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.11.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-hardened private engagement intake with durable health monitoring, abuse protection, accessible workflows, analytics, engagement handoff, secure scheduling, privacy, and quarantine.

== Description ==

Version 0.11.0 adds Reliability, Accessibility, and Security Hardening to the complete Sustainable Catalyst engagement workflow.

Highlights:

* Deduplicated operational health ledger
* Database-backed keyed rate limits
* Public-write incident pause and recovery
* Hourly production watchdog
* Daily hardening-data pruning
* Request correlation IDs
* Redacted fatal metadata capture
* Security headers and optional CSP report-only mode
* Redacted reliability export
* Skip links, live regions, visible focus, reduced motion, forced colors, and keyboard-scrollable tables
* No automated intake, fit, proposal, contract, payment, engagement, or deletion decisions

== Installation ==

1. Back up the database and private storage.
2. Upgrade to v0.11.0.
3. Clear caches.
4. Open Engagement Intake → Diagnostics.
5. Confirm database version 0.11.0.
6. Confirm hardening schema 1.0.0.
7. Open Engagement Intake → Reliability.
8. Run RUN HARDENING CHECK.
9. Test incident pause and recovery in staging.
10. Test keyboard, reduced-motion, and forced-colors behavior.

== Changelog ==

= 0.11.0 =
* Added durable technical health events and rate limits.
* Added incident public-write pause and human recovery.
* Added scheduled watchdog and pruning.
* Added request correlation and secret-safe fatal metadata.
* Added security headers and redacted export.
* Added accessibility helpers and resilient focus behavior.
* Preserved all human-control and privacy boundaries.

= 0.10.0 =
* Added Inquiry Analytics and Operational Intelligence.

= 0.9.2 =
* Added Proposal and Engagement Handoff.
