=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, fit assessment, human review, privacy, retention, legal hold, communications, secure upload, quarantine, microsoft teams
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private consulting and contact intake with human-controlled fit assessment, privacy and retention governance, reviewed communications, administrative review, secure document quarantine, and Microsoft Teams readiness.

== Description ==

Version 0.7.0 adds Human-Controlled Fit Assessment to the dual intake, secure document quarantine, scanner operations, Administrative Review Workspace, Communication History, and Privacy and Retention Center.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`

Human-controlled fit capabilities:

* Private assessment queue
* Six assessment domains
* Sixteen evidence-backed criteria
* Manual ratings and explicit criterion weights
* Transparent advisory score
* No score thresholds
* No score-generated recommendations
* Human recommendation and confidence
* Service-route recommendation
* Scope and independence boundaries
* Material concern flags
* Evidence notes and private source references
* Conditions, uncertainty, referral, and decline notes
* Human-attestation requirement
* AI and automation assistance disclosure
* Independent second-review workflow
* Second-review triggers for decline, conflict, unsafe scope, and material risk
* Optional distinct original assessor and second reviewer
* Optimistic version locking
* Versioned reassessment history
* Typed finalization confirmation
* Separate typed application to the Review Workspace
* Private JSON assessment export
* Privacy export and approved-erasure redaction
* Human-control diagnostics

The advisory score summarizes ratings selected by an authorized human. It has no acceptance or rejection threshold and never determines the recommendation.

Finalizing an assessment does not:

* accept or reject an inquiry
* change inquiry status
* send email
* schedule a Microsoft Teams meeting
* create a proposal
* create a referral
* change the Review Workspace

Applying a finalized assessment to the Review Workspace is a separate capability-protected and typed-confirmation action. It creates an immutable review snapshot but still does not change inquiry status.

== Assessment domains ==

* Mission and Service Alignment
* Problem and Outcome Clarity
* Evidence and Engagement Readiness
* Feasibility and Delivery Conditions
* Ethics, Independence, Privacy, and Risk
* Learning, Measurement, and Public Value

== Human-control safeguards ==

* Every assessed criterion can require evidence or reasoning.
* Every material concern requires an explicit concern or mitigation note.
* Every criterion must be assessed or marked not applicable before submission.
* The assessor must attest that the recommendation is their own human judgment.
* Assistance used for summarization or analytical support must be disclosed.
* Editing after submission returns the assessment to draft and invalidates prior second-review clearance.
* An “Agree” second review cannot silently change the submitted recommendation, route, or boundary.
* Finalization requires a ready-to-finalize state and exact typed confirmation.
* The fit repository does not call inquiry-status mutation or mail-delivery APIs.

== Installation ==

1. Back up the WordPress database and protected storage directory.
2. Upload and activate v0.7.0.
3. Open Engagement Intake → Diagnostics.
4. Confirm database version 0.7.0.
5. Confirm fit schema version 1.0.0.
6. Confirm the fit assessments, criterion items, and second reviews tables.
7. Open Engagement Intake → Settings.
8. Review evidence, rationale, second-review, staleness, and queue settings.
9. Open a private inquiry.
10. Start a fit assessment.
11. Rate every criterion and record evidence.
12. Save the draft.
13. Submit it into human review.
14. Test a required independent second review.
15. Finalize using the exact typed confirmation.
16. Confirm inquiry status and communications were unchanged.
17. Apply the assessment to the Review Workspace using the separate typed action.
18. Test JSON export, WordPress privacy export, and approved erasure in staging.

== Upgrade from 0.6.0 ==

The upgrade preserves:

* inquiries
* private documents
* review history
* communications and templates
* privacy requests
* consent events
* legal holds
* retention policies and actions
* audit records
* protected storage
* Microsoft Teams scheduling information

It adds:

* fit lifecycle fields to inquiries
* fit assessments table
* fit criterion items table
* fit second reviews table
* fit capabilities
* fit settings
* private queue and detail workspaces
* fit data in review packets and authenticated REST responses
* fit data in WordPress privacy export
* fit narrative redaction during approved inquiry erasure

Migration does not:

* create an assessment for an existing inquiry
* calculate a score
* recommend a service
* accept or reject an inquiry
* change inquiry status
* send an email
* schedule a meeting
* create a proposal or referral

== Frequently Asked Questions ==

= Does the score decide fit? =

No. It only summarizes ratings selected by a human. There are no thresholds or automatic recommendation rules.

= Can the assessment accept or reject an inquiry? =

No. Finalization freezes the assessment record but does not change the inquiry.

= Can a second reviewer change the recommendation while choosing Agree? =

No. An Agree review must confirm the submitted recommendation, route, and boundary. Proposed changes use an explicit changes-requested path.

= What happens when an assessor edits after submission? =

The assessment returns to draft and prior second-review clearance is invalidated.

= Does the system require a second review? =

Configured triggers can require one for a not-a-fit recommendation, conflict or independence concern, unsafe or prohibited scope, or material ethics, privacy, independence, or risk concern.

= Can AI perform the assessment? =

No. Assistance may be disclosed for clerical, summarization, or analytical support, but an authorized human must personally review the records, select ratings, write the recommendation, and attest to the judgment.

= Does applying the assessment change inquiry status? =

No. It creates a Review Workspace snapshot only.

= Are privacy and retention controls preserved? =

Yes. v0.7.0 retains the queue-only retention, legal-hold, approval, verified execution, and tombstone safeguards introduced in v0.6.0.

= Does v0.7.0 create Teams meetings? =

No. Teams remains the only live meeting platform represented, but Microsoft Graph is not connected.

== Changelog ==

= 0.7.0 =
* Added Human-Controlled Fit Assessment.
* Added 16 evidence-backed criteria across 6 assessment domains.
* Added transparent advisory scoring without thresholds.
* Added human recommendations, confidence, service routes, and scope boundaries.
* Added material concerns, evidence notes, source references, limitations, conditions, and referral notes.
* Added human attestation and assistance disclosure.
* Added optimistic locking and assessor ownership.
* Added independent second review and configured review triggers.
* Added workflow reset after post-submission edits.
* Added typed finalization.
* Added separate typed Review Workspace application.
* Added private assessment export.
* Added review packet, REST, privacy export, privacy erasure, inquiry list, settings, and Diagnostics integration.
* Preserved status-neutral and communication-neutral operation.

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
