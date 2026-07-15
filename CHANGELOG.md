# Changelog

## 1.4.0 — Proposals, Statements of Work, and Engagement Approvals

- Added governed proposal states, current-version enforcement, immutable sender-action receipts, and human-controlled proposal decisions.
- Added versioned Statements of Work with internal approval, sender-safe review, and explicit sender approval evidence.
- Added typed change requests with scope, timeline, and fee impact plus governed requested, review, approval, decline, application, and withdrawal states.
- Added idempotent conversion from a contracted proposal and sender-approved SOW into an engagement handoff.
- Added a Proposal Governance administration workspace, Sender Portal SOW view, reviewable communication templates, privacy export/redaction, REST export, readiness, and Live Validation.
- Added four nondestructive governance tables and advanced database and platform evidence versions to 1.4.0.
- Preserved the boundary against automatic contracts, electronic signatures, payments, proposal decisions, and engagement activation.

## 1.3.1 — Scheduling, Reminder, and Time-Zone Reliability Patch

- Rejects nonexistent and ambiguous daylight-saving local times instead of silently normalizing meeting times.
- Adds deterministic UTC conversion evidence for valid local civil times.
- Makes cancellation reminders eligible after cancellation and post-meeting reminders eligible after completion.
- Closes stale pre-meeting and orphaned reminders through a bounded, nondestructive repair.
- Requires a ready-for-review reminder and an accepted or recorded outbound communication for the same inquiry before marking it sent.
- Adds compensating rollback when rescheduling cannot regenerate reminder evidence.
- Stages post-meeting context and requested follow-up tasks before completion becomes canonical.
- Adds reminder-integrity metrics, Production Readiness checks, and expanded Live Validation.
- Keeps database version `1.3.0`; advances Platform evidence schema to `1.3.1` and Calendar schema to `1.0.1`.

## 1.3.0 — Microsoft Teams and Calendar Coordination

- Added governed Microsoft Teams scheduling, explicit IANA time zones, UTC storage, rescheduling history, reviewable reminders, cancellation safety, Sender Portal meeting projection, and post-meeting follow-up.
- Added calendar-coordination fields to meeting offers and the meeting-reminders table.
- Advanced database version to `1.3.0`, Platform evidence schema to `1.3.0`, and Calendar schema to `1.0.0`.

## 1.2.1 — Support Operations and Cross-Product Reliability Patch

- Made public support intake persist the canonical inquiry and linked support case as a recoverable operation, with bounded rollback of only the newly created inquiry when case creation fails.
- Added retry and concurrent-insert recovery for support cases, initial case events, typed relationships, and privacy-safe signals.
- Added stable handoff identifiers, registered source-system validation, strict product identifiers, receipt records, replay idempotency, and success/failure recovery evidence for `sc-product-support-handoff/1.0`.
- Added typed Knowledge Base article, known-issue, Feature Suggestion, product-release, and handoff-receipt relationships without merging or deleting underlying records.
- Added protected support-persistence and handoff-failure reliability events with redacted database-error hashes and request correlation.
- Added Sender Portal allowlist validation so private diagnostics, assignments, internal reasoning, and unreleased information remain excluded.
- Added missing product/version/component and failed-handoff metrics to Support Cases operations.
- Added a nondestructive v1.2.1 patch migration journal; database schema remains 1.2.0.
- Expanded Live Validation to exercise real case creation, governed triage, portal isolation, personal-data rejection, clean signal storage, relationship creation, handoff replay, and complete cleanup.
- Added dedicated cross-product reliability regression coverage while preserving advisory and inquiry behavior.

## 1.2.0 — Support Operations and Product Intelligence Integration

- Added private product-support cases keyed to the canonical inquiry rather than creating a separate sender system.
- Added governed stages from New Support Request through Triage, Investigation, Known Issue, Workaround, Fix Planned, Resolved, and Closed.
- Added product, version, component, environment, error, reproduction, expected/actual behavior, severity, priority, assignment, and sender-safe update fields.
- Added four nondestructive support tables for cases, events, typed relationships, and privacy-safe aggregated product-intelligence signals.
- Added the `sc-product-support-handoff/1.0` contract and capability-protected REST endpoints.
- Added typed links to Knowledge Base articles, known issues, Feature Suggestions, releases, reliability events, and duplicate cases.
- Added a focused `[sc_support_request]` shortcode and `/contact/?engagement=support` route.
- Added a dedicated Support Cases administration workspace and operational metrics.
- Added sender-safe support views to the Secure Sender Portal while excluding private diagnostics and internal reasoning.
- Added reviewable support communication templates without automatic sending or release commitments.
- Added support records to WordPress privacy export, approved redaction, retention execution, readiness, watchdog, cron, uninstall, and Live Validation.
- Preserved all v1.1.1 inquiry-persistence and human-control boundaries.

