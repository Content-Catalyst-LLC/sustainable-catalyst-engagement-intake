# Changelog

## 0.6.0 — Privacy and Retention Center

- Added a centralized private Privacy and Retention Center.
- Added Overview, Privacy Requests, Consent Ledger, Legal Holds, Retention Queue, Policies, and Operating Method views.
- Added private data inventory and aggregate JSON export.
- Added privacy-request cases.
- Added access, erasure, restriction, correction, withdrawal, objection, portability, and other request types.
- Added identity-verification states.
- Added request assignment, due dates, overdue metrics, summaries, resolutions, and completion.
- Added consent and authorization ledger.
- Added privacy notice, request processing, calendar, participant, communication, and document authorization types.
- Added granted, renewed, withdrawn, corrected, expired, and not-applicable actions.
- Added consent/version/source/evidence/basis/timestamp records.
- Added public-form privacy and calendar consent capture.
- Added processing restriction after consent withdrawal.
- Added inquiry privacy states.
- Added legal and operational holds.
- Added inquiry and attachment hold scopes.
- Added hold authority, reason, review date, placement, release, and release rationale.
- Added active-hold counters on inquiries.
- Added hold blocking for queue, approval, and execution.
- Added versioned retention policies.
- Added six default lifecycle policy families.
- Added deterministic candidate previews.
- Replaced destructive legacy retention cron behavior with queue-only candidate generation.
- Added unique retention-action deduplication.
- Added queued, blocked, approved, executing, executed, failed, canceled, and skipped action states.
- Made approval before execution mandatory.
- Added optional distinct proposer/approver enforcement.
- Added typed action-specific execution confirmation.
- Added physical private-document deletion verification.
- Added attachment tombstone preservation.
- Added communication and delivery-event redaction.
- Added transactional inquiry erasure.
- Added dependency blocking while private documents remain.
- Added consent evidence and email-hash erasure.
- Added privacy-request identifier and narrative erasure.
- Added released-hold narrative and authority erasure.
- Added retention snapshot and failure-narrative erasure.
- Added non-personal inquiry tombstones.
- Added queue-only WordPress personal-data eraser behavior.
- Added complete privacy lifecycle records to WordPress privacy export.
- Added privacy-state sender email suppression.
- Added privacy context to authenticated REST responses and review packets.
- Added lifecycle warnings and links across inquiry, review, and communication workspaces.
- Added privacy lifecycle columns to the inquiry list.
- Added privacy tables, policy state, queue state, metrics, cron state, and fixed-safety checks to Diagnostics.
- Added privacy and retention capabilities.
- Added cleanup for lifecycle options and tables on explicit uninstall deletion.
- Repaired inherited v0.5.0 settings code that referenced an undefined value inside `default_settings()`.

## 0.5.0 — Notifications and Communication History

- Added reviewed plain-text communication, versioned templates, immutable transport events, opt-in notifications, inbound and Teams interaction history, follow-up controls, suppression, exports, privacy integration, and diagnostics.

## 0.4.0 — Administrative Review Workspace

- Added human-controlled review assignment, judgments, checklists, escalation, snapshots, and review packet export.

## 0.3.2 — Quarantine Operations and Scanner Readiness

- Added cross-inquiry quarantine operations, scanner readiness, retries, guarded bulk controls, and access audit.

## 0.3.1 — Production Storage and Upload Reliability

- Added atomic storage, environment checks, reconciliation, and retention safety.

## 0.3.0 — Secure Document Intake and Quarantine

- Added protected document intake and quarantine.

## 0.2.2 — Dual Intake Experiences and Conversion Routing

- Added compact Consulting and advanced Contact experiences.
