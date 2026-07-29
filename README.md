# Sustainable Catalyst Contact and Engagement Platform

**Version:** 2.0.2  
**Release:** Interrupted Migration and Database Recovery Patch

v2.0.2 preserves the integrated v2.0 platform and repairs the Bluehost MySQL reserved-identifier failure that prevented four required tables from being created.

The patch specifically recovers `proposal_approvals` and `platform_handoffs`, prevents repeated missing-table metadata queries, and fails closed if database write access remains unavailable.

v2.0.0 unifies the platform’s mature contact, advisory, support, scheduling, proposal, secure workspace, billing, analytics, privacy, and Sender Portal capabilities behind a canonical engagement dossier and a stable v2 integration contract.

## v2.0.0 capabilities

- Canonical engagement dossier for each non-erased inquiry
- Route, phase, health, owner, sender-safe summary, next step, and integrity hash
- Typed relationships to support cases, meetings, proposals, Statements of Work, engagements, client workspaces, invoices, protected files, communications, and lifecycle tasks
- Unified activity timeline assembled from governed subsystem events
- Integrated Engagement Command Center with bounded backfill and repair
- Privacy-safe and idempotent `sc-engagement-platform-handoff/2.0` receipts
- Authorized REST endpoints for platform status, dossier lists, dossier detail, and typed handoffs
- WordPress privacy export, approved redaction, retention, diagnostics, and Live Validation coverage
- Compatibility with the existing v1 REST resources and public shortcodes

## Governance boundaries

The dossier is an index and coordination record. It does not replace or overwrite subsystem records. The platform does not automatically merge unrelated cases, make support or advisory decisions, schedule meetings, approve proposals, activate engagements, collect payments, or send communications.

## Existing platform layers retained

- Public contact and focused product-support intake
- Advisory qualification and lifecycle operations
- Private support cases and product-intelligence handoffs
- Microsoft Teams and calendar coordination
- Proposals, Statements of Work, approvals, and change requests
- Secure client workspaces and protected document exchange
- Billing, invoices, and external payment handoffs
- Aggregate analytics and human-reviewed service intelligence
- Sender Portal, privacy, retention, reliability, and Production Readiness

## Public entry points

```text
[sc_contact_engagement_platform]
[sc_support_request]
[sc_sender_portal]
```

The Command Center and client workspaces are not public registration systems.

## Release identity

- Plugin version: `2.0.0`
- Database version: `2.0.0`
- Platform evidence schema: `2.0.0`
- Unified platform schema: `2.0.0`
- Portal schema: `1.8.0`
- Support schema: `1.0.1`
- Calendar schema: `1.0.1`
- Proposal Governance schema: `1.0.1`
- Workspace schema: `1.0.0`
- Analytics schema: `1.1.0`
- Service Intelligence schema: `1.0.0`
- Billing schema: `1.0.0`
- Migration: `v2_0_0_integrated_advisory_support_institutional_platform`

## Installation and validation

1. Back up the WordPress database and protected engagement-document storage.
2. Install the v2.0.0 ZIP over the current plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Repair the database contract and verify the v2.0.0 migration if requested.
6. Open **Contact & Engagement → Command Center** and run the bounded dossier backfill.
7. Run Live Validation and confirm dossier creation, relationship indexing, timeline aggregation, private handoff rejection, safe replay, and cleanup.
8. Re-record version-bound inbox, backup, validation, and pilot evidence.
9. Require 100%, zero required failures, and zero warnings before Production.

Repository validation and archive integrity checks do not replace live validation on the WordPress host.

See `docs/INTEGRATED-ENGAGEMENT-PLATFORM.md`, `docs/MIGRATION-v2.0.0.md`, and `docs/RELEASE.md`.
