# Sustainable Catalyst Engagement Intake

**Version:** 0.4.0  
**Release:** Administrative Review Workspace

v0.4.0 adds the human administrative decision layer above:

```text
Dual public intake
→ private inquiry records
→ protected document quarantine
→ scanner and storage operations
→ administrative review
```

## Public shortcodes

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
```

```text
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
```

## Administrative Review Workspace

Open:

```text
Engagement Intake → Review Workspace
```

Views:

- Open Queue
- My Reviews
- Unassigned
- Escalations
- Completed
- Review Method

The workspace summarizes:

- assignment
- review stage
- priority and due date
- age and idle time
- fit decision and confidence
- risk
- evidence readiness
- scope clarity
- recommended next step
- checklist progress
- escalation
- inquiry status
- document attention
- Teams request state

## Human-authored review model

v0.4.0 intentionally does **not** include automated fit scoring.

Reviewers explicitly record:

```text
fit_decision
fit_confidence
risk_level
evidence_readiness
scope_clarity
recommended_next_step
review_summary
decision_rationale
information_gaps
conflict_notes
review_checklist
escalation_status
escalation_reason
```

A recommended next step does not automatically:

- change the inquiry status
- send correspondence
- schedule a Teams meeting
- accept work
- create a proposal
- release a document

The inquiry status has its own explicit field.

## Current state and immutable history

The inquiry table stores the current review state for fast queue operations.

The `sc_ei_reviews` table stores immutable structured snapshots:

```text
current review save
→ optimistic version check
→ current inquiry update
→ review snapshot insert
→ commit
→ audit events
```

If another reviewer saved first, the stale save is rejected with `review_conflict`.

## Assignment model

Reviewer:

- can see the workspace
- can claim unassigned work when allowed
- can edit their assigned review
- can record review fields, checklist, rationale, and escalation
- cannot assign another person or run bulk review actions by default

Manager:

- can assign and unassign
- can edit any review
- can run guarded bulk operations
- can export review packets

Default roles remain:

```text
Engagement Reviewer
Engagement Manager
Administrator
```

## Completion safeguards

Configurable safeguards default to enabled:

- rationale required for a fit decision
- rationale required for active escalation
- rationale required for completion
- full checklist required for completion
- explicit fit decision required
- explicit non-default next step required

## Review deadlines

Defaults:

```text
normal: 3 days
high: 1 day
low: 7 days
urgent: 4 hours
stale: 7 idle days
```

These are operational targets, not automated promises to the sender. Notifications arrive in v0.5.0.

## Guarded bulk actions

Manager-only bulk actions:

- assign reviewer
- unassign
- set priority
- set review stage
- set due date
- request escalation
- resolve escalation

Controls:

- configurable limit
- hard maximum of 50 selected inquiries
- nonce
- capability checks
- current review version on each record
- per-inquiry validation
- result summary
- audit event

A bulk request to mark reviews completed still fails for records that lack required checklist, fit, next-step, or rationale data.

## Private review packet

Authorized users can export:

```text
sc-engagement-intake-review-packet/1.0
```

The JSON packet contains:

- inquiry record
- structured review history
- sanitized attachment metadata
- audit history

It does not include physical document contents.

## Privacy

WordPress privacy export includes current administrative review fields and structured review snapshots associated with the inquiry.

Privacy erasure removes:

- current review narratives
- rationale
- information gaps
- conflict notes
- escalation narrative
- review snapshot narratives and event notes

Categorical operational history is retained for accountability unless the entire plugin data set is explicitly removed through uninstall configuration.

## Existing secure-document layer

v0.4.0 retains:

- no Media Library attachment
- no public file URL
- strict document validator
- protected storage
- atomic file commit
- SHA-256 verification
- scanner readiness and retry
- Quarantine Operations
- retention controls
- access audit
- Cloudflare and cache no-store controls

## Release boundary

This release creates internal review recommendations. It does not implement sender notifications, correspondence history, sender portal access, Microsoft Graph scheduling, proposal delivery, or contracting.
