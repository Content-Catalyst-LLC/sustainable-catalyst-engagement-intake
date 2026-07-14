# Release Procedure — v1.0.1

## Release identity

- Product: Sustainable Catalyst Contact and Engagement Platform
- Plugin slug: `sustainable-catalyst-engagement-intake`
- Version: `1.0.1`
- Database version: `1.0.0`
- Platform schema: `1.0.0`

## Required release checks

- PHP syntax across all plugin files
- JavaScript syntax for public and admin bundles
- all executable release suites
- exact schema mapping
- secret scan
- installable ZIP root and exclusions
- repository ZIP root and manifest
- ZIP CRC
- fresh extraction and complete retest
- push script syntax
- temporary clean Git clone, replace, commit, and push

## Staging acceptance

- upgrade from v0.12.0 without record loss
- migration journal completed once
- platform readiness snapshot hash verifies
- existing shortcodes render and submit correctly
- unified shortcode routes correctly
- sender portal tokens and sessions remain valid
- review, fit, Teams, proposal, engagement, privacy, analytics, reliability, and Workflow Core paths work
- incident pause preserves authenticated read-only access
- scheduled jobs run
- security headers do not conflict with host or CDN
- keyboard, focus, reduced-motion, forced-colors, and screen-reader checks complete

## Production promotion

1. Complete staging acceptance.
2. Back up production database and protected storage.
3. Install v1.0.1.
4. Clear all caches.
5. Verify migration and readiness.
6. Run one platform snapshot.
7. Record Pilot state.
8. Complete live smoke tests.
9. Record Production state through the typed human action only after required checks pass.
