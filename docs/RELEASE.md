# Release Procedure — v1.0.2

## Release identity

- Product: Sustainable Catalyst Contact and Engagement Platform
- Plugin slug: `sustainable-catalyst-engagement-intake`
- Version: `1.0.2`
- Database version: `1.0.0`
- Platform evidence schema: `1.0.1`
- Patch migration: `v1_0_2_production_readiness_live_validation`

## Required release checks

- PHP syntax across plugin, tests, and release scripts
- JavaScript syntax for public and admin bundles
- all executable release suites
- private/protected constant visibility scan
- version and schema contract checks
- secret scan
- installable ZIP root and exclusions
- repository ZIP root and release manifest
- ZIP CRC verification
- fresh extraction and complete retest

## Staging acceptance

- upgrade from v1.0.1 without record loss
- v1.0.2 migration journal completed once
- guided repair actions are capability and nonce protected
- configured public URLs resolve to published local pages containing the required shortcode
- every required cron hook has both a next run and a registered callback
- rendered accessibility evidence passes
- live validation creates and removes temporary records and files
- duplicate fingerprint and request lock controls pass
- sender portal temporary token verifies and is removed
- WordPress accepts the validation message and delivery is manually confirmed
- database and protected-storage backups are completed and attested
- production remains unavailable below 100%, with any failure, warning, stale validation, or stale backup evidence

## Production promotion

1. Complete staging acceptance.
2. Back up production database and protected storage.
3. Install v1.0.2 and clear caches.
4. Complete all guided repairs.
5. Run live validation with a monitored recipient.
6. Confirm the test email reached the recipient.
7. Record the backup attestation.
8. Require 100%, zero failures, and zero warnings.
9. Record Pilot state and complete controlled public submissions.
10. Record Production only through the typed human action after operational review.
