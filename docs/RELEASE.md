# Release Notes — v0.7.0

## Release

Human-Controlled Fit Assessment

## Primary outcome

Add structured fit reasoning without turning intake into an automated eligibility or sales-scoring system.

## Workflow

```text
draft
→ human submission
→ independent review when required
→ ready to finalize
→ typed finalization
→ optional typed Review Workspace application
```

## Human-control guarantees

- no score threshold
- no score-derived recommendation
- no automated acceptance
- no automated rejection
- no automated inquiry status
- no automated email
- no automated Teams meeting
- no automated proposal
- no automated referral

## Assessment evidence

Sixteen criteria require human ratings and can require evidence.

Material concerns require explicit notes.

Assistance must be disclosed.

## Review integrity

- drafts use optimistic locking
- assessor ownership is enforced
- second reviewer can be required to differ
- Agree cannot alter the submitted conclusion
- post-submission edits reset prior review clearance
- finalization freezes one version
- reassessment creates a new version

## Production verification

1. Back up database and protected storage.
2. Upgrade to v0.7.0.
3. Confirm migrations in Diagnostics.
4. Review fit settings.
5. Create a staging assessment.
6. verify evidence requirements.
7. Verify concurrent edit conflict.
8. Verify independent review triggers.
9. Verify post-submission reset.
10. Finalize with typed confirmation.
11. Confirm no external or inquiry status side effect.
12. Apply to Review Workspace separately.
13. Inspect immutable review snapshot.
14. Test JSON export.
15. Test privacy export and erasure.
16. Review fit criteria and operating policy with appropriate leadership and counsel.

## Limitations

- no automated legal conflict determination
- no professional licensing determination
- no identity or fraud assessment
- no automated pricing or proposal generation
- no predictive model
- no Microsoft Graph
- no production WordPress activation in the build environment
