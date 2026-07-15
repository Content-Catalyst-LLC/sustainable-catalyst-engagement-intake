# Migration to v1.2.1

## Purpose

This patch hardens support persistence and cross-product handoffs without changing the database schema.

## What changes

- records a v1.2.1 reliability migration journal
- verifies the existing v1.2.0 support database contract
- makes newly submitted public support inquiries recoverable when linked case creation fails
- adds retry and replay handling for cases, events, links, and privacy-safe signals
- validates registered product and source-system identifiers
- records protected handoff failure and recovery evidence

## What does not change

- database version remains `1.2.0`
- existing inquiry, support, lifecycle, portal, document, meeting, proposal, communication, and engagement records remain intact
- Feature Suggestions remains the public Knowledge Base and product-feedback layer
- Contact and Engagement remains the private support-case layer

## Upgrade sequence

1. Back up the WordPress database and protected document storage.
2. Replace the plugin with v1.2.1.
3. Clear object, page, CDN, browser, and PHP opcode caches.
4. Open Platform Overview and run the v1.2.1 repair if shown.
5. Run Live Validation.
6. Submit a controlled support request and verify exactly one inquiry and one linked support case are created.
7. Verify the Sender Portal exposes only approved support fields.
8. Re-record version-bound inbox, backup, and pilot evidence.
