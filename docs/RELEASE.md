# Release Notes — v0.9.0

## Release

Teams Scheduling and Proposal Workflow

## Outcome

Add a sender-safe pathway from reviewed inquiry to Teams meeting and proposal while keeping all high-consequence decisions under human control.

## Human control

The release does not automatically:

- approve an inquiry
- create a Microsoft 365 calendar event
- call Microsoft Graph
- send workflow email
- sign a contract
- collect payment
- activate an engagement

## Meeting controls

```text
PUBLISH <OFFER>
SCHEDULE <OFFER>
COMPLETE <OFFER>
CANCEL <OFFER>
```

## Proposal controls

```text
PUBLISH <PROPOSAL>
ACCEPT <PROPOSAL>
DECLINE <PROPOSAL>
WITHDRAW <PROPOSAL>
CONTRACT <PROPOSAL>
```

## Production verification

1. Confirm database and workflow schemas.
2. Confirm role capabilities.
3. Confirm sender portal permissions.
4. Publish a multi-slot meeting offer.
5. Test sender accept, alternative, and decline.
6. Finalize a Teams link.
7. Download and inspect ICS.
8. Publish a proposal.
9. Draft a revision and confirm the published version remains visible.
10. Publish the revision.
11. Test typed acceptance and decline.
12. Test external-contract attestation.
13. Test expiration cleanup.
14. Test workflow export.
15. Test privacy export and erasure.
