# Release Procedure — v1.0.3

## Release identity

- Product: Sustainable Catalyst Contact and Engagement Platform
- Plugin slug: `sustainable-catalyst-engagement-intake`
- Version: `1.0.3`
- Database version: `1.0.0`
- Platform evidence schema: `1.0.2`
- Base migration: `v1_0_0_unified_contact_engagement_platform`
- Readiness migration: `v1_0_2_production_readiness_live_validation`
- Launch-hardening migration: `v1_0_3_pilot_findings_public_launch_hardening`

All three migrations are nondestructive and idempotent. v1.0.3 does not alter the database schema.

## Required release checks

- PHP syntax across plugin and test files
- JavaScript syntax for public and admin bundles
- all executable repository test suites
- private/protected constant visibility scan
- version, database, schema, and migration contract checks
- route, draft-recovery, upload-probe, pilot, inbox, and operational-gate checks
- secret scan
- installable ZIP root and exclusions
- repository ZIP root and release manifest
- ZIP CRC verification
- fresh extraction and complete retest

## Staging acceptance

- upgrade from v1.0.2 without record loss
- v1.0.2 and v1.0.3 migration journals completed once
- canonical routed URLs resolve through the published Contact page
- route selection prepopulates the intended inquiry path without creating another endpoint
- unsent text drafts restore in the same browser tab; files and consent state do not restore
- live validation accepts the safe text fixture and rejects the disguised executable fixture
- public Contact, Sender Portal, Privacy, cron, adapter, accessibility, and storage checks pass
- WordPress accepts the validation message
- the validation message is confirmed in an external inbox and the evidence is recorded
- at least five controlled inquiries complete successfully
- every pilot checklist item is attested
- no failed communication, quarantine, portal-lockout, overdue-work, or critical-event blocker remains
- database and protected-storage backups are current and attested
- production remains unavailable below 100%, with any failure, warning, stale evidence, or operational blocker

## Production promotion

1. Complete staging acceptance.
2. Back up the production database and protected storage.
3. Install v1.0.3 and clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Complete guided repairs and verify the v1.0.3 migration journal.
5. Test each routed public entry from the Contact, Advisory, and Sustainable AI Assurance surfaces.
6. Run Live Validation with a monitored recipient.
7. Confirm external inbox receipt and record the delivery reference.
8. Complete at least five controlled inquiries and every pilot checklist item.
9. Resolve every operational blocker.
10. Record current backup evidence.
11. Require 100%, zero failures, and zero warnings.
12. Record Production only through the typed human action after operational review.

A repository test pass does not replace this staging and production-host validation.
