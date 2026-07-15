# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.7.0  
**Release:** Billing, Invoicing, and Payment Handoffs

v1.7.0 adds governed billing records to the engagement lifecycle while keeping payment collection outside WordPress. It supports versioned invoices, external HTTPS payment handoffs, sender-safe invoice status, replay-safe provider events, and privacy controls without storing card or bank credentials.

## Major capabilities

- Engagement-linked billing profiles, versioned invoices, line items, and immutable invoice snapshots
- Human-reviewed invoice issue, dispute, overdue, payment, and void states
- HTTPS external payment-provider handoffs with idempotent replay and bounded metadata
- Sender Portal invoice and payment-status projections that exclude internal and sensitive fields
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

- Plugin version: `1.7.0`
- Database version: `1.7.0`
- Platform evidence schema: `1.7.0`
- Portal schema: `1.8.0`
- Workspace schema: `1.0.0`
- Analytics schema: `1.1.0`
- Service Intelligence schema: `1.0.0`
- Billing schema: `1.0.0`
- Migration: `v1_7_0_billing_invoicing_payment_handoffs`

## Installation and validation

1. Back up the WordPress database and protected engagement-document storage.
2. Install the v1.7.0 ZIP over the current plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Repair the database contract and verify the v1.7.0 billing migration if requested.
6. Open **Contact & Engagement → Billing & Payments**.
7. Run Live Validation and confirm invoice issue is versioned, sensitive payment metadata is rejected, payment-handoff replay is idempotent, settlement updates the invoice, and cleanup succeeds.
8. Create one controlled billing profile and invoice, then verify the external HTTPS handoff and Sender Portal projection.
9. Re-record version-bound inbox, backup, validation, and pilot evidence.
10. Require 100%, zero required failures, and zero warnings before Production.

Repository tests and archive verification do not replace live validation on the WordPress host.

See `docs/BILLING-INVOICING-PAYMENT-HANDOFFS.md`, `docs/MIGRATION-v1.7.0.md`, and `docs/RELEASE.md`.
