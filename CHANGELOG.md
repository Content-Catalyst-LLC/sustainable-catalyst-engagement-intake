# Changelog

## 0.3.1 — Production Storage and Upload Reliability

- Added atomic protected-file staging and final commit.
- Added post-move size and SHA-256 verification.
- Added final committed-file verification.
- Moved storage-base locking to after the first successful commit.
- Added storage-lock race detection and cleanup.
- Prevented approved/quarantine moves from overwriting destinations.
- Made invalid deletion paths fail instead of reporting false success.
- Added storage write/read/rename/delete probe.
- Added protection-file and directory repair.
- Added stale `.part-*` cleanup.
- Added managed-file inventory and storage utilization.
- Added disk-free and disk-total diagnostics.
- Added persistent attachment storage status.
- Added last verification time, user, source, and message.
- Added per-document storage and integrity verification.
- Added download-time persisted integrity checks.
- Added read-only database-to-filesystem reconciliation.
- Added missing, hash mismatch, size mismatch, misplaced, unresolvable, and orphan categories.
- Added truncated-file-count detection.
- Added `post_max_size` overrun interception for REST and marked admin-post submissions.
- Added file-upload service and temporary-directory checks.
- Added server-aware effective file, per-file, and aggregate limits.
- Added request IDs and request-content-length audit context.
- Added request-level idempotency, concurrent submission locks, confirmation replay, and stale-lock cleanup.
- Added three-minute client upload timeout.
- Added offline-browser submission warning.
- Added browser, proxy, CDN, Cloudflare, and surrogate no-store headers.
- Added retention cleanup preview.
- Added retention cleanup lock, run summary, byte count, and failure count.
- Added guarded manual retention deletion with exact confirmation.
- Added production diagnostics action center.
- Added mobile file-input and long-filename improvements.
- Added atomic storage and upload-envelope executable fixtures.
- Preserved v0.3.0 validation, quarantine, Teams, privacy, and conversion features.

## 0.3.0 — Secure Document Intake and Quarantine

- Added protected multi-file intake and quarantine.

## 0.2.2 — Dual Intake Experiences and Conversion Routing

- Added compact Consulting intake and advanced Contact Hub.
