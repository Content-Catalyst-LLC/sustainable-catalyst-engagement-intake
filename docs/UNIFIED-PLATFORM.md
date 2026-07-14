# Sustainable Catalyst Contact and Engagement Platform

## Purpose

Version 1.0.2 provides a governed operating and launch layer above the complete contact-to-engagement workflow. It unifies navigation, readiness, migration provenance, live validation, backup evidence, status, launch governance, and public entry routing without replacing authoritative business repositories or human decisions.

## Product surfaces

### Public

- Unified contact and engagement entry
- General contact and Advisory inquiry routes
- Secure sender portal
- Privacy guidance
- Accessibility and process guidance

### Private administration

- Platform Overview and guided repair center
- Live Validation and backup evidence
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

The shortcode composes the existing centralized public intake implementation. Submission validation, duplicate prevention, rate limiting, consent provenance, storage, attribution, notification, privacy, and audit behavior remain in one pipeline.

Existing shortcodes remain supported:

```text
[sc_contact_hub]
[sc_contact_form]
[sc_engagement_inquiry]
[sc_sender_portal]
```

## Runtime-backed readiness

The production score uses actual runtime evidence for:

- installed plugin and database versions
- expected database tables and platform columns
- completed v1.0 and v1.0.2 migration journals
- protected-storage posture
- HTTPS and secure portal transport
- critical reliability events and incident write state
- required scheduled hooks and registered callbacks
- published local Contact/Engagement page containing a supported intake shortcode
- published local Sender Portal page containing `[sc_sender_portal]`
- valid support email
- Workflow Core consistency
- initialized internal-adapter registry
- published privacy guidance page
- rendered accessibility contract
- recent successful live validation
- recent database and protected-storage backup attestation

Configured URLs alone do not pass page checks. They must resolve to published local WordPress content with the required shortcode.

## Guided repair center

Authorized administrators can run bounded repairs for:

- stored plugin and platform schema version state
- database contract
- base and patch migration journals
- protected storage
- scheduled jobs

Configuration, privacy, HTTPS, reliability, Workflow Core, and accessibility findings remain manual review items.

## Live validation

The administrator-only suite uses temporary, clearly marked artifacts and validates:

- version and database contract
- public page and shortcode contracts
- cron schedules and callbacks
- rendered accessibility markers
- duplicate fingerprint and concurrent request lock
- protected-storage probe and exposure posture
- inquiry creation and status transition
- sender portal invitation issue and token verification
- private file store, SHA-256 integrity verification, and deletion
- WordPress mail transport acceptance
- cleanup of temporary records, tokens, files, audit rows, and scheduled inquiry work

A passing mail check means WordPress accepted the message. It does not prove inbox delivery; the administrator must confirm receipt manually.

## Backup evidence

Production requires a recent human attestation that both the WordPress database and protected document storage have current recoverable backups. The attestation records references, user, plugin version, and UTC time in the audit trail. It does not claim to inspect a host backup system that WordPress cannot access.

## Production gate

Launch states remain:

```text
setup
pilot
production
maintenance
```

Production requires all of the following:

- readiness score exactly 100%
- zero required failures
- zero warnings
- successful live validation for the installed plugin version within seven days
- database and protected-storage backup attestation within seven days
- authorized typed human launch action

No background process can change launch state.

## Migration journal

Base migration key:

```text
v1_0_0_unified_contact_engagement_platform
```

v1.0.2 patch migration key:

```text
v1_0_2_production_readiness_live_validation
```

Both migrations are non-destructive and idempotent. v1.0.2 preserves database version 1.0.0 and advances only the platform evidence schema to 1.0.1.

## Boundaries

The platform reports, validates, and coordinates workflow. It does not automatically accept or reject inquiries, decide fit, publish proposals, create or attest contracts, schedule meetings, activate engagements, provision external projects, send arbitrary webhooks, collect payment, or execute unverified inbound commands.
