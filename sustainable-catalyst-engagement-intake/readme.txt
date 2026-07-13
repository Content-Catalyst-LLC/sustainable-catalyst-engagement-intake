=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, communications, notifications, administrative review, secure upload, quarantine, microsoft teams, privacy
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private consulting and contact intake with reviewed communications, opt-in notifications, human administrative review, secure document quarantine, Microsoft Teams readiness, privacy tools, and audit history.

== Description ==

Version 0.5.0 adds Notifications and Communication History to the existing dual intake, protected document quarantine, scanner readiness, and Administrative Review Workspace.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`

Communication capabilities:

* Cross-inquiry communication history
* Inquiry-specific private timeline
* Version-locked email drafts
* Separate reviewed send action
* Plain-text WordPress email transport
* Honest accepted, failed, suppressed, received, recorded, draft, and canceled states
* Immutable delivery and change events
* Retry for failed messages
* Manual inbound email logging
* Manual Microsoft Teams message and meeting logging
* Phone, video, in-person, and other interaction logging
* Follow-up dates and due queues
* Communication thread state
* Unread inbound counts
* Sender email suppression with required reason
* Versioned plain-text templates
* Template variable allowlist
* Private CSV communication export
* Communication history in private review packets
* WordPress privacy export and erasure
* Notification transport and cron diagnostics

Automated notification policies are disabled by default:

* Sender acknowledgment
* Internal new-inquiry alert
* Assigned-reviewer due reminder
* Internal follow-up reminder
* Internal escalation alert

Enabling automation requires a valid sender name, sender email, and reply-to email.

The plugin uses `wp_mail()` as the transport boundary. A successful result means WordPress accepted the email for its configured transport. It does not prove delivery, inbox placement, opening, or reading.

No email attachments are supported. Private documents remain in protected storage and are never copied into notification emails.

Microsoft Teams remains the only live meeting platform represented in the intake workflow. v0.5.0 records Teams communications and approved meeting information, but does not create meetings through Microsoft Graph.

== Installation ==

1. Back up the WordPress database and protected storage directory.
2. Upload and activate v0.5.0.
3. Open Engagement Intake → Diagnostics.
4. Confirm database version 0.5.0.
5. Confirm communication, communication-event, and template tables.
6. Open Engagement Intake → Settings.
7. Configure an authorized sender and reply-to address.
8. Leave automated policies disabled until the mail transport test succeeds.
9. Open Engagement Intake → Communications → Notification Policy.
10. Send the plain-text transport test.
11. Confirm receipt separately; the plugin cannot prove delivery.
12. Test draft save, edit, reviewed send, failure, retry, cancellation, inbound logging, suppression, and follow-up.
13. Enable only the notification policies needed.

== Upgrade from 0.4.0 ==

The upgrade preserves inquiry, review, quarantine, storage, scanner, Teams, conversion, retention, privacy, and audit data.

It adds:

* Current communication state fields to inquiries
* Communications table
* Immutable communication event table
* Versioned communication templates table
* Default templates
* Hourly notification reminder cron
* Communication and notification capabilities
* Communication privacy export and erasure
* Communication diagnostics

All automated policies remain disabled after migration. No historical message is fabricated and no email is sent merely because the plugin was upgraded.

== Frequently Asked Questions ==

= Does “accepted” mean the email was delivered? =

No. It means the configured WordPress mail transport accepted the message. Delivery and reading are not independently confirmed.

= Are automatic messages enabled after upgrade? =

No. Every automated policy defaults to disabled.

= Can saving a draft send it? =

No. Saving and sending are separate actions. Manual sending requires an explicit confirmation checkbox.

= Can the plugin attach uploaded documents to email? =

No. Email attachments are deliberately unsupported.

= Can it receive email automatically? =

No. Inbound email is recorded manually in v0.5.0. A future secure sender portal may provide a controlled reply channel.

= Can it send Teams messages or create Teams meetings? =

No. Teams messages, calls, and meetings can be recorded in history. Microsoft Graph integration is not enabled.

= Can a failed message be retried? =

Yes. The failed record retains attempts and error details and can be retried by an authorized user.

= How are duplicate reminders prevented? =

Automated notifications use unique deduplication keys. Due and follow-up reminders are limited to one matching record per inquiry, day, and recipient.

= What happens when do-not-email is enabled? =

Sender-facing email is suppressed until an authorized user deliberately clears the control. A reason is required.

= Are templates editable in place? =

No. Saving a template creates a new version and archives the previous active version.

== Changelog ==

= 0.5.0 =
* Added reviewed plain-text communications, notification controls, communication history, immutable transport events, inbound and Teams interaction logging, follow-up and suppression controls, versioned templates, private exports, privacy integration, and diagnostics.

= 0.4.0 =
* Added the human-controlled Administrative Review Workspace.

= 0.3.2 =
* Added Quarantine Operations and scanner readiness.

= 0.3.1 =
* Added production storage and upload reliability.

= 0.3.0 =
* Added secure document intake and quarantine.

= 0.2.2 =
* Added dual intake experiences and conversion routing.
