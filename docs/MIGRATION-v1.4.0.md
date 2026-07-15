# Migration to v1.4.0

The migration is nondestructive and idempotent. It records `v1_4_0_proposals_statements_of_work_engagement_approvals` and advances the database version to `1.4.0`.

## New tables

- `sc_ei_proposal_approvals`
- `sc_ei_statements_of_work`
- `sc_ei_statement_of_work_versions`
- `sc_ei_change_requests`

Existing inquiries, proposals, meetings, support cases, engagements, files, portal records, and communications are preserved. The migration does not convert old proposal responses into signatures or contracts.

## Validation

After upgrading, run database repair if requested, verify the migration journal, run Live Validation, inspect Sender Portal isolation, and complete a controlled proposal/SOW/change-request workflow before Production.
