# Sustainable Catalyst Engagement Intake

**Version:** 0.8.1  
**Release:** Portal Authentication and Recovery Patch

## Scope

v0.8.1 patches the v0.8.0 Secure Sender Portal without changing its sender-safe data boundary.

```text
v0.8.0 portal
+ atomic activation
+ corrected lockout
+ __Host cookie migration
+ non-enumerating recovery
+ human-approved fresh invitation
```

## Shortcodes

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
[sc_sender_portal title="Secure Sender Portal"]
```

## Atomic activation

The v0.8.1 activation transaction covers:

```text
portal access transition
inquiry portal transition
session creation
```

The one-time invitation hash is cleared inside the transaction.

A failure in any stage performs:

```text
ROLLBACK
→ invitation remains invited
→ raw invitation can be retried
→ activation_rolled_back event
```

Successful activation performs:

```text
COMMIT
→ session cookie
→ invitation_activated event
```

## Lockout correction

The authentication order is:

```text
find public access ID
→ constant-work token hash check
→ reject incorrect token without lockout
→ verify invitation state
→ verify email challenge
→ increment lockout only for incorrect email
```

This closes an identifier-only denial-of-service path.

## Invitation states

A verified invitation can present:

```text
valid
expired
locked
inactive
```

An unverified credential presents only:

```text
invalid
```

The UI never distinguishes why an unverified credential failed.

## Production cookie

```text
__Host-sc_ei_sender_session
```

Attributes:

```text
Secure
HttpOnly
SameSite=Strict
Path=/
No Domain
```

The patch can read the v0.8.0 cookie:

```text
sc_ei_sender_session
```

On a valid HTTPS request, it migrates the same active session credential into the `__Host-` cookie and clears the legacy cookie.

## Correctable activation failures

These failures preserve or replace usable invitation context:

```text
expired WordPress activation nonce
terms not accepted
temporary optimistic conflict
inquiry persistence failure
session persistence failure
browser cookie establishment failure
```

A stale form returns to the original invitation.

A transactional persistence failure rolls back the invitation.

A cookie-establishment failure revokes the unusable session and generates a fresh invitation.

## Recovery request

The public recovery form accepts:

```text
inquiry reference
inquiry email
recovery reason
```

Its response is always:

```text
The request was received.
If the details match an eligible record, it will be reviewed.
No access link is issued automatically.
```

The response is the same for:

```text
matched request
unmatched request
invalid input
honeypot submission
deduplicated request
throttled request
```

## Recovery controls

```text
keyed-IP hourly limit
matched and unmatched event counting
minimum reason length
honeypot
pending-request deduplication
review expiry
hashed reference
hashed email
hashed IP
hashed browser
```

Unmatched attempts generate security events but do not create an identity-bearing recovery row.

## Human review

Reviewers with the view capability can inspect matched recovery requests.

Only managers with:

```text
sc_intake_manage_portal_recovery
```

can approve, decline, or reset lockout.

Approval:

```text
RECOVER <RECOVERY-ID>
```

Decline:

```text
DECLINE <RECOVERY-ID>
```

Lockout reset:

```text
UNLOCK <ACCESS-ID>
```

Every action requires a human rationale.

Recovery approval calls the normal invitation reissue path, preserving access permissions and revoking active sessions.

No email is sent automatically.

## New table

```text
{prefix}sc_ei_portal_recovery_requests
```

Fields:

```text
public_id
inquiry_id
access_id
status
match_status
reference_hash
email_hash
recovery_reason
request_ip_hash
request_user_agent_hash
request_count
requested_at
last_requested_at
expires_at
reviewed_by
reviewed_at
decision_note
completed_at
row_version
created_at
updated_at
```

## New capabilities

```text
sc_intake_view_portal_recovery
sc_intake_manage_portal_recovery
```

Engagement Reviewers receive view access.

Engagement Managers receive view and management access.

## Recovery lifecycle

```text
pending
→ completed
→ declined
→ expired
→ canceled
```

Hourly portal cleanup marks expired pending requests.

It does not delete audit history.

## Privacy

WordPress privacy export includes:

```text
recovery state
recovery reason
request count
review timestamps
decision note
completion state
```

Approved inquiry erasure clears:

```text
reference hash
email hash
recovery reason
IP hash
browser hash
decision note
```

## Upgrade checklist

1. Back up database and protected storage.
2. Upgrade from v0.8.0 to v0.8.1.
3. Confirm HTTPS.
4. Keep the existing sender portal URL.
5. Clear caches.
6. Confirm DB `0.8.1`.
7. Confirm portal schema `1.1.0`.
8. Confirm the recovery table.
9. Confirm recovery capabilities.
10. Confirm hourly cleanup.
11. Test v0.8.0 legacy-cookie migration.
12. Test incorrect-token behavior.
13. Test email-challenge lockout.
14. Test transactional rollback.
15. Test generic matched and unmatched recovery.
16. Test approval, decline, unlock, and link display.
17. Test privacy export and erasure.
