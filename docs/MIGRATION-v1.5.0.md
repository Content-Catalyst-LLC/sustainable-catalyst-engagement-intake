# Migration to v1.5.0

The migration is nondestructive and idempotent. It records:

```text
v1_5_0_secure_client_workspace_collaboration
```

## Version changes

- Plugin: `1.5.0`
- Database: `1.5.0`
- Platform evidence: `1.5.0`
- Portal schema: `1.7.0`
- Workspace schema: `1.0.0`

## New tables

- `sc_ei_client_workspaces`
- `sc_ei_workspace_members`
- `sc_ei_workspace_milestones`
- `sc_ei_workspace_deliverables`
- `sc_ei_workspace_messages`
- `sc_ei_workspace_documents`
- `sc_ei_workspace_events`

The actual WordPress table prefix replaces `sc_ei_` as appropriate.

Existing inquiries, support cases, lifecycle records, meetings, proposals, Statements of Work, approvals, engagements, communications, portal sessions, and protected files are preserved.

Database version advancement requires the full workspace table and column contract. A failed or partial migration remains visible in Production Readiness and can be retried through the bounded repair action.
