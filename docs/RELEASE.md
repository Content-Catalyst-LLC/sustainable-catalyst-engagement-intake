# Release Notes — v0.4.0

## Purpose

Create a serious internal workspace for human review of consulting, collaboration, technical, institutional, media, workshop, and general inquiries.

The workspace is designed to help reviewers reason consistently without pretending that fit can be reduced to an automated score.

## Review queue

The queue supports:

- ownership
- review stage
- priority
- due date
- fit decision
- risk
- evidence readiness
- scope clarity
- next step
- document attention
- inquiry status
- age and idle time
- search and filtering

## Review detail

The detail workspace combines:

- current review form
- assignment
- due state
- inquiry and sender context
- deterministic intake-routing context
- Teams request state
- document readiness summary
- checklist
- escalation
- immutable review history
- recent audit events
- private review packet export

Document scan, download, approval, retention, and deletion controls remain in the full inquiry and Quarantine workspaces. The review workspace links to them rather than duplicating risky operations.

## Concurrency

The current inquiry row contains `review_version`.

Every save includes the version rendered to the reviewer. The database update requires that version to remain current and increments it on success.

```text
expected version matches
→ current state update
→ immutable snapshot insert
→ commit
```

A stale save rolls back and returns a conflict message.

## Completion

When safeguards are enabled, review completion requires:

- all checklist items
- fit decision other than undecided
- recommended next step other than continue review
- recorded decision rationale

## Escalation

Active escalation requires a reason.

Escalation is a review state, not a message or notification. Notification and communication history are scheduled for v0.5.0.

## Bulk operations

Bulk operations are manager-only and preserve per-inquiry validation.

A bulk completion operation does not bypass completion requirements.

## Migration

Existing open inquiries receive a normal-priority due date calculated from their original creation time.

Existing inquiry statuses are not translated into fit decisions. No review is marked completed automatically.

## Live verification checklist

1. Back up database and protected storage.
2. Upgrade to v0.4.0.
3. Confirm `reviews` table in Diagnostics.
4. Confirm all review columns.
5. Confirm reviewer and manager role capabilities.
6. Open the Review Workspace.
7. Claim an unassigned inquiry.
8. Test manager reassignment.
9. Save a partial review.
10. Confirm snapshot v1.
11. Open the same review in two browser sessions.
12. Save session one.
13. Confirm session two receives a conflict.
14. Test checklist completion safeguard.
15. Test rationale safeguard.
16. Test escalation reason safeguard.
17. Test bulk assignment.
18. Test review packet export.
19. Test privacy export.
20. Test privacy erasure in staging.
