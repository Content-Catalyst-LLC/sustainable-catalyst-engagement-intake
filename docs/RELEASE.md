# Release Notes — v0.6.0

## Release

Privacy and Retention Center

## Primary outcome

Move privacy and retention from scattered settings and direct cleanup into a centralized, human-controlled lifecycle system.

## New workflow

```text
policy
→ preview
→ queue
→ hold/dependency review
→ approval
→ typed execution
→ verification
→ audit
```

## Non-destructive automation

Daily automation only queues candidates.

No automatic action can:

- delete a protected file
- redact an inquiry
- redact a communication
- mark an inquiry erased
- bypass a legal hold

## Erasure completeness

Approved inquiry erasure covers:

- inquiry contact and narrative fields
- Teams and scheduling details
- communications
- transport-event context
- review narratives and snapshots
- consent evidence and subject hashes
- privacy-request identifiers and narratives
- released-hold narratives and authorities
- retention snapshots and failure narratives

Private documents must already be deleted and verified.

## Production verification

1. Back up database and protected storage.
2. Upgrade to v0.6.0.
3. Confirm migrations in Diagnostics.
4. Review policy periods.
5. Confirm cron queue-only status.
6. Test request creation and identity states.
7. Test consent events and withdrawal restriction.
8. Test inquiry and attachment holds.
9. Preview candidates.
10. Queue candidates.
11. Confirm no physical deletion.
12. Approve one staging document action.
13. Type the execution phrase.
14. Confirm physical absence and tombstone.
15. Test an inquiry action with a remaining document and confirm dependency blocking.
16. Delete the document through an approved action.
17. Execute inquiry erasure.
18. Inspect retained tombstone and audit history.
19. Test the WordPress privacy exporter.
20. Test the WordPress eraser and confirm it only queues.
21. Confirm restricted inquiries suppress sender-facing email.

## Limitations

- No automated legal analysis.
- No jurisdiction detection.
- No automatic identity verification.
- No Microsoft Graph.
- No mailbox ingestion.
- No provider delivery webhook.
- No live WordPress production activation was performed in the build environment.
