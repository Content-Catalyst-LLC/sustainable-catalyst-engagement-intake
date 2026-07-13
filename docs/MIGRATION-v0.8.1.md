# Migration to v0.8.1

## Versions

```text
SC_EI_VERSION = 0.8.1
SC_EI_DB_VERSION = 0.8.1
SC_EI_PORTAL_SCHEMA_VERSION = 1.1.0
```

## New table

```text
{prefix}sc_ei_portal_recovery_requests
```

## Existing data

The migration preserves:

- inquiries
- portal access
- active sessions
- portal events
- secure messages
- protected documents
- review history
- fit assessments
- privacy requests
- consent
- legal holds
- retention actions
- audit history

No recovery request is created during migration.

## Session compatibility

v0.8.0 active sessions used:

```text
sc_ei_sender_session
```

v0.8.1 production HTTPS sessions use:

```text
__Host-sc_ei_sender_session
```

During transition:

1. v0.8.1 reads the legacy cookie.
2. It verifies the existing session hash and all normal session controls.
3. On HTTPS, it sets the `__Host-` cookie.
4. It clears the legacy cookie.
5. It records `legacy_cookie_migrated`.

No session database migration is required.

## New capabilities

```text
sc_intake_view_portal_recovery
sc_intake_manage_portal_recovery
```

Role assignment:

```text
Engagement Reviewer → view recovery
Engagement Manager → view and manage recovery
Administrator → all recovery capabilities
```

## Activation change

v0.8.0 could consume an invitation before session creation was guaranteed.

v0.8.1 moves access, inquiry, and session changes into one transaction.

A failed transaction does not consume the invitation.

## Lockout change

v0.8.0 could increment failed attempts before fully separating token and email failures.

v0.8.1:

```text
wrong token → reject, no lockout increment
correct token + wrong email → increment lockout
```

Existing failed-attempt counters remain visible.

An authorized manager can reset a reviewed lockout.

## Recovery backfill

None.

Existing inquiries and portal access records become eligible for recovery only when the sender submits matching reference and email details.

## Upgrade sequence

1. Back up database and protected storage.
2. Confirm the existing portal page is HTTPS.
3. Upgrade to v0.8.1.
4. Clear PHP opcode, WordPress object, page, reverse-proxy, and CDN caches.
5. Confirm DB 0.8.1.
6. Confirm portal schema 1.1.0.
7. Confirm the recovery table.
8. Confirm new role capabilities.
9. Confirm hourly cleanup.
10. Test one active v0.8.0 legacy cookie.
11. Test fresh activation.
12. Test wrong-token and wrong-email behavior.
13. Test recovery approval and decline.
14. Test privacy export and erasure in staging.

## Rollback

Before a temporary rollback to v0.8.0:

1. revoke newly created v0.8.1 sessions where operationally appropriate
2. preserve the recovery table
3. preserve audit and recovery history
4. understand that v0.8.0 does not expose the recovery queue
5. understand that v0.8.0 will use the legacy cookie name

Do not drop the recovery table merely to restore the older interface.
