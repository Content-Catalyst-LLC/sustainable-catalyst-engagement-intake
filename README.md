# Sustainable Catalyst Engagement Intake

**Version:** 0.8.0  
**Release:** Secure Sender Portal

## Public surfaces

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
[sc_sender_portal title="Secure Sender Portal"]
```

## Portal trust model

```text
authorized administrator
→ one-time invitation
→ inquiry-email challenge
→ terms acceptance
→ hashed credential verification
→ HttpOnly SameSite Strict session
→ sender-safe private workspace
```

The portal has no public lookup and creates no WordPress user.

Stored credentials:

```text
HMAC invite hash
HMAC session hash
HMAC email hash
HMAC IP fingerprint
HMAC user-agent fingerprint
```

Not stored:

```text
raw invitation token
raw session token
plaintext IP address
plaintext browser fingerprint
reusable portal password
```

## Sender capabilities

An invitation can permit:

```text
view sender-safe status
view secure messages
send secure messages
view private document metadata
upload private follow-up documents
update contact preferences
update Teams scheduling preferences
submit privacy requests
request inquiry withdrawal
revoke portal access
```

Every capability is checked on every write.

Privacy restrictions can block new processing while preserving status, existing message, privacy-request, and access-revocation controls.

## Message publication

Portal messages use the Communication History table.

New sender and staff portal messages are immediately portal-visible and use:

```text
channel = sender_portal
communication_type = portal_message
provider = sender_portal
email_sent = false
privacy_classification = private
```

Existing outbound email or manual communication is hidden by default.

An authorized reviewer must explicitly publish it.

Draft, failed, canceled, suppressed, inbound, and internal records cannot be published.

## Documents

Sender follow-up documents use the existing protected upload pipeline:

```text
authenticated portal
→ validation
→ atomic protected storage
→ quarantine
→ scanner policy
→ administrative review
```

The sender sees metadata only.

No public download endpoint is created.

## Contact and Teams updates

The sender can update approved contact and scheduling fields.

The inquiry email cannot be changed through the portal because it is part of the activation challenge.

Teams preferences do not book a meeting.

Calendar permission changes create consent-ledger events.

## Withdrawal

Typed confirmation:

```text
WITHDRAW <REFERENCE>
CANCEL <REFERENCE>
```

A withdrawal request does not:

```text
change inquiry status automatically
erase records
release legal holds
cancel retention obligations
```

## Access controls

Sender self-revocation requires:

```text
REVOKE <REFERENCE>
```

Administrative session revocation requires:

```text
SESSIONS <ACCESS-ID>
```

Administrative suspension or revocation requires:

```text
SUSPENDED <ACCESS-ID>
REVOKED <ACCESS-ID>
```

Revoked or expired access cannot be resumed. A fresh invitation is required.

## Expiration

Hourly cleanup marks:

```text
unused expired invitations → expired
absolute-expired sessions → expired
idle-expired sessions → expired
```

It does not delete audit history.

## Internal-data boundary

The sender portal never renders:

```text
internal notes
fit assessments
review summaries
decision rationale
risk level
legal-hold reason
retention queue
audit narratives
assignments
escalation notes
protected file paths
```

## New tables

```text
{prefix}sc_ei_portal_access
{prefix}sc_ei_portal_sessions
{prefix}sc_ei_portal_events
```

## New inquiry fields

```text
portal_status
portal_access_id
portal_last_activity_at
portal_message_count
portal_document_count
portal_last_sender_message_at
sender_withdrawal_status
sender_withdrawal_requested_at
sender_withdrawal_reason
portal_version
```

## Communication publication fields

```text
portal_visibility
portal_published_at
portal_published_by
portal_source
```

## Capabilities

```text
sc_intake_view_sender_portal
sc_intake_manage_sender_portal
sc_intake_issue_portal_invites
sc_intake_post_portal_messages
sc_intake_revoke_portal_access
sc_intake_manage_portal_settings
sc_intake_export_portal_audit
```

Reviewers can view and post secure portal messages.

Managers can issue invitations, manage access, terminate sessions, configure the portal, and export audit data.

## Privacy

WordPress privacy export includes:

- portal access state
- permissions and terms evidence
- sessions
- portal events
- withdrawal state
- portal-visible messages
- uploaded document metadata

Approved inquiry erasure:

- revokes active sessions
- replaces session hashes
- clears email, IP, and browser hashes
- clears invitation and access narratives
- clears withdrawal reason
- redacts event context
- preserves limited categorical and audit tombstones

## Production checklist

1. Back up database and protected storage.
2. Upgrade to v0.8.0.
3. Create the sender portal page and shortcode.
4. Exclude the page from caching and navigation.
5. Confirm HTTPS and security headers.
6. Confirm DB and portal schema versions.
7. Confirm hourly cleanup.
8. Test single-use invitation activation.
9. Test incorrect email and lockout.
10. Test idle and absolute expiration.
11. Test concurrent session limit.
12. Test reissue revokes active sessions.
13. Test secure messages without email.
14. Test communication publication boundaries.
15. Test quarantine uploads.
16. Test privacy restriction blocking.
17. Test withdrawal and self-revocation.
18. Test privacy export and erasure in staging.
19. Test audit export.
