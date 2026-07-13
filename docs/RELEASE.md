# Release Notes — v0.8.0

## Release

Secure Sender Portal

## Outcome

Create a private continuation channel after public intake without turning senders into WordPress users or exposing internal operations.

## Security boundary

```text
single-use credential
+ inquiry-email challenge
+ terms
+ revocable session
+ CSRF
+ capability
+ privacy state
+ rate limit
```

## No automatic actions

The portal does not automatically:

- email an invitation
- accept or reject an inquiry
- change inquiry status
- schedule a Teams meeting
- send a proposal
- publish an internal communication
- approve a document
- release quarantine
- erase data
- release a legal hold

## Production verification

1. Confirm HTTPS.
2. Create the portal page.
3. Exclude it from all caches.
4. Confirm headers with browser developer tools.
5. Issue a test invitation.
6. Verify raw link is shown once.
7. Verify incorrect email and token behavior.
8. Verify invitation lockout.
9. Verify token reuse fails.
10. Verify cookie flags.
11. Verify idle and absolute expiration.
12. Verify concurrent session limit.
13. Verify reissue revokes sessions.
14. Verify status and internal-data boundary.
15. Verify messages do not send email.
16. Verify explicit communication publication.
17. Verify protected quarantine upload.
18. Verify privacy restrictions.
19. Verify withdrawal remains status-neutral.
20. Verify self and administrative revocation.
21. Verify privacy export and erasure.
22. Verify audit export.
