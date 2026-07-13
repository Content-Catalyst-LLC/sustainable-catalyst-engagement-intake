=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, microsoft teams, scheduling, proposal workflow, sender portal, privacy, secure upload, quarantine
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private engagement intake with human-approved Microsoft Teams scheduling offers, versioned proposals, sender-safe portal responses, privacy governance, secure messaging, and protected document quarantine.

== Description ==

Version 0.9.0 adds a controlled Microsoft Teams Scheduling and Proposal Workflow to the Secure Sender Portal and administrative engagement workspace.

It preserves all earlier intake, authentication, recovery, review, fit assessment, communication, privacy, retention, quarantine, scanner, and protected-storage capabilities.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`
* Secure sender portal: `[sc_sender_portal title="Secure Sender Portal"]`

== Teams scheduling ==

Authorized staff can create:

* Draft meeting offers
* Up to the configured number of proposed time slots
* UTC-backed slot records with a sender timezone
* Purpose and preparation notes
* Expiration dates
* Optional Microsoft Teams URL
* Human-published portal offers

The sender can:

* Accept one offered time
* Request an alternative time
* Decline the meeting
* Open the finalized Microsoft Teams link
* Download an authenticated ICS file after final scheduling

The plugin does not:

* Create a Microsoft 365 calendar event
* Call Microsoft Graph
* Send an invitation automatically
* Support Zoom or Google Meet
* Treat a selected time as final when a Teams link is still pending

A staff member must finalize the accepted slot and record the Teams URL.

== Proposal workflow ==

Authorized staff can create structured proposals containing:

* Title and executive summary
* Scope
* Deliverables
* Exclusions
* Assumptions
* Timeline
* Fee summary
* Payment terms
* Proposal terms and boundaries
* Currency and total value
* Expiration date
* Version note

Each version is immutable and receives:

* Sequential version number
* SHA-256 content hash
* Creator and creation time

Published proposal content remains visible while staff prepares an unpublished revision. The revision replaces the sender-visible version only after a deliberate publish action.

== Sender proposal response ==

The sender can:

* Accept for external contracting
* Decline with a note
* Open an authenticated print-friendly view

Acceptance requires:

* Typed `ACCEPT <PROPOSAL-NUMBER>` confirmation
* Authority attestation
* Acknowledgment that portal acceptance is not an executed contract

Decline requires:

* Typed `DECLINE <PROPOSAL-NUMBER>` confirmation
* A response note

Portal acceptance records intent to proceed to contracting. It is not:

* An electronic signature
* An executed contract
* A payment authorization
* An invoice
* An active engagement

== External contract boundary ==

Only authorized staff can mark a proposal contracted.

The administrator must:

* Record an external contract reference
* Add an administrative note
* Type `CONTRACT <PROPOSAL-NUMBER>`

This action attests that an agreement was executed outside the plugin.

The plugin does not generate, sign, or store an electronic signature contract and does not collect payment.

== Workflow expiration ==

Hourly cleanup marks stale:

* Published meeting offers as expired
* Published proposals as expired

The cleanup does not:

* Delete workflow history
* Cancel a finalized meeting
* Withdraw an accepted proposal
* Delete proposal versions
* Change an inquiry to accepted automatically

== Privacy and audit ==

Meeting offers, proposals, versions, and workflow events are included in:

* Private data inventory
* WordPress privacy export
* Authenticated workflow export
* Review packet context
* Authenticated REST context
* Diagnostics
* Approved inquiry erasure

Approved erasure removes personal workflow narratives such as:

* Sender scheduling notes
* Alternative-time requests
* Administrative meeting notes
* Cancellation reasons
* Sender proposal response notes
* External contract references
* Workflow event context

Categorical lifecycle evidence and content hashes can remain as limited audit tombstones.

== Installation ==

1. Back up the database and protected storage.
2. Upgrade from v0.8.1 to v0.9.0.
3. Keep the existing sender portal page and shortcode.
4. Clear WordPress, object, host, reverse-proxy, CDN, and browser caches.
5. Open Engagement Intake → Diagnostics.
6. Confirm database version `0.9.0`.
7. Confirm portal schema version `1.2.0`.
8. Confirm workflow schema version `1.0.0`.
9. Confirm all four workflow tables.
10. Confirm the hourly workflow cleanup event.
11. Open Engagement Intake → Teams & Proposals.
12. Test a draft meeting offer.
13. Publish proposed Teams times.
14. Test sender acceptance and alternative request.
15. Finalize a Teams link and test the ICS file.
16. Create and publish a proposal.
17. Create an unpublished revision and confirm the previous version remains visible.
18. Publish the revision.
19. Test sender acceptance and decline.
20. Test external-contract attestation.
21. Test privacy export and approved erasure in staging.

== Changelog ==

= 0.9.0 =
* Added Teams Scheduling and Proposal Workflow.
* Added human-published meeting offers with multiple UTC-backed slots.
* Added sender accept, alternative, and decline responses.
* Added human meeting finalization with validated Microsoft Teams links.
* Added authenticated ICS download after final scheduling.
* Added structured versioned proposals with SHA-256 content hashes.
* Added pending proposal versions that do not disrupt published content.
* Added typed sender acceptance and decline.
* Added authority and non-contract boundary attestations.
* Added human-recorded external contract references.
* Added workflow events, metrics, exports, privacy integration, erasure, expiration, capabilities, and Diagnostics.
* Preserved no Graph booking, no automatic email, no electronic signature, no payment, and no automatic engagement activation.

= 0.8.1 =
* Added Portal Authentication and Recovery Patch.

= 0.8.0 =
* Added Secure Sender Portal.

= 0.7.0 =
* Added Human-Controlled Fit Assessment.

= 0.6.0 =
* Added Privacy and Retention Center.

= 0.5.0 =
* Added Notifications and Communication History.

= 0.4.0 =
* Added Administrative Review Workspace.

= 0.3.2 =
* Added Quarantine Operations and Scanner Readiness.

= 0.3.1 =
* Added Production Storage and Upload Reliability.

= 0.3.0 =
* Added Secure Document Intake and Quarantine.

= 0.2.2 =
* Added Dual Intake Experiences and Conversion Routing.
