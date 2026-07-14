# Sustainable Catalyst Engagement Intake

**Version:** 0.11.0  
**Release:** Reliability, Accessibility, and Security Hardening

v0.11.0 hardens the complete inquiry-to-engagement platform for production operation without adding automated intake, fit, contracting, payment, or engagement decisions.

## Production hardening model

```text
public intake and sender portal
→ durable abuse limits
→ validated human-controlled workflows
→ deduplicated technical health ledger
→ scheduled watchdog and pruning
→ administrator incident controls
→ redacted recovery export
```

## New Reliability workspace

```text
Engagement Intake → Reliability
```

The workspace provides:

- open critical, warning, and informational health events
- deduplicated event fingerprints and occurrence counts
- component and severity filtering
- scheduled-work watchdog results
- public-write incident pause and recovery
- typed event resolution with an audit note
- redacted operational report export
- bounded retention and pruning controls
- hardened rate-limit thresholds
- security-header and accessibility-helper settings

## Durable health ledger

New table:

```text
{prefix}sc_ei_health_events
```

The ledger records technical metadata only:

- component
- event type
- severity
- redacted message
- secret-filtered context
- occurrence count
- first and last seen timestamps
- resolution time, user, and note

Events are deduplicated by a SHA-256 fingerprint that deliberately excludes the per-request correlation ID.

The ledger does not store sender names, email addresses, message bodies, document contents, access tokens, passwords, client secrets, cookies, or authorization headers.

## Durable abuse protection

New table:

```text
{prefix}sc_ei_rate_limits
```

The limiter uses keyed hashes rather than raw IP addresses or user agents. It protects:

- public inquiry identity submissions
- public inquiry network volume
- sender portal activation
- sender portal recovery
- authenticated portal actions

Counter updates use an atomic database upsert so they remain effective when object caches or transients are cleared.

A rate-limit database failure fails open for availability and records a health warning. Existing nonce, timing, honeypot, duplicate, upload, permission, CSRF, and optimistic-locking controls remain in force.

## Incident write pause

Authorized administrators can enter:

```text
PAUSE PUBLIC WRITES
```

This blocks new public inquiry submissions and sender portal mutations during an incident.

Read-only sender capabilities remain available, including:

- authenticated portal viewing
- sign out
- proposal viewing and printing
- calendar-file download

Recovery requires:

```text
RESUME PUBLIC WRITES
```

Every state change is capability-gated, nonce-protected, typed-confirmation protected, and audited.

## Watchdog

The hourly watchdog checks:

- all plugin database tables
- hardening table columns
- private storage directory, marker, writability, and protection files
- sender portal cleanup schedule
- workflow cleanup schedule
- retention schedule
- notification schedule
- Graph catch-up schedule
- analytics snapshot schedule
- hardening watchdog and pruning schedules
- secure portal transport
- authenticated encryption availability

Manual execution requires:

```text
RUN HARDENING CHECK
```

The watchdog never repairs or deletes records automatically. It records issues for human review.

## Request correlation

Each request receives a UUID correlation identifier through:

```text
X-SC-EI-Request-ID
```

The request ID appears in redacted health and audit context but is excluded from health-event deduplication.

## Security headers

When enabled, v0.11.0 sends:

```text
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()
X-SC-EI-Request-ID: <uuid>
```

A conservative Content Security Policy can be enabled in report-only mode for deployment observation before enforcement.

The Secure Sender Portal retains stricter no-store, no-index, no-referrer, frame-denial, and same-origin protections.

## Fatal error capture

Fatal capture records only:

- PHP error class number
- plugin filename basename
- line number
- request ID

It does not persist the raw PHP error message, stack trace, request body, query string, cookie, or authorization value.

## Accessibility hardening

v0.11.0 adds:

- skip link for Engagement Intake administration pages
- stable primary-content target
- polite live regions
- required-field announcements
- submit busy state announcements
- visible focus treatment
- horizontally keyboard-scrollable data tables
- scoped table headers in the Reliability workspace
- reduced-motion support
- forced-colors support
- non-color status text retained across workflows

These changes strengthen accessibility but are not a third-party accessibility certification.

## Fixed boundaries

v0.11.0 does not:

- inspect private message bodies or uploaded-document contents for health monitoring
- rank senders or staff
- recommend acceptance or rejection
- automate fit assessment
- publish proposals automatically
- create contracts or signatures
- generate invoices or collect payment
- activate engagements automatically
- provision Workbench, Decision Studio, or external projects automatically
- automatically delete personal data or private files

## Capabilities

```text
sc_intake_view_reliability
sc_intake_manage_reliability
sc_intake_export_reliability
```

Reviewers receive view access. Engagement Managers receive view, management, and export access. Administrators retain all plugin capabilities.

## Typed operations

```text
SAVE HARDENING SETTINGS
RUN HARDENING CHECK
PAUSE PUBLIC WRITES
RESUME PUBLIC WRITES
RESOLVE <EVENT-ID>
PRUNE HARDENING DATA
```

## Upgrade checklist

1. Back up the database and protected storage.
2. Upgrade from v0.10.0.
3. Clear WordPress, object, PHP opcode, host, CDN, and browser caches.
4. Open **Engagement Intake → Diagnostics**.
5. Confirm database version `0.11.0`.
6. Confirm hardening schema `1.0.0`.
7. Confirm the health-event and rate-limit tables.
8. Confirm the watchdog and pruning schedules.
9. Open **Engagement Intake → Reliability**.
10. Run `RUN HARDENING CHECK`.
11. Review any critical or warning events.
12. Test public inquiry throttling in staging.
13. Test sender portal activation and recovery throttling.
14. Pause public writes and verify read-only portal access.
15. Resume public writes.
16. Test keyboard navigation, focus, reduced motion, and forced-colors behavior.
17. Export a redacted hardening report.
18. Review production query and cron performance.
