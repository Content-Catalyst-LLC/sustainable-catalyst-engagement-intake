# Secure Client Workspace and Collaboration

v1.5.0 adds a controlled collaboration layer for engagements that have already passed inquiry, meeting, proposal, Statement of Work, and conversion governance.

## Boundary

A client workspace is not a public account system and does not replace the Sender Portal. It is an engagement-scoped projection delivered through the existing authorized Sender Portal session.

Staff can retain private operational records while deliberately publishing only:

- workspace title, status, summary, and next step;
- selected milestones;
- published deliverables and sender decision state;
- selected protected-document metadata;
- selected collaboration updates.

Assignments, member email hashes, internal events, private notes, audit context, and unpublished work remain outside the sender projection.

## Records

The release adds:

- `client_workspaces`
- `workspace_members`
- `workspace_milestones`
- `workspace_deliverables`
- `workspace_messages`
- `workspace_documents`
- `workspace_events`

All records retain the canonical inquiry identifier. Workspaces also retain the governed engagement identifier.

## Collaboration controls

- Workspace transitions require typed human confirmation.
- Workspace and deliverable updates use row-version or state checks where applicable.
- Sender members are stored with a one-way email hash rather than a raw email address.
- Linked documents must belong to the same inquiry and remain in protected storage.
- Sender Portal workspace access uses the public workspace identifier and the existing authorized inquiry session.
- Deliverables remain private until explicitly published.
- Sender decisions are limited to accepted or changes requested.
- No workspace action automatically signs a contract, approves payment, changes proposal evidence, or expands the approved engagement scope.

## Operations

Production Readiness blocks on incomplete workspace schema, missing migration evidence, sender-projection contract failure, orphaned workspaces, and overdue milestones. Live Validation creates and removes a temporary workspace workflow and verifies sender-safe projection and cleanup.
