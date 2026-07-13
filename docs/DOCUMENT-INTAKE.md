# Secure Document Intake

## Recommended defaults

```text
Maximum files: 5
Maximum size: 20 MB each
Retention: 180 days
Allowed: PDF, DOCX, XLSX, CSV, TXT, PNG, JPG/JPEG
External scanner required: no
```

The effective maximum is also constrained by PHP `upload_max_filesize`, `post_max_size`, and `max_file_uploads`.

## Storage configuration

Preferred before the first accepted upload:

```php
define( 'SC_EI_PRIVATE_STORAGE_PATH', '/absolute/private/path/sc-engagement-intake' );
```

The directory should:

- be outside the server document root
- be writable by PHP
- not be shared by another application
- be included in encrypted backups only when appropriate
- not be synchronized to public object storage

The plugin locks the effective path after the first accepted document.

## Scanner contract

`sc_ei_scan_attachment` receives:

1. the default result
2. the absolute protected file path
3. validated metadata and intake context

Return:

```php
array(
    'status'   => 'clean',
    'provider' => 'clamav',
    'message'  => 'No threat detected.',
)
```

Allowed statuses:

- clean
- infected
- error
- skipped
- not_configured

## Status meanings

### Quarantined

Validated and stored, but not approved for normal controlled use.

### Approved

Human-reviewed and moved into the protected approved area.

### Replacement Requested

The current file remains private and is returned to quarantine. The sender must currently provide a replacement through another approved contact route; a secure magic-link replacement flow is a later release.

### Rejected

The physical file is deleted. Minimal metadata remains for auditability.

### Deleted

The physical file is deleted through retention, privacy erasure, or an authorized administrative action.

## CSV warning

CSV formula content is not automatically rejected. It receives a security flag. Open CSV files with spreadsheet protections because cells beginning with `=`, `+`, `-`, or `@` can be interpreted as formulas by spreadsheet software.

## Hyperlink warning

Safe HTTP, HTTPS, and mailto hyperlinks in DOCX/XLSX are permitted and flagged. External templates, linked data, remote media, DDE, file URIs, and unsafe schemes are rejected.
