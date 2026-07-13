# Sustainable Catalyst Engagement Intake

**Version:** 0.7.0  
**Release:** Human-Controlled Fit Assessment

v0.7.0 adds an evidence-backed fit layer between private intake and engagement decisions:

```text
private inquiry
→ administrative review
→ human fit assessment
→ independent review when required
→ final assessment record
→ separately authorized Review Workspace application
```

It does not automate acceptance, rejection, status changes, communication, scheduling, proposals, or referrals.

## Public shortcodes

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
```

```text
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
```

## Fit workspace

Open:

```text
Engagement Intake → Fit Assessment
```

The workspace includes:

- queue metrics
- state and ownership filters
- human-control disclosures
- assessment creation
- criterion evidence
- material concerns
- human recommendation and route
- independent second review
- typed finalization
- explicit Review Workspace application
- private JSON export

## Six assessment domains

```text
Mission and Service Alignment
Problem and Outcome Clarity
Evidence and Engagement Readiness
Feasibility and Delivery Conditions
Ethics, Independence, Privacy, and Risk
Learning, Measurement, and Public Value
```

## Sixteen criteria

```text
mission_alignment
service_alignment
problem_clarity
outcome_clarity
evidence_readiness
stakeholder_readiness
decision_authority
budget_feasibility
timing_feasibility
implementation_readiness
ethics_public_interest
privacy_confidentiality
conflict_independence
risk_manageability
measurement_readiness
public_value
```

Each criterion stores:

```text
human rating
transparent weight
numeric rating value
applicability
material-concern flag
evidence and reasoning
concern or mitigation
private source references
row version
timestamps
```

## Advisory score

The optional score is:

```text
sum(human rating × disclosed weight)
÷
sum(maximum rating × disclosed weight)
× 100
```

It is only a compact summary of manually selected ratings.

The system has:

```text
no acceptance threshold
no rejection threshold
no score-to-recommendation rule
no automatic routing rule
```

A low or high signal does not create a decision.

## Recommendations

Authorized humans can select:

```text
Undecided
Strong Fit
Possible Fit
Conditional Fit
Needs Clarification
Limited Fit
Referral Candidate
Not a Fit
```

Confidence is separately recorded as:

```text
Not Assessed
Low
Moderate
High
```

## Service routes

The human reviewer can recommend:

```text
Continue Review
Request More Information
Free 20-Minute Fit Call
Paid Consultation
Evidence and Claims Audit
Evidence Systems Diagnostic
Knowledge Architecture
Technical Storytelling or Product Dossier
Measurement and Indicator Design
Decision Dossier or Systems Analysis
Responsible AI or Knowledge Workflow
Strategy Sprint
Workshop or Training
Monthly Advisory Retainer
Institutional Partnership Discussion
Referral
Not Yet — Revisit Later
Decline
```

The route is a recommendation only.

## Scope boundaries

```text
Within Scope
Potentially In Scope After Reframing
Capacity Constraint
Budget or Resource Mismatch
Timing Mismatch
Conflict or Independence Concern
Requires Legal or Regulatory Expertise
Requires Medical or Clinical Expertise
Unsafe, Prohibited, or Inappropriate Scope
Outside Scope
```

## Human attestation

Before submission, the assessor must attest that:

- they personally reviewed the inquiry and relevant private records
- ratings represent their judgment
- recommendation and route represent their judgment
- scope and limitations are recorded
- assistance is disclosed

Assistance disclosure options include:

```text
No assistance
Clerical only
Summarization
Analytical support with retained human judgment
Other disclosed assistance
```

## Independent second review

A second review can be required for:

- not-a-fit recommendation
- conflict or independence boundary
- unsafe or prohibited scope
- material ethics concern
- material privacy concern
- material independence concern
- material risk concern

The second reviewer records:

```text
Agree
Agree with Required Changes
Disagree
Escalate for Additional Review
```

An Agree disposition must confirm the submitted recommendation, service route, and scope boundary.

The original assessor and second reviewer can be required to differ.

## Workflow integrity

```text
draft
→ submitted
→ second review when required
→ ready to finalize
→ finalized
```

Alternative states:

```text
changes requested
superseded
withdrawn
```

Any edit after submission:

```text
returns the assessment to draft
clears submitted time
clears second-review disposition
clears second reviewer
requires fresh submission and review
```

Drafts use optimistic `row_version` locking.

## Finalization

Finalization requires:

```text
ready_to_finalize state
complete criteria
required evidence
human attestation
recommendation
confidence
rationale
resolved second review
typed FINALIZE <assessment-id>
```

Finalization does not call:

```text
inquiry status mutation
mail transport
Teams scheduling
proposal generation
referral dispatch
```

## Review Workspace application

After finalization, an authorized manager may type:

```text
APPLY <assessment-id>
```

This creates a new immutable Review Workspace snapshot containing:

- fit decision
- confidence
- recommended next step
- summary
- rationale
- conflict notes when applicable

It does not change inquiry status.

## Privacy and retention

WordPress privacy export includes:

- assessment headers
- human recommendation and route
- advisory signal
- criterion ratings and weights
- criterion evidence and source references
- material concerns
- second-review history
- assistance disclosure

Approved inquiry erasure redacts:

- assessment summaries
- recommendation rationale
- limitations
- conditions
- referral notes
- second-review reasons
- assistance notes
- criterion evidence
- concern notes
- source references
- second-review notes
- required changes
- reviewer conflict disclosures

Categorical lifecycle state, timestamps, internal actor IDs, and audit evidence can remain as non-personal tombstones.

## New database tables

```text
{prefix}sc_ei_fit_assessments
{prefix}sc_ei_fit_assessment_items
{prefix}sc_ei_fit_assessment_reviews
```

## New inquiry fields

```text
fit_assessment_status
current_fit_assessment_id
fit_assessment_updated_at
fit_assessment_finalized_at
fit_assessment_version
```

## New capabilities

```text
sc_intake_view_fit_assessments
sc_intake_create_fit_assessments
sc_intake_review_fit_assessments
sc_intake_finalize_fit_assessments
sc_intake_apply_fit_to_review
sc_intake_manage_fit_settings
sc_intake_export_fit_assessments
```

Reviewers can create and independently review assessments.

Managers can finalize and explicitly apply assessments.

## Production verification

1. Back up the database and protected storage.
2. Upgrade to v0.7.0.
3. Confirm DB version `0.7.0`.
4. Confirm fit schema `1.0.0`.
5. Confirm all three fit tables and five inquiry fields.
6. Review fit settings.
7. Start an assessment from an inquiry.
8. Verify evidence requirements.
9. Verify optimistic conflict handling in two browser sessions.
10. Verify a post-submission edit resets review clearance.
11. Verify a triggered independent second review.
12. Verify Agree cannot change the submitted conclusion.
13. Finalize with typed confirmation.
14. Confirm inquiry status, communications, and scheduling remain unchanged.
15. Apply to Review Workspace with separate typed confirmation.
16. Test assessment export.
17. Test WordPress privacy export.
18. Test approved inquiry erasure in staging.

## Legal and professional boundary

The fit workspace supports structured internal judgment.

It does not determine:

- whether a relationship is legally permissible
- whether a conflict is waivable
- whether legal, medical, clinical, accounting, or regulated expertise is required
- whether a price or contract term is appropriate
- whether an engagement should ultimately be accepted
- whether a retention or disclosure obligation applies

Use appropriate legal, professional, ethical, and organizational review.
