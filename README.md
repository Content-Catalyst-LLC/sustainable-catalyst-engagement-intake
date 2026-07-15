# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.5.0  
**Release:** Secure Client Workspace and Collaboration

v1.5.0 extends the governed contact-to-engagement platform with engagement-linked client workspaces. Authorized staff can publish selected milestones, deliverables, collaboration updates, and protected-document metadata to the existing Sender Portal while keeping assignments, internal reasoning, audit context, and private operational details outside the sender boundary.

## Major capabilities

- Engagement-linked secure client workspaces with explicit sender and staff membership
- Governed Draft, Active, Paused, Completed, and Archived workspace stages
- Sender-safe milestones, deliverables, updates, next steps, and document metadata
- Deliverable publication, acceptance, and change-request records
- Workspace-specific Sender Portal replies
- Protected document relationships limited to the same canonical inquiry
- Workspace event history, optimistic locking, privacy export, approved redaction, and retention support
- Production Readiness and Live Validation coverage for schema, migration, sender isolation, collaboration, and cleanup

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

- Plugin version: `1.5.0`
- Database version: `1.5.0`
- Platform evidence schema: `1.5.0`
- Portal schema: `1.7.0`
- Workspace schema: `1.0.0`
- Migration: `v1_5_0_secure_client_workspace_collaboration`

## Installation and validation

1. Back up the WordPress database and protected engagement-document storage.
2. Install the v1.5.0 ZIP over the current plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Repair the database contract and verify the v1.5.0 workspace migration if requested.
6. Open **Contact & Engagement → Client Workspace**.
7. Run Live Validation and confirm the temporary workspace, members, milestones, deliverables, messages, sender projection, and cleanup checks pass.
8. Test one controlled engagement workspace through the Sender Portal.
9. Re-record version-bound inbox, backup, validation, and pilot evidence.
10. Require 100%, zero required failures, and zero warnings before Production.

Repository tests and archive verification do not replace live validation on the WordPress host.

See `docs/SECURE-CLIENT-WORKSPACE-COLLABORATION.md`, `docs/MIGRATION-v1.5.0.md`, and `docs/RELEASE.md`.
