# Changelog

## 0.5.0 — Notifications and Communication History

- Added a private Communications submenu and cross-inquiry history.
- Added All History, Drafts, Failed, Inbound, Follow-up Due, Notifications, Templates, and Notification Policy views.
- Added inquiry-specific communication timelines.
- Added plain-text email drafts.
- Added optimistic draft row-version locking.
- Added separate reviewed send action and explicit confirmation.
- Added send locks against concurrent duplicate attempts.
- Added WordPress `wp_mail()` failure capture.
- Added honest `accepted` transport state without delivery claims.
- Added failed state, error codes, messages, attempt counts, and retry.
- Added canceled and suppressed states.
- Made accepted, received, recorded, canceled, and suppressed records immutable.
- Added immutable communication event history.
- Added outbound, inbound, internal, and system directions.
- Added email, Teams message, Teams meeting, phone, video, in-person, and other channels.
- Added manual inbound and external interaction recording.
- Added communication thread state.
- Added follow-up dates and due queues.
- Added unread inbound counts and mark-read control.
- Added do-not-email suppression with required rationale.
- Added communication aggregates to inquiry records and the general inquiry list.
- Added communication links to Review Workspace and full inquiry records.
- Added communication history to private review packets.
- Added versioned plain-text templates.
- Added an allowlisted template variable engine.
- Added sender acknowledgment, general response, request-information, fit-call, Teams confirmation, consultation, referral, and decline templates.
- Added internal new-inquiry, review-due, follow-up, and escalation templates.
- Added default-off sender acknowledgment.
- Added default-off internal new-inquiry alerts.
- Added default-off assigned-reviewer reminders.
- Added default-off follow-up reminders.
- Added default-off escalation alerts.
- Added sender-readiness gate before enabling automation.
- Added hourly notification cron and lock.
- Added configurable lead time and batch limit.
- Added notification deduplication keys.
- Added plain-text mail transport test.
- Added transport policy diagnostics.
- Added communication tables and fields to Diagnostics.
- Added authenticated REST communication history.
- Added private CSV communication export.
- Added spreadsheet formula neutralization.
- Added communication records and events to WordPress privacy export.
- Added communication and event narrative erasure.
- Added communication and notification capabilities.
- Added cleanup for cron state and mail locks.
- Added responsive communication workspace styling.
- Added template replacement confirmation and unsaved-draft protection.
- Preserved Administrative Review, Quarantine Operations, scanner readiness, storage reliability, and dual intake experiences.

## 0.4.0 — Administrative Review Workspace

- Added human-controlled review assignment, due dates, fit and risk judgments, checklists, escalation, immutable snapshots, and review packet export.

## 0.3.2 — Quarantine Operations and Scanner Readiness

- Added cross-inquiry quarantine operations, scanner readiness, retries, bulk controls, access audit, and isolation guidance.

## 0.3.1 — Production Storage and Upload Reliability

- Added atomic storage commits, reconciliation, request-envelope checks, and retention safety.

## 0.3.0 — Secure Document Intake and Quarantine

- Added protected document intake and quarantine.

## 0.2.2 — Dual Intake Experiences and Conversion Routing

- Added compact Consulting and advanced Contact experiences.
