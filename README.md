# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.4.0  
**Release:** Proposals, Statements of Work, and Engagement Approvals

v1.4.0 adds a governed proposal-to-engagement layer without turning the Sender Portal into an electronic-signature, payment, or autonomous contracting system. Proposal versions, Statements of Work, sender decisions, external contract evidence, change requests, and engagement conversion remain deliberate and auditable.

## Major capabilities

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

The v1.4.0 migration is nondestructive:

- plugin version: `1.4.0`
- database version: `1.4.0`
- platform evidence schema: `1.4.0`
- proposal-governance schema: `1.0.0`
- migration journal: `v1_4_0_proposals_statements_of_work_engagement_approvals`

It adds proposal approval, Statement of Work, SOW version, and change-request records while preserving all existing inquiries, support cases, meetings, proposals, engagements, documents, communications, and portal access.

## Upgrade

1. Back up the WordPress database and protected engagement storage.
2. Install the v1.4.0 ZIP over the existing plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Run the v1.4.0 proposal-governance migration repair if shown.
6. Open **Contact & Engagement → Proposal Governance**.
7. Run Live Validation.
8. Test a proposal revision, SOW approval, sender decision, external-contract record, engagement conversion, and change request.
9. Re-record version-bound backup, inbox, validation, and pilot evidence.
10. Promote only at 100%, zero required failures, and zero warnings.

See `docs/PROPOSALS-SOW-ENGAGEMENT-APPROVALS.md`, `docs/MIGRATION-v1.4.0.md`, and `docs/RELEASE.md`.
