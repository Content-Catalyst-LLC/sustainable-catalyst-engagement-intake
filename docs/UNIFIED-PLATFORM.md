# Sustainable Catalyst Contact and Engagement Platform

## Purpose

Version 1.1.0 extends the production-hardened public intake system into Advisory Operations and Engagement Lifecycle management. It keeps one authoritative inquiry pipeline while coordinating human review, qualification, ownership, Microsoft Teams meetings, proposals, engagement handoff, follow-up, privacy, reliability, and launch governance.

## Product surfaces

### Public

- Unified Contact and Engagement entry
- Canonical routed service URLs through the Contact page
- Browser-tab draft recovery for unsent text fields
- Secure Sender Portal
- Deliberately published lifecycle stage, summary, and next step
- Privacy, accessibility, and process guidance

### Private administration

- Platform Overview, guided repair center, and Public Launch Operations dashboard
- Advisory Lifecycle workspace
- Inquiries and Administrative Review
- Human-Controlled Fit Assessment
- Communications and templates
- Privacy and Retention
- Sender Portal administration and recovery
- Teams and Proposals
- Microsoft Graph reliability
- Engagement Handoff
- Analytics, Reliability, Workflow Core, Diagnostics, and Settings

## Unified public shortcode

```text
[sc_contact_engagement_platform]
```

The shortcode composes the centralized intake implementation. Submission validation, duplicate prevention, rate limiting, consent provenance, storage, attribution, notifications, privacy, audit, and lifecycle initialization remain in one pipeline.

Existing shortcodes remain supported:

```text
[sc_contact_hub]
[sc_contact_form]
[sc_engagement_inquiry]
[sc_sender_portal]
```

## Canonical routed entries

Use the configured Contact page as the canonical endpoint:

```text
/contact/?engagement=general
/contact/?engagement=advisory
/contact/?engagement=ai-assurance
/contact/?engagement=evidence-systems
/contact/?engagement=knowledge-architecture
/contact/?engagement=technical-storytelling
/contact/?engagement=responsible-ai
/contact/?engagement=collaboration
/contact/?engagement=media
/contact/?engagement=technical
/contact/?engagement=partnership
/contact/?engagement=workshop
/contact/?engagement=monthly-advisory
```

A route controls preselection and attribution only. It does not create separate forms, records, notifications, or write endpoints.

## Advisory lifecycle

The lifecycle layer adds thirteen governed stages, structured qualification, owner and priority, next actions, internal notes, assigned tasks, linked Teams offers, linked proposals, linked engagements, and an append-only event history.

All stage transitions are authorized human actions. The platform enforces allowed transitions, typed confirmation, current-state checks, optimistic row versioning, and audit records. It does not automatically accept, reject, qualify, schedule, publish a proposal, attest a contract, or activate an engagement.

## Sender-safe projection

The Sender Portal can display only:

- a public lifecycle label
- an approved sender-facing summary
- an approved next step

It does not render internal notes, sensitive flags, qualification rationale, scores, owners, tasks, decision authority, funding status, or transition reasons.

## Runtime-backed readiness

The production score uses runtime evidence for:

- installed plugin, database, platform, Portal, Engagement, and lifecycle schema versions
- expected database tables, inquiry columns, and lifecycle support tables
- completed v1.0.0, v1.0.2, v1.0.3, and v1.1.0 migration journals
- protected-storage posture and upload rejection probe
- HTTPS and secure portal transport
- critical reliability events and incident write state
- required scheduled hooks and registered callbacks, including lifecycle reminders
- published Contact page containing a supported intake shortcode
- published Sender Portal page containing `[sc_sender_portal]`
- valid routed-entry contracts
- support email, Workflow Core consistency, adapter registry, privacy page, and rendered accessibility evidence
- recent successful live validation
- recent external inbox delivery evidence
- completed controlled pilot evidence
- absence of public-launch and lifecycle operational blockers
- recent database and protected-storage backup attestation

Configured URLs alone do not pass page checks.

## Live validation

The administrator-only suite validates temporary inquiry creation, a real audited lifecycle transition, sender-safe lifecycle projection, duplicate fingerprinting, request locks, Sender Portal tokens, protected file storage and deletion, route contracts, cron callbacks, accessibility markers, WordPress mail acceptance, safe upload acceptance, disguised-executable rejection, lifecycle migration evidence, and cleanup.

A passing mail-transport result does not prove inbox delivery. External delivery is recorded separately with recipient, reference, user, plugin version, and UTC timestamp.

## Production gate

Launch states remain `setup`, `pilot`, `production`, and `maintenance`.

Production requires:

- readiness score exactly 100%
- zero required failures
- zero warnings
- successful live validation for v1.1.0 within the evidence window
- externally confirmed inbox evidence within the evidence window
- completed pilot evidence within the evidence window
- no public-launch or lifecycle operational blockers
- database and protected-storage backup evidence within the evidence window
- authorized typed human launch action

No background process can change launch state.

## Migration journal

```text
v1_0_0_unified_contact_engagement_platform
v1_0_2_production_readiness_live_validation
v1_0_3_pilot_findings_public_launch_hardening
v1_1_0_advisory_operations_engagement_lifecycle
```

All migrations are nondestructive and idempotent. v1.1.0 advances the database to 1.1.0 and adds lifecycle fields plus event, note, and task tables while preserving existing records and legacy status compatibility.

## Boundaries

The platform reports, validates, and coordinates workflow. It does not automatically accept or reject inquiries, decide fit, publish proposals, create or attest contracts, schedule meetings, activate engagements, provision external projects, send arbitrary webhooks, collect payment, or execute unverified inbound commands.
