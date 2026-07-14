# Unified Contact and Engagement Platform

## Purpose

Version 1.0.0 provides one governed operating layer above the complete contact-to-engagement workflow. It unifies navigation, readiness, migration provenance, status, launch governance, and public entry routing without replacing the authoritative repositories built through v0.12.0.

## Product surfaces

### Public

- Unified contact and engagement entry
- General contact
- Engagement inquiry
- Secure sender portal
- Privacy guidance
- Accessibility and process guidance

### Private administration

- Platform Overview
- Inquiries
- Administrative Review
- Human-Controlled Fit Assessment
- Communications
- Privacy and Retention
- Sender Portal administration and recovery
- Teams and Proposals
- Microsoft Graph reliability
- Engagement Handoff
- Analytics
- Reliability
- Workflow Core
- Diagnostics and Settings

## Unified public shortcode

```text
[sc_contact_engagement_platform]
```

The shortcode composes the existing `SC_EI_Public::contact_hub()` implementation. All submission validation, rate limiting, consent provenance, storage, attribution, notification, privacy, and audit behavior therefore remains centralized.

Recommended example:

```text
[sc_contact_engagement_platform
  title="Contact Sustainable Catalyst"
  intro="Choose the path that best matches your request."
  source="contact-page"
  entry_cta="unified-contact-engagement"
  show_form="yes"
  show_portal="yes"
  show_privacy="yes"]
```

## Platform readiness

Required checks cover:

- plugin and database version
- all expected database tables and platform columns
- completed v1.0 migration journal
- protected storage readiness
- HTTPS
- required scheduled jobs
- critical reliability events
- incident public-write state
- configured public entry page
- configured secure sender portal URL
- configured support email
- Workflow Core consistency
- required internal adapter state when configured
- accessibility implementation controls

Warnings can include privacy URL configuration and non-required adapter availability.

## Launch states

```text
setup
pilot
production
maintenance
```

Only a user with `sc_intake_launch_platform` can change launch state. Production is blocked while a required readiness check fails. No background process can change launch state.

## Readiness snapshots

Each snapshot stores:

- launch state
- readiness score
- required failure count
- warning count
- complete readiness payload
- SHA-256 content hash
- source and generating user
- generation time

Snapshots are append-only operational records and are pruned only through the configured retention lifecycle.

## Migration journal

The v1.0 migration key is:

```text
v1_0_0_unified_contact_engagement_platform
```

The migration is non-destructive and idempotent. It records:

- previous version
- target version
- schema-version map
- schema SHA-256
- started and completed times
- success or failure state
- bounded error metadata

It does not rename the plugin directory, reset existing schemas, rewrite inquiry records, or discard historical data.

## Stable compatibility contract

Version 1.0.0 retains:

- plugin folder `sustainable-catalyst-engagement-intake`
- text domain `sustainable-catalyst-engagement-intake`
- database table naming
- public shortcodes
- sender portal links and tokens
- historical audit and privacy records
- Graph credential vault and operation records
- existing Workflow Core handoff contract `sc-engagement-workflow-handoff/1.0`

## Boundaries

The unified platform reports and coordinates workflow. It does not automate business decisions, contracts, signatures, payments, engagement activation, external project provisioning, arbitrary webhooks, or unverified inbound commands.
