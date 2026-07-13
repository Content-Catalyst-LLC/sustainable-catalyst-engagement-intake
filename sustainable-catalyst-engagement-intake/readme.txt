=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, sender portal, secure messaging, privacy, retention, fit assessment, secure upload, quarantine, microsoft teams
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private consulting and contact intake with a secure passwordless sender portal, human-controlled fit assessment, privacy governance, reviewed communications, protected document quarantine, and Microsoft Teams readiness.

== Description ==

Version 0.8.0 adds a Secure Sender Portal to the existing dual intake, protected uploads, quarantine operations, Administrative Review Workspace, Communication History, Privacy and Retention Center, and Human-Controlled Fit Assessment.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`
* Private sender portal page: `[sc_sender_portal title="Secure Sender Portal"]`

The sender portal provides:

* One-time passwordless invitations
* Inquiry-email activation challenge
* Expiring invitations
* Absolute and idle session expiration
* Maximum active-session limits
* Revocable sessions and access
* Activation lockout after failed attempts
* HttpOnly SameSite Strict cookies
* Session-derived CSRF protection
* Rate limits
* No-store, noindex, no-referrer, frame-denial, and same-origin headers
* Secure portal-only messages
* Explicit publication of existing outbound communications
* Private follow-up documents through the protected quarantine pipeline
* Contact preference updates
* Microsoft Teams scheduling preference updates
* Privacy requests
* Inquiry withdrawal requests
* Sender self-revocation
* Hashed IP and browser fingerprints
* Private portal audit exports
* Privacy export and approved-erasure integration

The portal does not create a WordPress account, public password, inquiry-reference lookup, or public file download.

Raw invitation and session credentials are never stored. Only keyed hashes are persisted.

The portal never sends an invitation automatically. An authorized administrator receives the one-time link once and must deliver it through an approved channel.

Secure portal messages remain inside the portal and Communication History. They are not automatically sent through email.

== Sender-safe boundary ==

The portal can show:

* Inquiry reference
* Submission date
* Sender-safe status
* Original project summary
* Explicitly portal-visible messages
* Private document names, sizes, dates, and broad storage state
* Approved Microsoft Teams meeting information
* Contact, scheduling, privacy, withdrawal, and access controls

It never shows:

* Internal notes
* Human fit assessments
* Review judgments or assignments
* Risk ratings
* Legal-hold details
* Retention deliberations
* Audit narratives
* Internal escalation
* Private operational reasoning
* Protected file paths
* Internal-only communications

== Installation ==

1. Back up the WordPress database and protected storage.
2. Install and activate v0.8.0.
3. Create a private WordPress page containing `[sc_sender_portal title="Secure Sender Portal"]`.
4. Exclude the page from navigation, search, indexing, caching, CDN caching, and optimization.
5. Open Engagement Intake → Sender Portal.
6. Save the exact portal page URL.
7. Open Engagement Intake → Diagnostics.
8. Confirm database version 0.8.0 and portal schema 1.0.0.
9. Confirm the portal access, session, and event tables.
10. Confirm the hourly portal cleanup event.
11. Test invitation activation in a private browser.
12. Test invalid-token lockout.
13. Test idle and absolute session expiration.
14. Test session and access revocation.
15. Test secure messages without email delivery.
16. Test protected document upload and quarantine.
17. Test privacy restrictions and approved erasure.
18. Test the portal audit export.

== Upgrade from 0.7.0 ==

The upgrade preserves all prior inquiry, attachment, review, fit, communication, privacy, retention, audit, scanner, storage, and Teams records.

It adds:

* Three portal tables
* Eleven inquiry portal fields
* Four communication publication fields
* Portal roles and capabilities
* Sender portal shortcode and views
* Administrative portal workspace
* Hourly session and invitation expiration
* Portal data in authenticated REST, review packets, privacy export, inventory, diagnostics, and erasure

Activation does not:

* Create portal access for any sender
* Send an invitation
* Create a WordPress account
* Publish an internal communication
* Expose a private file
* Change inquiry status
* Schedule a meeting
* Accept an engagement
* Delete portal audit events

== Security notes ==

* Serve the portal only over HTTPS.
* Do not place the portal page in public navigation.
* Exclude it from page caches and CDN caches.
* Do not share one-time invitations through public channels.
* Reissue invitations after suspected disclosure.
* Review portal-visible communication before publication.
* Treat sender-uploaded files as untrusted until quarantine and scanner review are complete.
* Portal audit event retention is configured for governance review; v0.8.0 does not silently purge those events.

== Changelog ==

= 0.8.0 =
* Added the Secure Sender Portal.
* Added passwordless one-time invitation activation.
* Added inquiry-email challenge, terms acceptance, lockout, sessions, CSRF, rate limits, revocation, and expiration.
* Added secure portal messages without automatic email.
* Added explicit outbound communication publication.
* Added protected follow-up uploads through quarantine.
* Added sender contact and Teams preference updates.
* Added privacy and withdrawal requests.
* Added access/session audit and private export.
* Added privacy export, erasure, REST, review packet, inquiry, communication, inventory, and Diagnostics integration.

= 0.7.0 =
* Added Human-Controlled Fit Assessment.

= 0.6.0 =
* Added the Privacy and Retention Center.

= 0.5.0 =
* Added Notifications and Communication History.

= 0.4.0 =
* Added the Administrative Review Workspace.

= 0.3.2 =
* Added Quarantine Operations and scanner readiness.

= 0.3.1 =
* Added production storage and upload reliability.

= 0.3.0 =
* Added secure document intake and quarantine.

= 0.2.2 =
* Added dual intake experiences and conversion routing.