## 1.1.1 — Inquiry Persistence and Lifecycle Reliability Patch

- Fixed strict-mode inquiry creation by initializing `qualification_score` to `0`, matching the non-null database contract.
- Added protected `inquiry_insert_failed` reliability events with request correlation and redacted database diagnostics.
- Kept public submission failures generic while providing an administrator-facing reliability reference.
- Added the full inquiry-column contract to Platform Readiness, Live Validation, and the reliability watchdog.
- Prevented `sc_ei_db_version` from advancing unless all required tables, inquiry columns, platform columns, and lifecycle columns verify successfully.
- Added a nondestructive v1.1.1 migration journal tied to the runtime persistence contract.
- Added executable inquiry-persistence regression coverage using a strict fake database adapter that rejects null qualification scores.
- Preserved database schema version 1.1.0 and all v1.1.0 lifecycle records and boundaries.

## 1.1.0 — Advisory Operations and Engagement Lifecycle

- Added thirteen governed lifecycle stages from new inquiry through completed, declined, or archived.
- Added typed, human-confirmed transitions with allowed-transition rules, reasons, ownership requirements, optimistic locking, lifecycle events, and audit records.
- Added nondestructive lifecycle fields to existing inquiries and backfilled existing records from legacy statuses.
- Added dedicated lifecycle event, internal-note, and follow-up-task tables.
- Added structured advisory qualification for organizational challenge, desired outcome, systems, constraints, timeline, stakeholders, decision authority, funding, privacy/security, AI Assurance applicability, Teams readiness, score, status, and rationale.
- Added an Advisory Lifecycle administration workspace with ownership, priority, next actions, internal notes, tasks, Teams offers, proposals, engagements, and event history.
- Added sender-safe lifecycle publishing while keeping internal notes, qualification reasoning, assignments, task details, and transition rationale private.
- Added service-specific routes for Evidence Systems Diagnostic, Knowledge Architecture, Technical Storytelling, Responsible AI Workflows, Sustainable AI Assurance, collaboration, workshops, and monthly advisory support.
- Added reviewable lifecycle communication templates and opt-in internal task reminders.
- Added stage, source, service, response-time, qualification, proposal, acceptance, active-engagement, and overdue-work metrics.
- Added lifecycle records to privacy export, approved erasure, retention, diagnostics, readiness, cron, uninstall, and operational-blocker handling.
- Advanced the database version to 1.1.0, platform evidence schema to 1.1.0, Portal schema to 1.4.0, and Engagement schema to 1.1.0.
- Preserved the v1.0.3 production gate and all human-controlled acceptance, proposal, contract, scheduling, and engagement-activation boundaries.

## 1.0.3 — Pilot Findings and Public Launch Hardening

- Added canonical routed Contact-page entry URLs for Advisory, Sustainable AI Assurance, collaboration, media, technical, partnership, workshop, monthly advisory, and general inquiries.
- Added route resolution, attribution, preselection, runtime contract evidence, and public route notices without creating a second submission pipeline.
- Added browser-tab draft recovery with an eight-hour freshness window while excluding files, tokens, nonces, hidden controls, and consent state.
- Added a runtime upload-security probe that accepts a clean text fixture and rejects a disguised executable fixture before launch.
- Separated WordPress mail-transport acceptance from externally confirmed inbox-delivery evidence.
- Added controlled pilot evidence requiring at least five completed inquiries and completion of every launch checklist item.
- Added an operational launch dashboard and production blockers for failed communications, quarantine, portal lockouts, overdue work, and unresolved critical events.
- Added a nondestructive v1.0.3 migration journal and uninstall cleanup for pilot and external-mail evidence.
- Preserved database version 1.0.0 and advanced the platform evidence schema to 1.0.2.

## 1.0.2 — Production Readiness and Live Validation

- Replaced configured-URL assumptions with published local page and shortcode verification.
- Added runtime validation for required cron schedules and their registered callbacks.
- Replaced hard-coded adapter and accessibility passes with initialized adapter-registry and rendered-interface evidence.
- Added guided repair actions for version state, database checks, migrations, protected storage, and scheduled jobs.
- Added an administrator-only live validation suite covering inquiry persistence, status transition, duplicate and request-lock controls, sender portal token verification, protected file integrity and deletion, mail transport acceptance, and cleanup.
- Added recent database and protected-storage backup attestation as a production requirement.
- Made Production require 100% readiness, zero required failures, zero warnings, fresh successful live validation, and fresh backup evidence.
- Added a nondestructive v1.0.2 migration journal entry and automatic schedule repair during upgrade.
- Preserved database version 1.0.0 and advanced the platform evidence schema to 1.0.1.

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
