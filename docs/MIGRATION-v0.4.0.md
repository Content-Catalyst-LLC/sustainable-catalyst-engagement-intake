# Migration to v0.4.0

## Database version

```text
SC_EI_DB_VERSION = 0.4.0
```

## New table

```text
{prefix}sc_ei_reviews
```

The table stores immutable structured snapshots, not physical documents.

Key fields:

```text
inquiry_id
reviewer_user_id
event_type
from_stage
to_stage
priority
fit_decision
fit_confidence
risk_level
evidence_readiness
scope_clarity
recommended_next_step
summary
rationale
information_gaps
conflict_notes
checklist_json
escalation_status
escalation_reason
assigned_user_id
due_at
inquiry_status
review_version
snapshot_json
created_at
```

## New inquiry fields

```text
assignment_at
assignment_by
review_stage
review_priority
review_due_at
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
review_started_at
last_reviewed_at
last_reviewed_by
decision_at
review_completed_at
review_version
```

## Backfill

For existing inquiries:

- database defaults initialize categorical fields
- missing checklist JSON becomes a complete allowlisted zero-value checklist
- open reviews with no due date receive `created_at + default_review_due_days`
- no inquiry status is converted into a fit decision
- no existing inquiry is automatically marked review completed
- no immutable snapshot is fabricated for historical activity

## New capabilities

```text
sc_intake_manage_review
sc_intake_assign_inquiries
sc_intake_manage_review_priority
sc_intake_escalate_review
sc_intake_bulk_review_actions
sc_intake_export_review_packet
```

Reviewer receives:

```text
manage review
manage priority
escalate review
export review packet
```

Manager receives all new capabilities.

## Settings

Defaults:

```text
default_review_due_days = 3
high_priority_review_due_days = 1
low_priority_review_due_days = 7
urgent_review_due_hours = 4
stale_review_days = 7
review_bulk_limit = 50
reviewer_self_assignment = true
restrict_review_to_assignee = true
require_review_rationale = true
require_completion_checklist = true
```

## Upgrade sequence

1. Back up database.
2. Back up private document storage.
3. Install v0.4.0.
4. Confirm database migration.
5. Confirm due-date backfill.
6. Confirm custom roles.
7. Review Settings.
8. Open Unassigned queue.
9. Assign legacy inquiries.
10. Do not assume legacy statuses represent completed reviews.

## Rollback

v0.3.2 does not understand review fields or the review table.

A rollback should preserve:

- inquiry table
- reviews table
- audit table
- private storage
- plugin settings

Do not drop the review table merely to make the older interface appear clean.
