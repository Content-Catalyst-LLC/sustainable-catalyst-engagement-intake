# Release Notes — v0.9.1

## Release

Microsoft Graph Reliability Patch

## Outcome

Optionally convert an accepted Teams time into a Microsoft 365 calendar-backed Teams event while preserving human control and manual fallback.

## Reliability contract

```text
encrypted credentials
+ encrypted token cache
+ persistent transactionId
+ encrypted durable operation
+ optimistic claim
+ Retry-After
+ bounded exponential backoff
+ circuit breaker
+ reconciliation
+ request IDs
+ manual same-operation retry
```

## Fixed boundaries

The connector does not:

- automatically discover and book accepted times
- use delegated user login
- use Graph beta
- use sovereign cloud endpoints
- create proposals or contracts
- sign documents
- collect payment
- remove manual Teams finalization
- expose client secrets or access tokens

## Production verification

1. Verify Entra application permission and admin consent.
2. Verify Exchange Application RBAC scope.
3. Verify encrypted credential storage.
4. Verify token scope.
5. Verify health check.
6. Verify idempotent create.
7. Verify join URL reconciliation.
8. Verify Retry-After.
9. Verify circuit opening and reset.
10. Verify permanent-failure retry.
11. Verify remote deletion behavior.
12. Verify local-state race protection.
13. Verify manual fallback.
14. Verify redacted exports.
15. Verify privacy erasure.
