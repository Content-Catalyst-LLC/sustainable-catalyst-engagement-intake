# Security Model

## v0.1.0 guarantees

- No public submission route
- No public inquiry archive
- No unauthenticated REST access
- Dedicated capabilities
- Nonces for administrative writes
- Sanitization and escaping
- Separate private database records
- Privacy exporter and eraser
- Conservative data preservation
- No raw IP address storage
- No physical upload handling before quarantine and protected storage exist

## Secure upload requirements for v0.3.0

- Allowlist extensions and MIME types
- File-signature inspection
- Macro and executable blocking
- Quarantine before administrative download
- Malware-scanner integration
- Randomized internal filenames and paths
- Storage outside public Media Library behavior
- Permission checks on every download
- Expiring signed download URLs
- Download audit events
- Retention and deletion controls
- No permanent file URLs in email

## Reporting

Security concerns should be reported privately through the project owner rather than filed with confidential details in a public issue.
