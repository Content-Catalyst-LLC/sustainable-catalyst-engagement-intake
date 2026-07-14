# Release Procedure — v1.2.0

## Release identity

- Product: Sustainable Catalyst Contact and Engagement Platform
- Release: Support Operations and Product Intelligence Integration
- Plugin slug: `sustainable-catalyst-engagement-intake`
- Plugin version: `1.2.0`
- Database version: `1.2.0`
- Support schema: `1.0.0`
- Lifecycle schema: `1.0.0`
- Platform evidence schema: `1.2.0`
- Support migration: `v1_2_0_support_operations_product_intelligence`
- Handoff contract: `sc-product-support-handoff/1.0`

The v1.2.0 migration is nondestructive and idempotent.

## Required repository checks

- PHP syntax across plugin and tests
- JavaScript syntax for public and admin bundles
- every executable repository test suite
- four support-table and support-column contracts
- governed support-stage and typed-confirmation contracts
- privacy-safe product-intelligence rejection and aggregation contracts
- REST capability and Sender Portal data-boundary contracts
- privacy export, approved redaction, retention, readiness, watchdog, cron, and uninstall contracts
- Live Validation support-case creation, transition, signal rejection/acceptance, and cleanup
- common-secret scan
- installable and repository ZIP roots
- release manifest, ZIP CRC, and fresh-extraction verification

## Staging acceptance

- upgrade from v1.1.1 without record loss
- database version and v1.2.0 migration journal complete
- `[sc_support_request]` creates one canonical inquiry and one support case
- product, version, component, environment, error, and reproduction context persist correctly
- invalid and stale support transitions are rejected
- Sender Portal exposes only approved support context
- typed Knowledge Base, known issue, Feature Suggestion, release, event, and duplicate-case relationships persist and audit
- handoff payloads containing identity, messages, files, or credentials are rejected
- nonpersonal product-intelligence signals aggregate without sender data
- support communication templates remain drafts until deliberate human sending
- privacy export and approved redaction include support records
- high-priority unresolved support cases block Production
- all v1.1.1 inquiry, advisory, portal, upload, notification, backup, and launch controls remain effective

## Production promotion

1. Complete staging acceptance.
2. Back up the database and protected storage.
3. Install v1.2.0 and clear all caches.
4. Complete database and support migration repairs.
5. Inspect the Support Cases workspace.
6. Run Live Validation with a monitored recipient.
7. Confirm external inbox delivery.
8. Complete controlled general, advisory, support, upload, and Sender Portal tests.
9. Resolve all high-priority support and public-launch blockers.
10. Record current database and protected-storage backup evidence.
11. Require 100%, zero failures, and zero warnings.
12. Promote only through the typed human Production action.

Repository validation does not replace live WordPress-host validation.
