# Reliability, Accessibility, and Security Operations

## Daily posture

Review:

```text
Engagement Intake → Reliability
```

Prioritize:

1. critical unresolved events
2. private storage failures
3. database schema failures
4. missing scheduled jobs
5. secure-transport failures
6. repeated rate-limit triggers
7. Graph, quarantine, and retention warnings

## Incident pause

Use public-write pause when public mutation endpoints should be stopped without taking the entire site or sender portal offline.

```text
PAUSE PUBLIC WRITES
```

Still available:

- portal authentication with an existing invitation or session
- read-only portal records
- proposal print
- meeting ICS download
- logout
- WordPress administration

Blocked:

- new public inquiry submission
- portal activation and recovery
- portal messages
- portal uploads
- contact and scheduling changes
- meeting and proposal responses
- privacy and withdrawal mutations
- access revocation

After validation:

```text
RESUME PUBLIC WRITES
```

## Watchdog recovery

Run:

```text
RUN HARDENING CHECK
```

When a scheduled job is missing:

1. confirm WordPress cron is enabled
2. confirm the site receives traffic or has a real cron trigger
3. deactivate and reactivate in staging when appropriate
4. confirm the expected hook is present
5. rerun the watchdog
6. resolve the event only after verification

The watchdog reports; it does not silently repair production state.

## Event resolution

Enter:

```text
RESOLVE <EVENT-ID>
```

The resolution note should record:

- what was checked
- whether the issue was reproduced
- corrective action
- validation evidence
- whether follow-up remains

A repeated matching event reopens the deduplicated health record.

## Rate-limit response

The rate-limit ledger stores keyed hashes, windows, counts, and block expiration—not raw IP addresses or email addresses.

Repeated legitimate blocks can be addressed by adjusting the bounded thresholds in the Reliability workspace. Do not disable nonce, CSRF, timing, duplicate, upload, or permission controls to work around rate limits.

## Fatal events

Fatal capture intentionally omits raw error messages and stack traces. Use the recorded filename, line, request ID, server PHP log, and deployment commit to investigate.

Do not paste access tokens, client secrets, sender records, or private document contents into resolution notes.

## Accessibility verification

Test with:

- keyboard-only navigation
- browser zoom
- reduced motion
- forced-colors or high-contrast mode
- screen-reader focus and live announcements
- table scrolling without pointer input
- form error focus and recovery

Accessibility helpers improve the plugin interface but do not replace formal accessibility testing.

## Security-header rollout

Security headers are enabled by default.

CSP remains report-only and disabled by default. Enable it first in staging, inspect reports and browser behavior, then decide whether a site-wide enforcement policy belongs at the server or CDN layer.

## Redacted report

The Reliability export contains:

- versions
- metrics
- watchdog state
- technical health events
- request IDs
- fixed safety boundaries

It does not contain:

- client secrets
- access tokens
- passwords
- cookies
- authorization headers
- sender names or emails
- message bodies
- document contents
