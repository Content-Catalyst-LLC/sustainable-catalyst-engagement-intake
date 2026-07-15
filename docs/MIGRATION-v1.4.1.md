# Migration — v1.4.1

This is a nondestructive reliability patch.

- Database version remains `1.4.0`.
- Platform evidence schema advances to `1.4.1`.
- Proposal Governance schema advances to `1.0.1`.
- Patch journal: `v1_4_1_proposal_versioning_approval_reliability`.
- No tables or columns are added, removed, or renamed.

The upgrade records patch evidence, verifies proposal-governance tables, preserves every proposal version, SOW version, approval receipt, change request, and engagement, and exposes a bounded readiness repair when patch evidence is missing.
