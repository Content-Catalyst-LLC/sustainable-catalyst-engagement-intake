# Release Notes — v0.9.2

## Release

Proposal and Engagement Handoff

## Outcome

Complete the controlled transition from contracted proposal to operational engagement while separating commercial agreement, handoff preparation, readiness, and activation.

## Safety contract

```text
contracted proposal does not activate work
handoff creation does not activate work
ready state does not activate work
activation requires a fresh readiness check and typed human confirmation
```

## Production verification

1. Verify new tables and indexes.
2. Verify role capabilities.
3. Create one staging handoff.
4. Confirm duplicate creation is blocked.
5. Confirm transaction rollback with a controlled fixture or staging failure.
6. Verify proposal and snapshot hashes.
7. Verify required items block readiness.
8. Verify owner and privacy checks.
9. Verify typed ready and activate actions.
10. Verify sender-safe portal view.
11. Verify no internal notes appear publicly.
12. Verify pause, resume, completion, and cancellation.
13. Verify private export.
14. Verify privacy export and approved erasure.
15. Verify Workbench and Decision Studio remain unprovisioned.
