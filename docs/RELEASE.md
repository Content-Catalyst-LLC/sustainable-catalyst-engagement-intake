# Release Notes — v0.11.0

## Release

Reliability, Accessibility, and Security Hardening

## Outcome

Strengthen the full Engagement Intake platform for production operations while preserving human decisions and private-data boundaries.

## Reliability contract

```text
durable health ledger
+ durable keyed abuse limits
+ request correlation
+ actual cron readiness checks
+ private storage checks
+ fatal metadata capture
+ incident write pause
+ human recovery
```

## Accessibility contract

```text
skip link
+ primary target
+ live region
+ invalid-field announcement
+ submit busy state
+ visible focus
+ reduced motion
+ forced colors
+ keyboard-scrollable tables
```

## Security contract

```text
no secret context
+ no raw identity rate-limit keys
+ no automatic decisions
+ no automatic deletion
+ redacted exports
+ conservative headers
+ CSP report-only option
```

## Production verification

1. Run schema and capability checks.
2. Run the watchdog.
3. Validate private storage.
4. Validate WordPress cron or real cron.
5. Exercise rate limits.
6. Exercise write pause and resume.
7. Verify read-only portal operations during pause.
8. Review response headers.
9. Test keyboard and assistive-technology behavior.
10. Export and inspect the redacted report.
