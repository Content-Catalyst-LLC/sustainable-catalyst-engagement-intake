# Release Notes — v0.12.0

## Release

Workflow Core Integration

## Outcome

Create one canonical, auditable workflow projection and controlled cross-plugin handoff mechanism across the Sustainable Catalyst platform.

## Reliability contract

```text
authoritative records
+ projection hash
+ idempotent command key
+ signed handoff
+ unique outbox event key
+ optimistic claim
+ bounded retry
+ explicit acknowledgment
```

## Production verification

1. Verify all four new tables and indexes.
2. Verify Workflow Core capabilities.
3. Verify sync and outbox cron schedules.
4. Synchronize all cases.
5. Review blockers and warnings.
6. Register one staging adapter.
7. Prepare an operational-minimum handoff.
8. Verify content hash and signature.
9. Dispatch the outbox event.
10. Verify idempotent target import.
11. Record acknowledgment.
12. Test stale-claim recovery.
13. Test adapter failure and retry.
14. Test privacy erasure tombstones.
15. Test read-only REST access.
16. Test rollback while preserving tables.
