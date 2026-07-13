# Changelog

## 0.3.2 — Quarantine Operations and Scanner Readiness

- Added a cross-inquiry Quarantine Operations workspace.
- Added queue search across filename, SHA-256, inquiry reference, contact, email, and organization.
- Added filters for quarantine, validation, scanner, storage, category, confidentiality, and retention state.
- Added operational summary counts for active, quarantined, approved, replacement, scanner, storage, expired, bytes, and downloads.
- Added scanner attempt count, last scanner time, and last scanner actor fields.
- Added scanner provider and integration-version readiness matching.
- Added generated benign scanner readiness test containing no submitted user data.
- Added readiness freshness policy.
- Added clean-required scanner-mode activation gate.
- Preserved fail-closed behavior when an already-enabled scanner later becomes unavailable.
- Added single-file scanner retry.
- Added configurable bulk scanner retry.
- Added storage and SHA-256 verification before administrative rescan.
- Added automatic physical deletion and rejection when a rescan reports infected.
- Added clear infected-file deletion-failure warning state.
- Added guarded bulk integrity verification.
- Added guarded bulk approval, quarantine, replacement, retention, and rejection actions.
- Limited bulk selection to 50 records.
- Added exact `REJECT SELECTED` confirmation before bulk physical deletion.
- Added approval safeguards for validation, infection, scanner policy, storage, and integrity.
- Added storage utilization and free-space dashboard.
- Added private document access and operations audit.
- Added filters for event, actor, date, inquiry, file, and message.
- Added CSV audit export capped at 5,000 rows.
- Added CSV formula-injection neutralization.
- Added isolation guidance for untrusted documents.
- Added scanner state, attempts, provider, time, and message to inquiry review.
- Added scanner readiness and queue state to Diagnostics.
- Added scanner readiness, freshness, and bulk-limit controls to Settings.
- Added private file operational metadata to privacy export.
- Added v0.3.2 operational option and transient cleanup on explicit uninstall.
- Preserved v0.3.1 atomic storage, reconciliation, request idempotency, retention safety, and cache/CDN reliability.

## 0.3.1 — Production Storage and Upload Reliability

- Added atomic storage commits, request-envelope checks, storage probes, reconciliation, integrity tracking, retention previews, and cache/CDN hardening.

## 0.3.0 — Secure Document Intake and Quarantine

- Added protected multi-file intake, validation, private storage, quarantine, downloads, retention, and privacy erasure.

## 0.2.2 — Dual Intake Experiences and Conversion Routing

- Added compact Consulting intake and advanced Contact Hub.
