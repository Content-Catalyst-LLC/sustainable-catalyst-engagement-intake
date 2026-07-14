# Sustainable Catalyst Contact and Engagement Platform

## Purpose

Version 1.0.3 hardens the pilot-to-public-launch layer above the governed contact-to-engagement workflow. It unifies runtime readiness, routed public entry, live validation, external mail evidence, pilot evidence, operational blockers, backup evidence, and launch governance without replacing authoritative repositories or human decisions.

## Product surfaces

### Public

- Unified Contact and Engagement entry
- Canonical routed entry URLs for Advisory, Sustainable AI Assurance, collaboration, media, technical, partnership, workshop, monthly advisory, and general inquiries
- Browser-tab draft recovery for unsent text fields
- Secure Sender Portal
- Privacy, accessibility, and process guidance

### Private administration

- Platform Overview, guided repair center, and Public Launch Operations dashboard
- Live Validation, external inbox evidence, pilot evidence, and backup evidence
- Inquiries and Administrative Review
- Human-Controlled Fit Assessment
- Communications
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

The shortcode composes the centralized intake implementation. Submission validation, duplicate prevention, rate limiting, consent provenance, storage, attribution, notifications, privacy, and audit behavior remain in one pipeline.

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
/contact/?engagement=collaboration
/contact/?engagement=media
/contact/?engagement=technical
/contact/?engagement=partnership
/contact/?engagement=workshop
/contact/?engagement=monthly-advisory
```

The route controls preselection and attribution only. It does not create separate forms, records, or write endpoints.

## Draft recovery

The public form keeps an unsent text-field draft in `sessionStorage` for up to eight hours. It intentionally excludes:

- file inputs
- hidden fields
- nonces and tokens
- passwords
- consent and acknowledgment state

Draft evidence remains limited to the current browser tab and is cleared after a successful submission.

## Runtime-backed readiness

The production score uses actual evidence for:

- installed plugin, database, and platform evidence-schema versions
- expected database tables and platform columns
- completed v1.0.0, v1.0.2, and v1.0.3 migration journals
- protected-storage posture and upload rejection probe
- HTTPS and secure portal transport
- critical reliability events and incident write state
- required scheduled hooks and registered callbacks
- published Contact page containing a supported intake shortcode
- published Sender Portal page containing `[sc_sender_portal]`
- valid routed-entry contracts
- support email, Workflow Core consistency, adapter registry, privacy page, and rendered accessibility evidence
- recent successful live validation
- recent external inbox delivery evidence
- completed controlled pilot evidence
- absence of operational blockers
- recent database and protected-storage backup attestation

Configured URLs alone do not pass page checks.

## Live validation

The administrator-only suite validates temporary inquiry creation and transition, duplicate fingerprinting, request locks, Sender Portal tokens, protected file storage and deletion, route contracts, cron callbacks, accessibility markers, WordPress mail acceptance, safe upload acceptance, disguised-executable rejection, and cleanup.

A passing mail-transport result does not prove inbox delivery. External delivery is recorded separately with recipient, reference, user, plugin version, and UTC timestamp.

## Pilot evidence

Production requires at least five controlled inquiries and completion of every checklist item covering general intake, Advisory, Sustainable AI Assurance, private upload, administrator notification, sender acknowledgment, and Sender Portal isolation/access.

## Operational blockers

The Public Launch Operations dashboard blocks Production while unresolved launch-risk queues exist, including failed communications, quarantine items, active portal lockouts, overdue work, or unresolved critical reliability events.

## Production gate

Launch states remain `setup`, `pilot`, `production`, and `maintenance`.

Production requires:

- readiness score exactly 100%
- zero required failures
- zero warnings
- successful live validation for v1.0.3 within the evidence window
- externally confirmed inbox evidence within the evidence window
- completed pilot evidence within the evidence window
- no operational blockers
- database and protected-storage backup evidence within the evidence window
- authorized typed human launch action

No background process can change launch state.

## Migration journal

```text
v1_0_0_unified_contact_engagement_platform
v1_0_2_production_readiness_live_validation
v1_0_3_pilot_findings_public_launch_hardening
```

All migrations are nondestructive and idempotent. v1.0.3 preserves database version 1.0.0 and advances the platform evidence schema to 1.0.2.

## Boundaries

The platform reports, validates, and coordinates workflow. It does not automatically accept or reject inquiries, decide fit, publish proposals, create or attest contracts, schedule meetings, activate engagements, provision external projects, send arbitrary webhooks, collect payment, or execute unverified inbound commands.
