# Engagement Analytics and Service Intelligence

Version 1.6.0 extends the existing aggregate analytics layer across the complete engagement lifecycle.

## Included evidence

- Inquiry volume, routing, review, decision, meeting, proposal, contract, and activation funnels
- Support triage, resolution, known-issue, product, component, and privacy-safe signal patterns
- Meeting, proposal, engagement, workspace, milestone, deliverable, and completion activity
- Cycle-time medians, operational attention queues, and weekly aggregate series

## Privacy and decision boundaries

- Grouped metrics use minimum-cohort suppression.
- Snapshots exclude sender names, contact details, message bodies, document contents, tokens, and file contents.
- Finding evidence rejects direct personal-data keys and common email, phone, and IP-address patterns.
- The platform does not rank senders, score individual staff, infer protected traits, or make service decisions automatically.
- A finding is an aggregate observation requiring human review. It cannot create a proposal, communication, task, commitment, or status transition outside its own review record.

## Human-reviewed findings

Findings move through Candidate, Reviewing, Actioned, Dismissed, and Closed states. Transitions require typed confirmation, optimistic row-version checks, a decision note, and an audit event. If the event cannot be written, the transition is compensated by restoring the prior state.

## Snapshots and retention

Authorized administrators can save a JSON snapshot and SHA-256 content hash. Production Readiness expects a current version-bound snapshot. Only Closed or Dismissed findings are automatically eligible for aggregate-intelligence retention cleanup.
