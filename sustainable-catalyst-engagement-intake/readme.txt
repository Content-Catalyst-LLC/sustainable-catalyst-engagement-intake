=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, privacy, retention, legal hold, consent, communications, administrative review, secure upload, quarantine, microsoft teams
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private consulting and contact intake with a Privacy and Retention Center, reviewed communications, human administrative review, secure document quarantine, Microsoft Teams readiness, and auditable lifecycle controls.

== Description ==

Version 0.6.0 adds a centralized Privacy and Retention Center to the existing dual intake, secure document quarantine, scanner operations, Administrative Review Workspace, and Communication History.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`

Privacy and retention capabilities:

* Private data inventory
* Privacy-request case management
* Identity-verification state
* Request deadlines and assignment
* Consent and authorization ledger
* Notice and consent version evidence
* Consent withdrawal and processing restriction
* Inquiry-level privacy state
* Legal and operational holds
* Hold review dates and release history
* Versioned retention policies
* Deterministic retention previews
* Queue-only daily retention cron
* Human approval before execution
* Optional separation between proposer and approver
* Typed confirmation for irreversible execution
* Physical document deletion verification
* Communication-content redaction
* Transactional inquiry erasure
* Dependency blocking when private documents remain
* Non-personal tombstone preservation
* Private inventory export
* Privacy lifecycle diagnostics
* Privacy context in review packets and authenticated REST responses

The daily retention event never physically deletes a document and never erases inquiry data. It only creates candidates in the private retention queue.

Every lifecycle action must be approved before it can execute. Execution is a separate capability-protected operation and requires an action-specific typed phrase.

The WordPress personal-data eraser is also queue-only. It creates a tracked privacy request and retention actions instead of silently deleting data.

The Privacy and Retention Center is an operational governance tool. It does not determine which laws apply, replace legal advice, or guarantee that a configured period satisfies legal, contractual, insurance, tax, employment, litigation, or professional obligations.

== Installation ==

1. Back up the WordPress database and protected storage directory.
2. Upload and activate v0.6.0.
3. Open Engagement Intake → Diagnostics.
4. Confirm database version 0.6.0 and privacy schema 1.0.0.
5. Confirm privacy-request, consent-event, legal-hold, retention-policy, and retention-action tables.
6. Open Engagement Intake → Privacy Center.
7. Review the private data inventory.
8. Review every active retention policy and period.
9. Confirm the daily retention event is shown as queue-only.
10. Test privacy-request creation and assignment.
11. Test consent-event recording.
12. Place and release a test legal hold.
13. Generate a retention preview.
14. Queue candidates and confirm that no data is deleted.
15. Approve and execute test actions only in staging.
16. Verify private document physical deletion and tombstone preservation.
17. Test WordPress privacy export and queue-only eraser behavior.
18. Review sender suppression for restricted inquiries.
19. Enable distinct proposer/approver separation when staffing permits.

== Upgrade from 0.5.0 ==

The upgrade preserves inquiries, attachments, reviews, communication history, templates, audit records, scanner state, protected storage, and Teams scheduling information.

It adds:

* Privacy lifecycle state fields to inquiries
* Privacy requests table
* Consent events table
* Legal holds table
* Versioned retention policies table
* Retention actions table
* Default policy versions
* Privacy and retention capabilities
* Privacy Center administration
* Queue-only WordPress privacy eraser
* Consent evidence capture on new submissions
* Privacy-state sender-email suppression
* Privacy lifecycle diagnostics

Migration does not:

* enable automatic deletion
* execute a retention action
* erase an existing inquiry
* delete an existing private document
* create a legal conclusion
* send an email
* connect Microsoft Graph
* ingest a mailbox

== Frequently Asked Questions ==

= Will the daily retention cron delete files? =

No. It only queues candidates for human review.

= Can an administrator mark an inquiry erased without running the erasure workflow? =

No. The erased state is reserved for verified execution by the retention engine.

= What blocks an inquiry erasure? =

Any active related legal hold and any undeleted private document.

= Is approval optional? =

No. Approval before execution is permanently required in v0.6.0.

= Can one person both propose and approve an action? =

By default, yes. Administrators can require a different authorized approver.

= What does verified document deletion mean? =

The protected file is deleted, its physical absence is checked, and a non-personal database tombstone is recorded.

= What remains after inquiry erasure? =

A limited operational tombstone can retain the inquiry reference, categorical workflow states, policy/action states, timestamps, internal actor IDs, and audit evidence. Personal narratives, contact information, scheduling details, document files, communication content, consent evidence, request identifiers, and related lifecycle narratives are redacted.

= Does the WordPress privacy eraser delete immediately? =

No. It creates a privacy request case and queues legal-hold-aware actions for review, approval, and verified execution.

= Is the plugin legal advice? =

No. Retention periods and workflows require review for the organization and obligations that actually apply.

= Are automated notifications changed? =

No. The v0.5.0 notification policies remain opt-in and default to disabled.

= Does v0.6.0 create Teams meetings? =

No. Teams remains the only live meeting platform represented, but Microsoft Graph is not connected.

== Changelog ==

= 0.6.0 =
* Added the Privacy and Retention Center.
* Added private data inventory and export.
* Added privacy-request cases, assignments, deadlines, identity state, and resolutions.
* Added consent and authorization ledger with version and evidence.
* Added legal holds, review dates, release reasons, and queue blocking.
* Added versioned retention policies.
* Added queue-only retention previews and daily candidate generation.
* Added mandatory approval and typed execution.
* Added verified private-document deletion.
* Added transactional inquiry and communication erasure.
* Added dependency blocking and non-personal tombstones.
* Added queue-only WordPress privacy eraser behavior.
* Added privacy lifecycle state to inquiry, review, communication, REST, packet, settings, and diagnostics surfaces.
* Repaired an inherited v0.5.0 settings sanitization defect.

= 0.5.0 =
* Added Notifications and Communication History.

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
