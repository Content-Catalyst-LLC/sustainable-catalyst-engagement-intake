# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.6.0  
**Release:** Engagement Analytics and Service Intelligence

v1.6.0 adds a privacy-safe service-intelligence layer over the governed inquiry, support, scheduling, proposal, engagement, and client-workspace system. Authorized staff can inspect aggregate demand, cycle time, resolution, collaboration, proposal, and delivery patterns without ranking senders or processing private message and document contents.

## Major capabilities

- Aggregate inquiry, support, scheduling, proposal, engagement, workspace, milestone, deliverable, and signal metrics
- Minimum-cohort suppression for grouped and rate metrics
- Product, component, service, weekly, funnel, timing, workload, and completion views
- SHA-256-hashed aggregate snapshots with version-bound freshness evidence
- Human-reviewed service-intelligence findings with typed confirmation and optimistic locking
- Direct personal-data key and value rejection for finding evidence
- Compensating rollback when a finding event cannot be written
- Retention limited to closed or dismissed aggregate findings
- Production Readiness, diagnostics, privacy inventory, and Live Validation coverage

## Existing platform layers retained

- Public contact and product-support intake
- Advisory lifecycle and private support cases
- Microsoft Teams and calendar coordination
- Governed proposals, Statements of Work, approvals, and engagement conversion
- Secure Sender Portal, communications, protected uploads, privacy, retention, reliability, and launch governance

## Public entry points

```text
[sc_contact_engagement_platform]
[sc_support_request]
[sc_sender_portal]
```

Client workspaces are not a separate public registration system. A workspace is created only from an existing governed engagement and becomes visible only after an authorized administrator activates and publishes it.

## Release identity

- Plugin version: `1.6.0`
- Database version: `1.6.0`
- Platform evidence schema: `1.6.0`
- Portal schema: `1.7.0`
- Workspace schema: `1.0.0`
- Analytics schema: `1.1.0`
- Service Intelligence schema: `1.0.0`
- Migration: `v1_6_0_engagement_analytics_service_intelligence`

## Installation and validation

1. Back up the WordPress database and protected engagement-document storage.
2. Install the v1.6.0 ZIP over the current plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Repair the database contract and verify the v1.6.0 service-intelligence migration if requested.
6. Open **Contact & Engagement → Analytics & Intelligence**.
7. Run Live Validation and confirm the aggregate privacy rejection, human-reviewed finding, evidence hash, snapshot, and cleanup checks pass.
8. Save one controlled aggregate snapshot and review one controlled finding.
9. Re-record version-bound inbox, backup, validation, and pilot evidence.
10. Require 100%, zero required failures, and zero warnings before Production.

Repository tests and archive verification do not replace live validation on the WordPress host.

See `docs/ENGAGEMENT-ANALYTICS-SERVICE-INTELLIGENCE.md`, `docs/MIGRATION-v1.6.0.md`, and `docs/RELEASE.md`.
