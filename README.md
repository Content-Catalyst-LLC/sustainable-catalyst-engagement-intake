# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.4.1  
**Release:** Proposal Versioning, Approval, and Engagement Conversion Reliability Patch

v1.4.1 hardens the governed proposal-to-engagement path introduced in v1.4.0. Sender decisions now commit with immutable receipts or roll back, Statement of Work approvals remain bound to the current proposal version, proposal and SOW revisions use bounded retry, and engagement conversion can repair partial receipt or status evidence without creating duplicate engagements.

## Major capabilities

- synchronous proposal-response and immutable-receipt commits with compensating rollback
- replay-safe sender proposal and Statement of Work approvals
- current-version SOW enforcement and stale SOW reconciliation
- bounded proposal and SOW version creation retries
- recoverable, idempotent engagement conversion and conversion-receipt repair
- approval-integrity and conversion-consistency metrics in Production Readiness
- governed proposal lifecycle with current-version enforcement
- immutable sender action receipts with integrity hashes
- versioned Statements of Work with internal approval and sender review
- typed change requests with scope, schedule, and fee impact
- sender-safe proposal and SOW projection
- idempotent conversion from a contracted proposal and sender-approved SOW into an engagement
- reviewable proposal and engagement communication templates
- privacy export, approved redaction, retention, REST export, readiness, and Live Validation coverage

## Human-control boundary

The platform does not create contracts, electronic signatures, invoices, payments, proposal acceptances, or engagement activations automatically. A Sender Portal response is an auditable workflow record. Any legally operative contract or signature remains external and must be recorded separately before conversion.

## Migration

The v1.4.1 patch migration is nondestructive:

- plugin version: `1.4.1`
- database version: `1.4.0`
- platform evidence schema: `1.4.1`
- proposal-governance schema: `1.0.1`
- base migration journal: `v1_4_0_proposals_statements_of_work_engagement_approvals`
- patch migration journal: `v1_4_1_proposal_versioning_approval_reliability`

It adds no tables or columns. It records reliability evidence and preserves all existing inquiries, support cases, meetings, proposals, SOWs, approvals, engagements, documents, communications, and portal access.

## Upgrade

1. Back up the WordPress database and protected engagement storage.
2. Install the v1.4.1 ZIP over the existing plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Run the v1.4.1 proposal-reliability patch repair if shown.
6. Open **Contact & Engagement → Proposal Governance**.
7. Run Live Validation.
8. Test a proposal revision, SOW approval, sender decision, external-contract record, engagement conversion, and change request.
9. Re-record version-bound backup, inbox, validation, and pilot evidence.
10. Promote only at 100%, zero required failures, and zero warnings.

See `docs/PROPOSAL-VERSIONING-APPROVAL-RELIABILITY.md`, `docs/MIGRATION-v1.4.1.md`, and `docs/RELEASE.md`.
