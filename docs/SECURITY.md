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


## v0.4.0 administrative review controls

- human-authored review categories; no automated fit score
- explicit inquiry status separate from fit decision and recommended next step
- optimistic `review_version` check prevents silent concurrent overwrite
- current review update and immutable snapshot insertion occur in one database transaction
- stale saves roll back
- assignment validates that the selected user can review inquiries
- reviewer editing can be restricted to the assigned reviewer
- managers retain explicit reassignment authority
- completion can require the complete checklist
- fit decisions, escalation, and completion can require a rationale
- completion requires an explicit fit decision and non-default next step
- active escalation requires a reason
- bulk review actions require a dedicated capability and nonce
- bulk operations remain capped at 50 inquiries
- each bulk item still passes normal review validation
- private review packet export requires a dedicated capability and nonce
- review packet export excludes physical document contents
- privacy export includes review fields and snapshots
- privacy erasure clears current and historical review narratives
- categorical review history is retained for accountability
- unsaved-change warning reduces accidental browser navigation loss
- review metrics are operational signals and never trigger status changes


## v0.5.0 communication and notification controls

- automated notification settings default to disabled
- automation cannot be enabled with an invalid sender name, sender email, or reply-to email
- all plugin email is plain text
- file attachments are unsupported
- drafts use optimistic row-version locking
- saving a draft cannot send it
- manual send requires a separate capability, nonce, and explicit confirmation
- short-lived send locks reduce concurrent duplicate sends
- failed transport attempts remain visible and auditable
- transport acceptance is never labeled delivery
- accepted and externally recorded history is immutable through normal editing
- automated notifications use unique deduplication keys
- reminder cron uses a stale-recoverable lock and a configurable batch limit
- sender-facing email respects per-inquiry do-not-email suppression
- a suppression reason is required
- template variables are allowlisted
- unknown variables are rejected
- template versions are immutable
- communication CSV export requires a dedicated capability and nonce
- spreadsheet formula-leading values are neutralized
- communication REST history requires an authenticated communication capability
- privacy export includes communication content and events
- privacy erasure redacts message bodies, parties, transport IDs, errors, hashes, dedupe keys, and event context
- internal and sender notification recipients are sanitized and limited
- no mailbox, Microsoft Graph, Zoom, or Google Meet integration is implied


## v0.6.0 privacy and retention controls

- daily retention automation is queue-only
- WordPress personal-data erasure is queue-only
- approval before execution is mandatory
- proposal and approval can be separated
- execution requires a typed action-specific phrase
- legal holds are checked at queue, approval, and execution
- any related active hold blocks inquiry erasure
- private documents must be deleted before inquiry erasure
- protected-file deletion verifies physical absence
- attachment database tombstones are preserved
- inquiry erasure uses a database transaction
- communication event context is redacted with message content
- consent evidence and subject hashes are redacted
- privacy-request identifiers and narratives are redacted
- released-hold narratives and authorities are redacted
- lifecycle snapshots and failure narratives are redacted
- ordinary privacy-state editing cannot set erased
- sender-facing email is blocked for restricted, erasure-requested, and erased inquiries
- lifecycle exports require a dedicated capability and nonce
- policy changes create versions instead of overwriting history
- retention actions use unique deduplication keys
- failed actions remain visible and auditable
- non-personal tombstones cannot be disabled in v0.6.0


## v0.7.0 fit assessment controls

- fit records are private and capability-gated
- reviewers and managers have separate capabilities
- finalization and Review Workspace application are separate
- no automatic inquiry-status mutation
- no communication or scheduling path
- no score thresholds
- no score-derived recommendation
- criterion evidence can be required
- material concern notes are required
- human attestation is fixed on
- assistance must be disclosed
- drafts use optimistic locking
- assessor ownership is enforced
- a distinct second reviewer can be required
- Agree cannot change the submitted conclusion
- post-submission edits invalidate prior review clearance
- JSON export requires capability and nonce
- REST fit context requires capability
- privacy export includes assessment history
- approved erasure redacts narratives, evidence, references, and reviewer disclosures
- categorical lifecycle and audit tombstones may remain


## v0.8.0 sender portal controls
- no WordPress sender account
- no public inquiry lookup
- HMAC invitation and session hashes
- single-use invitation
- email activation challenge
- activation lockout
- absolute and idle session expiry
- maximum active sessions
- HttpOnly SameSite Strict cookie
- browser binding
- hashed IP-change evidence
- session-derived CSRF
- per-session rate limits
- no-store and noindex
- no referrer
- frame denial
- explicit communication publication
- no portal file-download endpoint
- quarantine-only follow-up uploads
- privacy-state feature blocking
- typed withdrawal and access revocation
- portal privacy export and erasure


## v0.8.1 authentication and recovery controls

- atomic invitation activation
- rollback preserves invitation
- optimistic access and inquiry locking
- token verification before email lockout
- wrong token cannot increment lockout
- verified-token email failures can lock
- `__Host-` production cookie
- legacy cookie migration
- HTTPS-required production authentication
- generic recovery response
- no public record enumeration
- matched and unmatched shared rate limit
- recovery honeypot
- pending-request deduplication
- recovery expiry
- human approval or decline
- typed recovery and unlock confirmations
- no automatic invitation email
- recovery privacy export and erasure


## v0.9.0 Teams and proposal controls

- Microsoft Teams only
- no Microsoft Graph booking
- no automatic calendar event
- no automatic workflow email
- capability-gated draft creation
- separate publication capabilities
- typed publication and finalization
- validated Teams URLs
- portal CSRF and permission checks
- inquiry ownership checks
- privacy-state response blocking
- optimistic status and row-version updates
- authenticated ICS
- authenticated no-store proposal print
- immutable proposal versions
- SHA-256 proposal hashes
- separate published and pending version pointers
- typed sender acceptance and decline
- authority attestation
- non-contract acknowledgment
- no electronic signature
- no payment collection
- human external-contract attestation
- workflow privacy export and erasure


## v0.9.1 Microsoft Graph controls

- application-only client credentials
- Microsoft Graph v1.0 only
- global cloud only
- Calendars.ReadWrite only
- Exchange Application RBAC recommended
- authenticated encrypted credential vault
- authenticated encrypted access-token cache
- client secret never redisplayed
- credential fingerprint and expiry
- persistent Graph transaction ID
- encrypted operation payload
- local idempotency key
- SHA-256 request hash
- optimistic queue claims
- stale-lock recovery
- Retry-After
- bounded exponential backoff with jitter
- circuit breaker
- one-time 401 token refresh
- request ID correlation
- human-triggered event creation
- local-state eligibility check
- Teams join URL validation
- closed-meeting resurrection prevention
- explicit remote cancellation
- same-operation manual retry
- manual Teams fallback
- redacted export
- privacy erasure integration
- no contract, signature, invoice, payment, or engagement automation
