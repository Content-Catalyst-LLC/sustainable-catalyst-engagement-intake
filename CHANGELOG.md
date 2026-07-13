# Changelog

## 0.4.0 — Administrative Review Workspace

- Added a private Administrative Review Workspace submenu.
- Added open, mine, unassigned, escalation, completed, and method views.
- Added review queue metrics for open, unassigned, mine, overdue, due soon, escalated, decision ready, completed, document attention, and Teams attention.
- Added review ownership and reviewer self-claim.
- Added manager assignment and unassignment.
- Added configurable assignee-only reviewer editing.
- Added current review stage.
- Added review priority and due date.
- Added normal, high, low, and urgent due-window settings.
- Added age, idle-time, stale, due-soon, and overdue indicators.
- Added manual fit decision.
- Added manual fit confidence.
- Added manual risk level.
- Added manual evidence readiness.
- Added manual scope clarity.
- Added explicit recommended next step.
- Kept inquiry status explicitly separate from fit and next-step fields.
- Added review summary.
- Added decision rationale.
- Added information-gap and question record.
- Added conflict, independence, privacy, and reputational notes.
- Added a nine-item administrative review checklist.
- Added escalation request, under-review, resolved, reason, and resolution record.
- Added configurable rationale and completion-checklist safeguards.
- Added explicit completion requirements for fit decision and next step.
- Added optimistic review-version locking.
- Added transactionally paired current-state updates and immutable review snapshots.
- Added dedicated `sc_ei_reviews` table.
- Added assignment, review, fit, risk, evidence, scope, escalation, and timing indexes.
- Added migration backfill for review due dates and checklist JSON.
- Added guarded bulk assignment, unassignment, priority, stage, due-date, escalation, and resolution actions.
- Added configurable bulk limit with hard maximum 50.
- Added private JSON review packet export without physical document contents.
- Added request, conversion, Teams, document, review history, and audit context to review detail.
- Added review status and due visibility to the general inquiry list.
- Added review settings and direct navigation.
- Added review table and queue health to Diagnostics.
- Added review fields and snapshots to WordPress privacy export.
- Added review narrative erasure to WordPress privacy erasure.
- Added reviewer, assignment, priority, escalation, bulk, and review-export capabilities.
- Added mobile and responsive Review Workspace styling.
- Added unsaved-change warning and live checklist progress.
- Added review schema, operations, privacy, migration, and fresh-package tests.
- Preserved v0.3.2 Quarantine Operations, scanner readiness, v0.3.1 storage reliability, and v0.3.0 secure document intake.

## 0.3.2 — Quarantine Operations and Scanner Readiness

- Added cross-inquiry quarantine operations, scanner readiness, retry, bulk file controls, access audit, and isolation guidance.

## 0.3.1 — Production Storage and Upload Reliability

- Added atomic storage commits, request-envelope checks, reconciliation, and retention safety.

## 0.3.0 — Secure Document Intake and Quarantine

- Added protected multi-file intake, validation, quarantine, controlled downloads, and privacy erasure.

## 0.2.2 — Dual Intake Experiences and Conversion Routing

- Added compact Consulting intake and advanced Contact Hub.
