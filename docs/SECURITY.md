# Security Architecture

## Public form controls

- WordPress nonce
- signed form variant, source page, and CTA attribution
- honeypot
- minimum completion time
- per-email rate limiting
- duplicate inquiry detection
- strict sanitization
- conditional server-side requirements
- no-cache form rendering
- generic public error messages
- no public inquiry archive

## Document authorization

A selected file requires explicit authorization confirming that the sender may upload it and understands that accepted files enter protected quarantine.

The form prohibits:

- passwords and credentials
- payment-card data
- regulated health records
- government identification
- highly sensitive personal data
- export-controlled material
- executable code
- archives
- macro-enabled files
- encrypted files
- unauthorized documents

## Storage isolation

Files are not WordPress attachments.

They are stored with:

- randomized UUID-based internal names
- `.qtn` storage extension
- separate quarantine and approved areas
- SHA-256 fingerprints
- restrictive file permissions where supported
- Apache, IIS, and index-denial files
- no public REST file route
- no direct public URL stored in the database

The plugin prefers a directory outside the server document root. Storage inside the web root is considered a diagnostic warning even when deny files exist, because Nginx, proxies, CDNs, or hosting configuration may bypass Apache rules.

## Fail-closed validation

The validator rejects ambiguous or unsupported files rather than attempting to repair them.

Controls include:

- extension allowlist
- Fileinfo MIME
- WordPress file type cross-check
- executable/script magic bytes
- format signatures
- PDF active-content/encryption checks
- Office Open XML structure and relationship checks
- Office encryption/macro/embedded-object checks
- archive entry and expansion limits
- image decoding and pixel limit
- binary/script text checks
- SHA-256 generation

## Malware scanning

No antivirus engine is bundled.

External scanners integrate through:

- `sc_ei_scan_attachment`
- `sc_ei_scanner_probe`

Scanner-required mode deletes and rejects anything not reported clean.

## Administrative authorization

Capabilities:

- `sc_intake_view`
- `sc_intake_review`
- `sc_intake_download_files`
- `sc_intake_release_files`
- `sc_intake_manage_file_retention`
- `sc_intake_add_notes`
- `sc_intake_change_status`
- `sc_intake_communicate`
- `sc_intake_export`
- `sc_intake_delete`
- `sc_intake_manage_settings`

Every file action also requires a per-file nonce.

## Download safety

Before streaming:

- user capability is checked
- nonce is checked
- metadata must be active
- infected files are blocked
- physical path is reconstructed from a sanitized relative path
- containment within the locked storage directory is checked
- SHA-256 integrity is verified
- an audit event is recorded

Downloads force attachment disposition. Quarantined files use `application/octet-stream`.

## Retention and deletion

Accepted files receive a default retention date. Daily cron removes expired files. Authorized managers can change retention.

Rejection and explicit deletion remove the physical file and retain minimal audit metadata. Uninstall deletes private storage only when the administrator explicitly enabled complete data deletion.

## Privacy

The personal-data exporter includes metadata only.

The eraser attempts physical deletion first. It reports retained items when deletion fails rather than falsely claiming success.

## Operational guidance

For higher-risk intake:

- define `SC_EI_PRIVATE_STORAGE_PATH` outside the web root before the first accepted upload
- integrate an antivirus or content-disarm service
- enable scanner-required mode only after the integration is verified
- review quarantined files on an isolated workstation
- keep WordPress, PHP, and server packages patched
- back up encrypted at rest
- restrict administrator accounts and require MFA at the identity layer


## v0.3.1 reliability controls

- staging file is verified before final rename
- committed file is verified after final rename
- storage locks after a successful commit
- lock-race changes remove the committed file
- managed moves refuse destination overwrite
- invalid paths cannot report successful deletion
- request overrun detection is restricted to marked Engagement Intake admin-post actions
- browser/server file-count mismatch fails before inquiry creation
- REST and form responses include explicit no-store headers
- reconciliation is read-only
- manual cleanup requires capability, nonce, and typed confirmation

- request-level idempotency prevents concurrent duplicate inquiry creation
- successful response replay is limited to 15 minutes
- abandoned request locks are pruned after one hour


## v0.3.2 quarantine operations controls

- dedicated scanner-management, bulk-file-action, and file-audit capabilities
- generated benign scanner test contains no submitted user content
- readiness requires configured probe, clean result, successful deletion, freshness, and provider/version match
- scanner exceptions and unknown statuses fail to `error`
- clean-required mode cannot be newly enabled without readiness
- already-enabled clean-required policy remains fail closed when readiness degrades
- administrative rescans verify storage and SHA-256 first
- infected rescans block download and attempt immediate physical deletion
- bulk selection is limited to 50 records
- bulk scanner retries use a separate configurable limit
- approval rechecks validation, scanner policy, storage, and integrity
- bulk physical deletion requires exact typed confirmation
- reconciliation orphans are never automatically deleted
- access audit CSV is capability- and nonce-protected
- CSV values beginning with spreadsheet formula characters are neutralized
- audit export never includes physical file contents
- isolation guidance remains necessary even after a clean scan
