# Release Notes — v0.2.0

## Purpose

Turn the private v0.1.0 record foundation into a usable public Contact Hub without introducing document-upload risk before protected storage and quarantine exist.

## Public experiences

- Adaptive Contact Hub
- General Contact Form
- Consulting and Engagement Inquiry

## Submission path

1. Choose inquiry type.
2. Enter identity and organization context.
3. Complete conditional fields.
4. Review the submission.
5. Accept privacy and authorization terms.
6. Submit through REST or non-JavaScript fallback.
7. Receive a human-readable inquiry reference.
8. Create a private inquiry and audit event.

## Security controls

- WordPress nonce
- Signed form-start timestamp
- Minimum completion time
- Honeypot
- Email-based rate limit
- Duplicate suppression
- Field-length limits
- URL allowlisting through WordPress sanitization
- Server-side conditional validation
- No raw IP storage
- No public read endpoint
- No file uploads in this release
- No-JavaScript form fallback
- Dynamic page cache protection for nonce-bearing forms

## Next release

v0.3.0 adds secure document intake, quarantine, validation, protected storage, scan state, signed downloads, and attachment audit history.
