# Changelog

## 1.0.1 — Production Validation and Migration Reliability Patch

- Fixed the Platform Overview and Diagnostics fatal error caused by direct access to the private hardening watchdog hook constant.
- Added `SC_EI_Hardening_Repository::watchdog_hook()` as the supported cross-component contract.
- Made the stable platform readiness version check accept patch releases while still requiring the stored plugin version to match.
- Added a repository-wide private/protected constant visibility regression test.
- Corrected the release packaging script from v0.12.0 to v1.0.1.
- Preserved database and schema versions at 1.0.0; no data migration is required.

## 0.12.0 — Workflow Core Integration

- Added canonical case projections derived from authoritative workflow records.
- Added versioned projection hashes and consistency checks.
- Added an idempotent command ledger with optimistic claims.
- Added signed, versioned cross-plugin handoff packages.
- Added operational-minimum and internal-private classifications.
- Added a durable internal-adapter outbox with bounded retry and stale-claim recovery.
- Added explicit internal adapter registration and acknowledgment.
- Added audit-driven deferred case synchronization.
- Added capability-gated read-only Workflow Core REST resources.
- Added Workflow Core administration, Diagnostics, review-packet, privacy, reliability, and uninstall integration.
- Added integrity-preserving privacy tombstones.
- Preserved no automatic acceptance, fit decision, proposal, contract, activation, project creation, external delivery, or inbound commands.

## 0.11.0 — Reliability, Accessibility, and Security Hardening

- Added the Reliability administration workspace.
- Added a deduplicated operational health-event ledger.
- Added a database-backed rate-limit ledger.
- Added keyed hashing for public request identity metadata.
- Added atomic rate-limit upserts.
- Added public inquiry identity and network limits.
- Added sender portal activation, recovery, and authenticated-action edge limits.
- Added incident public-write pause and recovery.
- Preserved read-only portal access and sign-out during incident pause.
- Added hourly production-readiness watchdog.
- Added daily health and rate-limit pruning.
- Added actual portal, workflow, retention, notification, Graph, analytics, and hardening cron checks.
- Added private storage marker, writability, and protection checks.
- Added request correlation IDs.
- Added secret-filtered health context.
- Added fatal plugin error metadata capture without raw error messages or traces.
- Added security headers and optional CSP report-only mode.
- Added redacted operational export.
- Added typed event resolution and audit notes.
- Added skip links, primary-content targets, live regions, invalid-field announcements, and busy submit states.
- Added visible focus, reduced-motion, forced-colors, and keyboard-scrollable table support.
- Added hardening Diagnostics integration.
- Added hardening capabilities and role assignments.
- Preserved all human-controlled fit, proposal, contract, Graph, privacy, retention, and engagement boundaries.

## 0.10.0 — Inquiry Analytics and Operational Intelligence

- Added aggregate funnel, timing, workload, cohort suppression, snapshots, and exports.

## 0.9.2 — Proposal and Engagement Handoff

- Added controlled contracted-proposal handoff, immutable snapshots, readiness, and human activation.
