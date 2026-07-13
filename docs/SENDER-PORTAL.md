# Secure Sender Portal

## Purpose

Give an inquiry sender a private, auditable way to continue an engagement without exposing WordPress accounts, internal review state, protected storage, or public file links.

## Invitation handling

An authorized manager issues an invitation.

The database stores:

```text
access public ID
HMAC token hash
token prefix for support
expiry
HMAC inquiry-email hash
permissions
terms version
issuer and timestamps
```

The raw URL is placed in a user-scoped transient for five minutes, displayed once, and deleted when the detail page loads.

Reissuing an invitation revokes every active session.

## Activation

Activation verifies:

1. access is invited
2. invitation has not expired
3. invitation is not locked
4. HMAC token matches
5. HMAC email challenge matches
6. required terms are accepted
7. inquiry has not been erased

The invitation hash is cleared after successful activation.

## Sessions

Sessions use a random 48-byte base64url credential.

The browser receives it only in:

```text
Secure when HTTPS
HttpOnly
SameSite=Strict
path=/
```

The database stores only an HMAC hash.

Validation checks:

```text
active session
not revoked
absolute expiration
idle expiration
active access
browser hash
available inquiry
privacy state
```

IP changes are recorded as keyed-hash events but do not automatically lock out mobile or changing-network users.

## CSRF

Write forms include a token derived from:

```text
raw session credential
session public ID
WordPress nonce salt
```

No WordPress login nonce is required for authenticated senders.

## Rate limits

Per-session event counts enforce:

```text
secure messages per hour
preference/document/privacy updates per hour
failed invitation attempts
temporary invitation lockout
maximum concurrent sessions
```

## Public protection

The portal page applies:

```text
Cache-Control: no-store, no-cache, private
Pragma: no-cache
Expires in the past
Referrer-Policy: no-referrer
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Cross-Origin-Opener-Policy: same-origin
Cross-Origin-Resource-Policy: same-origin
robots: noindex, nofollow, noarchive, nosnippet
DONOTCACHEPAGE
```

Also exclude the page in hosting, CDN, page-cache, optimization, and search plugins.

## Message rules

Sender and staff portal messages:

- are private
- remain in Communication History
- do not invoke mail transport
- are visible only through active portal access
- preserve direction and timestamps

Existing outbound communication is hidden until separately published.

## Document rules

Portal uploads inherit all v0.3.x controls:

- extension and MIME validation
- content validation
- active-content rejection
- atomic write
- protected path
- quarantine
- scanner state
- integrity checks
- retention state
- audit

The sender receives no download URL.

## Privacy restrictions

For `restricted` or `erasure_requested` inquiries, the sender may still:

- view sender-safe status
- read existing visible messages
- submit privacy requests
- revoke portal access

The sender may not:

- create messages
- upload documents
- change contact information
- change scheduling information
- request workflow withdrawal

## Withdrawal

A withdrawal is a request for human action.

It is not equivalent to privacy erasure.

It does not change the inquiry status automatically.

## Incident response

After suspected invitation or session compromise:

1. suspend or revoke access
2. revoke every session
3. record the reason
4. review hashed event history
5. reissue only after verification
6. review portal-visible messages and documents
7. review privacy state and legal holds
8. preserve required evidence

## Audit retention

v0.8.0 stores the configured portal event retention period for governance planning but does not automatically delete audit events. Deletion must remain in the reviewed Privacy and Retention Center lifecycle.
