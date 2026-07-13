# Security Model

## v0.2.2 public form controls

- WordPress nonce validation
- Write-only public REST submission route
- Non-JavaScript admin-post fallback
- Hidden honeypot
- Signed form-start timestamp
- Configurable minimum completion time
- Configurable email-based hourly rate limit
- Exact duplicate suppression
- Field-length constraints
- Server-side conditional validation
- Sanitized text, dates, email, and URLs
- No raw IP address storage
- No public inquiry archive
- Dynamic form pages request no-cache headers and define `DONOTCACHEPAGE` when supported
- No unauthenticated inquiry-read endpoint
- Dedicated private capabilities
- Audit event for public submission

## Document boundary

v0.2.2 does not accept physical documents. The form warns against credentials, payment data, regulated health records, highly sensitive personal data, export-controlled material, and confidential documents.

## Secure upload requirements for v0.3.0

- Allowlist extensions and MIME types
- File-signature inspection
- Macro and executable blocking
- Quarantine before administrative download
- Malware-scanner integration
- Randomized internal filenames and paths
- Protected storage outside normal public Media Library behavior
- Permission checks on every download
- Expiring signed download URLs
- Download audit events
- Retention and deletion controls
- No permanent file URLs in email

## Full-page caching

The shortcode requests dynamic no-cache behavior because WordPress nonces and signed form timing should not be served indefinitely from a static cache. When a CDN ignores WordPress cache controls, exclude the Contact page from full-page HTML caching.


## Microsoft Teams scheduling security

- No Microsoft credentials are collected from visitors.
- No Graph client secret is stored in an inquiry.
- Teams meeting URLs are validated against an allowlist of Teams hosts.
- Calendar invitation consent is stored explicitly.
- Participant emails are normalized and limited.
- Accessibility notes are private and highlighted as sensitive in administration.
- Scheduled local times are normalized to UTC.
- Scheduling changes require `sc_intake_review`.
- Scheduling changes generate audit events.
- No calendar event is created automatically in v0.2.2.


## Conversion guidance boundary

- Guidance is computed from allowlisted service and budget keys.
- Guidance text is escaped before output.
- Guidance never changes status.
- Guidance never approves, rejects, or schedules an inquiry.
- Source and CTA attributes are sanitized before storage.
- Signed source, CTA, and form-variant attribution is verified before record creation.
- No analytics vendor receives inquiry content by default.
