# Changelog

## 0.8.1 — Portal Authentication and Recovery Patch

- Made invitation activation atomic across access, inquiry, and session persistence.
- Added rollback that preserves the invitation after safe activation failure.
- Added optimistic invited-state and inquiry-version checks.
- Preserved invitation context after terms, nonce, and retryable activation failures.
- Added verified invitation-state inspection.
- Prevented incorrect invitation tokens from incrementing email lockout.
- Limited lockout to correct-token and incorrect-email failures.
- Added detailed token, email, lockout, rollback, and migration events.
- Enforced HTTPS for production authentication and recovery.
- Replaced the production cookie with `__Host-sc_ei_sender_session`.
- Added v0.8.0 legacy-cookie compatibility and migration.
- Added dual-cookie cleanup.
- Added fresh invitation reissue after rare session-cookie establishment failure.
- Added non-enumerating public recovery request.
- Added keyed-IP throttling covering matched and unmatched attempts.
- Added recovery honeypot, minimum reason, deduplication, and expiry.
- Added a human-reviewed Authentication Recovery Queue.
- Added typed recovery approval and decline.
- Added typed invitation lockout reset.
- Added separate recovery view and management capabilities.
- Added recovery table, indexes, schema diagnostics, and hourly expiration.
- Added recovery data to portal audit export, private inventory, WordPress privacy export, and approved erasure.
- Preserved the prohibition on automatic invitation delivery, WordPress sender accounts, inquiry status changes, and public lookup.

## 0.8.0 — Secure Sender Portal

- Added passwordless sender portal, secure messages, protected follow-up documents, preference updates, privacy requests, withdrawal, sessions, access controls, and audit.

## 0.7.0 — Human-Controlled Fit Assessment

- Added evidence-backed human fit assessment and independent review.

## 0.6.0 — Privacy and Retention Center

- Added privacy requests, consent, legal holds, versioned policies, reviewed retention, verified execution, and tombstones.

## 0.5.0 — Notifications and Communication History

- Added reviewed communication history and notifications.

## 0.4.0 — Administrative Review Workspace

- Added assignment, review judgments, escalation, snapshots, and exports.

## 0.3.2 — Quarantine Operations and Scanner Readiness

- Added quarantine operations and scanner readiness.

## 0.3.1 — Production Storage and Upload Reliability

- Added production protected storage and upload reliability.

## 0.3.0 — Secure Document Intake and Quarantine

- Added private document intake and quarantine.

## 0.2.2 — Dual Intake Experiences and Conversion Routing

- Added compact Consulting and advanced Contact experiences.
