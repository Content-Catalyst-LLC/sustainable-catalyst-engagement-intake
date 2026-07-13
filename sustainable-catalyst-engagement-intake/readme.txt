=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, intake, administrative review, assignment, secure upload, quarantine, microsoft teams, privacy
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private consulting and contact intake with a human-controlled Administrative Review Workspace, secure document quarantine, scanner readiness, Microsoft Teams scheduling readiness, privacy tools, and audit history.

== Description ==

Version 0.4.0 adds the Administrative Review Workspace above the dual public intake, reliable protected storage, and Quarantine Operations layers.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`

The Review Workspace includes:

* Open, assigned-to-me, unassigned, escalation, and completed queues
* Review ownership and self-claim controls
* Manager assignment and unassignment
* Configurable normal, high, low, and urgent due windows
* Overdue, due-soon, stale, and aging indicators
* Manual fit decision and confidence
* Manual risk, evidence-readiness, and scope-clarity judgments
* Explicit recommended next step
* Explicit inquiry status selection
* Review summary and decision rationale
* Information-gap and conflict/independence notes
* Administrative checklist
* Escalation request, active review, and resolution
* Optimistic version locking against silent reviewer overwrites
* Immutable review snapshots
* Guarded bulk assignment, priority, stage, due-date, and escalation actions
* Private JSON review packet export
* Request, document, scheduling, conversion, and audit context
* Review health metrics in Diagnostics
* WordPress privacy export and erasure for review narratives

The review layer is human-authored. It does not calculate a fit score, automatically accept or reject an inquiry, infer an inquiry status, send a message, schedule a meeting, generate a proposal, or disclose a document.

== Installation ==

1. Back up the WordPress database and private storage directory.
2. Upload and activate v0.4.0.
3. Open Engagement Intake → Diagnostics.
4. Confirm database version 0.4.0.
5. Confirm the `reviews` table and review fields.
6. Open Engagement Intake → Review Workspace.
7. Review unassigned and overdue queues.
8. Configure review deadlines and completion safeguards in Settings.
9. Claim or assign an inquiry.
10. Complete a test review using non-sensitive data.
11. Confirm a review snapshot appears.
12. Export a private review packet.
13. Verify reviewer and manager permissions.

== Upgrade from 0.3.2 ==

The upgrade preserves inquiries, documents, storage, scanner, quarantine, Teams, conversion, privacy, retention, and audit data.

It adds:

* Current review fields to the inquiry table
* A dedicated immutable review snapshot table
* Review assignment and due-date fields
* Manual fit, risk, evidence, and scope fields
* Checklist, escalation, rationale, and information-gap fields
* Review version and completion timestamps
* Reviewer and manager capabilities
* Review settings

Existing open inquiries without a due date receive a due date based on the normal-priority review window. Existing inquiry statuses are not converted into fit decisions or completed reviews.

== Frequently Asked Questions ==

= Does the plugin score inquiry fit? =

No. Fit decision, confidence, risk, evidence readiness, and scope clarity are explicit human judgments.

= Does selecting a recommended next step change the inquiry status? =

No. The reviewer must explicitly select the inquiry status. The next-step field does not send a message, schedule a meeting, or create a proposal.

= How are conflicting edits handled? =

Every current review has a version number. A save based on an older version is rejected and the reviewer must reload the current review.

= Are review changes preserved? =

Yes. Each successful save writes an immutable structured snapshot in addition to updating the current review state.

= Can reviewers edit each other's work? =

By default, a reviewer can edit an unassigned inquiry or the inquiry assigned to them. Managers can reassign and edit any inquiry. This restriction is configurable.

= What does completion require? =

By default, a completed review requires the full checklist, an explicit fit decision, a non-default next step, and a recorded rationale.

= Does a completed review contact the sender? =

No. Sender communications arrive in a later release.

= Does the review packet include document contents? =

No. It includes private inquiry, review, attachment metadata, and audit history in JSON. Physical file contents are not embedded.

== Changelog ==

= 0.4.0 =
* Added the human-controlled Administrative Review Workspace, assignments, due-date and aging visibility, manual fit and risk judgments, review checklist, escalation, immutable snapshots, bulk review operations, private review packet export, privacy integration, and diagnostics.

= 0.3.2 =
* Added cross-inquiry Quarantine Operations and scanner readiness.

= 0.3.1 =
* Added production storage and upload reliability.

= 0.3.0 =
* Added secure document intake and quarantine.

= 0.2.2 =
* Added dual intake experiences and conversion routing.
