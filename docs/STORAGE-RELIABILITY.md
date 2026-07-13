# Production Storage Reliability

## Storage probe

The probe creates a random private temporary file and verifies:

- write
- read-back
- atomic rename
- delete

It stores only the result, not the probe content.

## Repair action

Repair:

- initializes known directories
- rewrites `.htaccess`, `web.config`, `index.php`, and `index.html`
- applies best-effort `0700` directory permissions
- removes `.part-*` files older than one hour
- runs the probe

Repair does not delete committed `.qtn` files.

## Reconciliation

Reconciliation checks up to 1,000 active database records and 5,000 managed files per manual run.

The report marks whether either limit was reached.

### Missing

Record exists, physical file does not.

### Hash mismatch

Physical file differs from recorded SHA-256.

### Size mismatch

Physical file size differs from recorded size.

### Misplaced

Database status expects `approved/` or `quarantine/`, but the relative path uses the other area.

### Unresolvable

Relative path fails safe containment.

### Orphan

Managed `.qtn` file has no active attachment record.

No issue is automatically repaired or deleted.

## Hosting guidance

Preferred:

```php
define( 'SC_EI_PRIVATE_STORAGE_PATH', '/private/non-web/sc-engagement-intake' );
```

Verify:

- outside `DOCUMENT_ROOT`
- writable by PHP
- stable across deployments
- not ephemeral
- enough free disk space
- included in encrypted backup policy
- excluded from public CDN or object-storage synchronization

## Cloudflare exclusions

Exclude:

```text
Consulting page URL
Contact page URL
/wp-json/sc-engagement-intake/v1/submit
/wp-admin/admin-post.php?sc_ei_submission=1
```

Also avoid HTML caching rules that ignore `Set-Cookie`, `Cache-Control`, or WordPress nonce behavior.

## Submission idempotency

Request IDs protect against double-clicks, mobile resubmissions, and timeout retries. The server lock is acquired only after validation and released in a `finally` block. Successful responses are replayable for 15 minutes.
