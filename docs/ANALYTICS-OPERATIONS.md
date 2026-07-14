# Inquiry Analytics and Operational Intelligence

v0.10.0 adds an aggregate operational dashboard under **Engagement Intake → Analytics**.

## Questions answered

- How many inquiries entered the system in the selected period?
- Which source pages, inquiry types, and service interests generate demand?
- How many inquiries progressed into review, decisions, meetings, proposals, contracts, and active engagements?
- How long do review, decision, and proposal-to-contract stages take?
- Where are overdue reviews, stale inquiries, missed follow-ups, unassigned records, blocked onboarding items, Graph failures, or quarantined documents accumulating?
- What are the finalized fit and engagement lifecycle distributions?

## Privacy boundary

The dashboard does not expose names, emails, organizations, message bodies, project descriptions, document contents, internal notes, or direct identifiers.

Cohorts below the configured minimum are suppressed. Analytics must not be used to rank senders or automate fit, proposal, contract, or engagement decisions.

## Snapshots

Authorized managers can type `SNAPSHOT ANALYTICS` to store the current aggregate JSON payload and SHA-256 hash. Daily snapshots can be enabled. Snapshot retention is configurable.

## Export

The aggregate JSON export contains the dashboard schema, period, counts, rates with numerator and denominator, cycle-time medians, operational alerts, and fixed boundaries. It contains no direct personal data.
