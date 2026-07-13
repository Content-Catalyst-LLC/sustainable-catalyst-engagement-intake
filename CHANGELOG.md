# Changelog

## 0.9.1 — Microsoft Graph Reliability Patch

- Added an optional Microsoft Graph v1.0 calendar connector.
- Added application-only client-credentials authentication.
- Added `Calendars.ReadWrite` application workflow documentation.
- Added separate authenticated-encryption credential storage.
- Added Sodium secretbox and OpenSSL AES-256-GCM support.
- Added secret fingerprint and expiry metadata.
- Added encrypted access-token caching.
- Added old and new token-cache invalidation during credential rotation.
- Corrected OAuth scope to `https://graph.microsoft.com/.default`.
- Restricted connector requests to global Graph `/users/` calendar paths.
- Added human-triggered event creation after sender time acceptance.
- Added persistent Graph `transactionId`.
- Added local SHA-256 idempotency and request hashes.
- Added encrypted durable operation payloads.
- Added create, reconcile, and delete queue operations.
- Added optimistic operation claims and row versions.
- Added stale-lock recovery.
- Added `Retry-After` handling.
- Added bounded exponential backoff with jitter.
- Added maximum retry attempts.
- Added circuit breaker and human reset.
- Added one-time token refresh after 401.
- Added request ID and client-request-ID capture.
- Added Teams `onlineMeeting.joinUrl` reconciliation.
- Added local-state checks before remote creation.
- Prevented remote reconciliation from reopening closed local meetings.
- Added explicit remote event deletion.
- Added manual permanent-failure retry preserving idempotency.
- Added manual local-linkage reset.
- Added connector health test.
- Added encrypted credential and queue administration.
- Added per-meeting Graph status and operation history.
- Added redacted Graph operation export.
- Added Graph privacy inventory, WordPress export, workflow export, erasure, and Diagnostics.
- Added least-privilege Graph capabilities.
- Preserved the manual Teams URL workflow.
- Preserved no automatic proposal, contract, signature, invoice, payment, or engagement activation.

## 0.9.0 — Teams Scheduling and Proposal Workflow

- Added human-approved Teams offers, sender responses, finalization, ICS, versioned proposals, and external-contract attestation.

## 0.8.1 — Portal Authentication and Recovery Patch

- Added atomic authentication, lockout correction, `__Host-` cookies, and human-reviewed recovery.

## 0.8.0 — Secure Sender Portal

- Added passwordless sender portal, secure messages, protected documents, and privacy controls.
