=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, sender portal, authentication recovery, secure messaging, privacy, retention, secure upload, quarantine, microsoft teams
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private consulting and contact intake with patched passwordless portal authentication, human-reviewed recovery, secure messages, protected document quarantine, privacy governance, fit assessment, and Microsoft Teams readiness.

== Description ==

Version 0.8.1 is a focused Portal Authentication and Recovery Patch for the Secure Sender Portal introduced in v0.8.0.

It preserves the existing public intake, protected documents, quarantine operations, Administrative Review Workspace, Communication History, Privacy and Retention Center, and Human-Controlled Fit Assessment.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`
* Private sender portal page: `[sc_sender_portal title="Secure Sender Portal"]`

== Authentication repairs ==

v0.8.1 adds:

* Atomic invitation activation
* Rollback when access, inquiry, or session persistence fails
* Invitation preservation after safe rollback
* Correctable activation failures that return to the same invitation
* Safe handling of expired activation-form nonces
* Verified invitation-state inspection
* HTTPS enforcement for production authentication and recovery
* `__Host-sc_ei_sender_session` production cookie
* Migration support for active v0.8.0 legacy cookies
* Explicit session-cookie establishment recovery
* Optimistic access and inquiry version checks

The invitation is consumed only after:

1. the access record updates successfully
2. the inquiry portal state updates successfully
3. the session record is created successfully

A failure in any stage rolls the transaction back.

== Lockout correction ==

An incorrect invitation token never increments the sender's email-challenge lockout.

Lockout applies only when:

1. the invitation public ID exists
2. the invitation token is correct
3. the email challenge is incorrect

This prevents denial-of-service attempts using only a leaked invitation identifier.

Authorized managers can reset a verified email-challenge lockout after human review using:

`UNLOCK <ACCESS-ID>`

== Sender recovery ==

The public portal now contains a recovery form for expired, consumed, lost, locked, revoked, or browser-bound access.

The public response is always generic. It does not reveal whether:

* an inquiry reference exists
* an email address exists
* portal access exists
* a recovery request matched
* a request was deduplicated or throttled

Matched requests enter:

`Engagement Intake → Sender Portal → Authentication Recovery Queue`

Recovery requires:

* a dedicated management capability
* an unexpired pending request
* human review
* written rationale
* typed confirmation

Approval requires:

`RECOVER <RECOVERY-ID>`

Decline requires:

`DECLINE <RECOVERY-ID>`

Approval issues a new one-time invitation, revokes existing sessions through the normal invitation-reissue path, and displays the raw link once to the authorized administrator.

It does not send the invitation automatically.

== Recovery abuse controls ==

* Keyed-IP hourly rate limit
* Matched and unmatched attempts share the same limit
* Generic response after throttling
* Honeypot field
* Minimum reason length
* Request deduplication
* Expiring review queue
* Hashed reference, email, IP, and browser values
* No unmatched identity record is persisted
* No public access link is generated

== Cookie migration ==

Production HTTPS uses:

`__Host-sc_ei_sender_session`

The cookie has:

* Secure
* HttpOnly
* SameSite Strict
* Path `/`
* No Domain attribute

During the patch transition, an active v0.8.0 `sc_ei_sender_session` cookie can be read and migrated to the new `__Host-` cookie.

The legacy cookie is cleared after successful migration.

== Privacy and audit ==

Portal recovery records are included in:

* private data inventory
* WordPress privacy export
* portal audit export
* Diagnostics
* approved inquiry erasure

Approved erasure clears:

* reference and email hashes
* recovery reason
* hashed IP and browser values
* human decision notes

Limited categorical lifecycle and audit tombstones can remain.

== Installation ==

1. Back up the database and protected storage.
2. Upgrade directly from v0.8.0 to v0.8.1.
3. Keep the existing sender portal page and shortcode.
4. Confirm the portal is served over HTTPS.
5. Clear page, object, CDN, and browser caches.
6. Open Engagement Intake → Diagnostics.
7. Confirm database version `0.8.1`.
8. Confirm portal schema version `1.1.0`.
9. Confirm the portal recovery table and fields.
10. Confirm the hourly cleanup event.
11. Open Engagement Intake → Sender Portal.
12. Review authentication recovery policy settings.
13. Test a v0.8.0 active session cookie in staging.
14. Test a fresh invitation.
15. Test an incorrect token and confirm lockout does not increment.
16. Test a correct token with an incorrect email and confirm lockout increments.
17. Test a failed activation transaction and confirm the invitation remains usable.
18. Test generic recovery responses for matched and unmatched details.
19. Test human approval, decline, typed unlock, and one-time link display.
20. Test privacy export and approved erasure in staging.

== Changelog ==

= 0.8.1 =
* Added atomic invitation activation and rollback.
* Preserved invitations after session or inquiry persistence failure.
* Preserved invitation context after correctable activation errors.
* Added safe expired-form recovery.
* Prevented incorrect tokens from incrementing email lockout.
* Added verified invitation states.
* Added HTTPS enforcement.
* Added `__Host-` production cookie.
* Added v0.8.0 legacy-cookie migration.
* Added generic sender recovery requests.
* Added shared throttling for matched and unmatched recovery attempts.
* Added deduplication and expiring recovery review.
* Added human recovery approval and decline.
* Added typed invitation lockout reset.
* Added recovery privacy export, erasure, inventory, audit, and Diagnostics integration.

= 0.8.0 =
* Added the Secure Sender Portal.

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
