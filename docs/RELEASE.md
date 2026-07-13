# Release Notes — v0.3.1

## Purpose

Stabilize the v0.3.0 private document pipeline in real WordPress and hosting environments before adding broader review, communication, or sender-portal workflows.

## Atomic storage

Files no longer move directly from PHP temporary storage to their final name.

Each accepted file:

1. moves to a randomized `.part-*` staging name
2. receives restrictive best-effort permissions
3. is rechecked for expected byte size
4. is rechecked for expected SHA-256
5. is atomically renamed to its final `.qtn` name
6. is rechecked after commit
7. locks the effective storage path after success

A failed transaction removes the staged or committed file.

## Request envelope

v0.3.1 detects conditions that commonly appear as unexplained empty forms:

- request content length exceeds PHP `post_max_size`
- PHP file uploads disabled
- temporary upload directory missing or unwritable
- browser selected more files than PHP delivered
- plugin settings exceed PHP file or request limits

The admin-post fallback is marked with `sc_ei_submission=1`, preventing the oversize interceptor from affecting other WordPress forms.

## Reconciliation

The operator can run a read-only scan comparing active attachment rows with managed files.

Detected issue types:

- missing
- SHA-256 mismatch
- size mismatch
- misplaced between quarantine and approved areas
- unresolvable relative path
- orphan `.qtn` file

Per-record verification metadata is updated. Orphans are reported but not removed.

## Retention

A preview shows the expired queue without deletion.

Manual execution requires:

- `sc_intake_delete`
- nonce
- exact phrase `DELETE EXPIRED`

Cron and manual runs use a short-lived lock and save counts, bytes, and failures.

## Cache behavior

The plugin sends explicit no-store headers for forms and REST submissions. Cloudflare and hosting configuration must still exclude the form pages and endpoints from full-page caching.

## Live verification checklist

1. Activate v0.3.1.
2. Open Diagnostics.
3. Confirm migration fields.
4. Run Storage Probe.
5. Run Storage Reconciliation.
6. Preview retention.
7. Submit TXT, PDF, PNG, DOCX, XLSX, and CSV test files.
8. Test one oversized file.
9. Test excessive combined size.
10. Test compact and advanced forms on mobile.
11. Confirm Cloudflare does not cache nonces or REST responses.
12. Verify an attachment manually and download it.
13. Review audit events.

## Idempotency

The client blocks duplicate in-flight submissions. The server atomically locks each request ID, returns HTTP 409 for a concurrent copy, and stores the completed response for 15 minutes. Retrying the same timed-out request returns the original reference and attachment result.

Abandoned request locks older than one hour are removed by a throttled maintenance pass.
