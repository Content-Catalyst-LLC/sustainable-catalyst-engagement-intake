=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, inquiry, consulting, microsoft teams, scheduling, workflow, privacy, audit, forms
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adaptive private contact and engagement intake with Microsoft Teams communication preferences and scheduling readiness.

== Description ==

Version 0.2.1 extends the Adaptive Contact Hub with Microsoft Teams as the only supported live meeting platform.

Public shortcodes:

* `[sc_contact_hub]`
* `[sc_contact_form mode="general"]`
* `[sc_engagement_inquiry mode="consulting"]`

New in v0.2.1:

* Preferred response method: email, Microsoft Teams, phone, or no preference
* Conditional Teams email and phone fields
* Microsoft Teams meeting request
* Browser time-zone suggestion with manual IANA time-zone entry
* City and country
* Preferred weekdays and time windows
* Preferred meeting duration
* Participant count and participant emails
* Private accessibility and accommodation field
* Calendar invitation consent
* Scheduling notes
* Teams scheduling statuses
* Private Teams meeting URL and calendar event metadata
* UTC-normalized scheduled start and end times
* Admin filtering and scheduling review
* Audit events for meeting requests and scheduling changes
* Teams organizer and default-duration settings
* No Microsoft Graph connection in this release

Submitting availability does not create a meeting. An administrator reviews the inquiry and updates the Teams scheduling record manually.

== Installation ==

1. Upload and activate the plugin.
2. Open Engagement Intake → Diagnostics.
3. Confirm that the v0.2.1 Teams database columns are present.
4. Open Engagement Intake → Settings and optionally enter the Teams organizer email.
5. Add `[sc_contact_hub]` to the Contact page.

== Frequently Asked Questions ==

= Does this version automatically create a Microsoft Teams meeting? =

No. v0.2.1 prepares the data, consent, admin workflow, UTC scheduling record, and Teams URL storage. Microsoft Graph event creation is a later integration.

= Does the visitor book directly on the calendar? =

No. The visitor requests or prepares for a meeting and supplies availability. Sustainable Catalyst reviews the inquiry before approving or scheduling a Teams conversation.

= Can visitors choose Zoom or Google Meet? =

No. Microsoft Teams is the only supported live meeting platform.

= Can visitors upload documents? =

Not yet. Secure protected uploads arrive in v0.3.0.

== Changelog ==

= 0.2.1 =
* Added Microsoft Teams communication preferences and scheduling readiness.
* Added time-zone, location, availability, participants, accessibility, consent, scheduling workflow, and admin controls.

= 0.2.0 =
* Added Adaptive Contact Hub and Conditional Forms.

= 0.1.0 =
* Added private inquiry records, roles, audit history, privacy tools, diagnostics, and administration.
