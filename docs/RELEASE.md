# Release Notes — v0.8.1

## Release

Portal Authentication and Recovery Patch

## Critical fixes

### Atomic activation

Invitation consumption, inquiry state, and session creation now commit together.

### Lockout isolation

Wrong tokens never increment the verified invitation's email lockout.

### Recovery

Expired, lost, consumed, revoked, locked, or browser-bound access can enter a non-enumerating human-review queue.

### Cookie hardening

HTTPS uses a `__Host-` cookie while preserving controlled migration from v0.8.0 active sessions.

## No automated recovery

The public recovery path does not:

- confirm a match
- issue a link
- send email
- unlock access
- reactivate a revoked record
- change inquiry status

## Production verification

1. Confirm HTTPS.
2. Upgrade and clear caches.
3. Verify DB and portal schema.
4. Verify the recovery table.
5. Verify role capabilities.
6. Test legacy-cookie migration.
7. Test wrong token without lockout.
8. Test correct token and wrong email with lockout.
9. Test expired nonce retry.
10. Test transaction rollback.
11. Test generic matched and unmatched recovery.
12. Test rate limiting.
13. Test human approval and decline.
14. Test typed unlock.
15. Test one-time link display.
16. Test privacy export and erasure.
