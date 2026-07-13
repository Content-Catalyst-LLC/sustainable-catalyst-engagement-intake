# Portal Authentication and Recovery

## Authentication invariant

A one-time invitation is not consumed unless all required persistence succeeds.

```text
START TRANSACTION
update access from invited to active
update inquiry portal state
insert session
COMMIT
```

Any failure:

```text
ROLLBACK
record activation_rolled_back
return to usable invitation
```

## Credential order

```text
public access ID
→ submitted token HMAC
→ constant-time token comparison
→ invitation state
→ email HMAC
→ terms
→ transaction
```

Incorrect tokens are security events but do not increment the email lockout counter.

## Recovery invariant

The public recovery surface never reveals identity or record existence.

```text
same response
same redirect
no automatic link
no automatic email
```

Only exact internal reference and email matches create a pending recovery row.

Unmatched attempts create only sanitized security events.

## Human recovery standard

Before approval, inspect:

- inquiry identity and contact email
- portal access state
- activation and session events
- lockout history
- recovery reason
- privacy state
- legal or security concerns
- whether continued access remains appropriate

Approval should not be treated as a business acceptance decision.

## Incident guidance

For suspected credential disclosure:

1. suspend portal access
2. revoke sessions
3. review event hashes and timestamps
4. decline or expire open recovery requests when inappropriate
5. issue a fresh invitation only after verification
6. deliver the link through an approved private channel
7. document the rationale
