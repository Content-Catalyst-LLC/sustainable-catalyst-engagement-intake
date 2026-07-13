# Sustainable Catalyst Engagement Intake

**Version:** 0.3.1  
**Release:** Production Storage and Upload Reliability

v0.3.1 stabilizes the v0.3.0 secure-document pipeline without adding a new business workflow layer.

## Public forms

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
```

```text
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
```

## Reliable storage transaction

```text
Validate temporary file
→ move to randomized staging path
→ verify size and SHA-256
→ atomically rename to final .qtn path
→ verify committed size and SHA-256
→ lock effective storage base
→ create attachment metadata
```

A failed move, mismatch, commit, or final verification removes the staging or final file and returns a file-specific warning.

## Production diagnostics

`Engagement Intake → Diagnostics` now includes:

- storage path and permission checks
- write/read/rename/delete probe
- protection-file repair
- stale staging cleanup
- PHP upload envelope and effective limits
- cache and Cloudflare no-store headers
- database-to-filesystem reconciliation
- retention preview and guarded cleanup
- active database bytes versus managed filesystem bytes
- Fileinfo, ZipArchive, scanner, and cron status

## Reconciliation

The read-only reconciliation scan checks:

- active attachment record has a resolvable path
- physical file exists
- size matches
- SHA-256 matches
- approved/quarantine area matches status
- managed `.qtn` file has an active record

It records per-file verification state and stores a summarized report. It never deletes orphan files automatically.

## Request reliability

The form sends:

- generated request ID
- browser-selected file count
- browser-selected total bytes

The server detects:

- `post_max_size` overrun
- file-upload service disabled
- unavailable temporary directory
- browser-selected files truncated by the server
- per-file and aggregate request limits

The non-JavaScript form action carries a plugin-specific query marker so oversized-request interception does not affect unrelated WordPress admin-post actions.

## Cache resilience

Form pages and the REST submission response send:

```text
Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0
CDN-Cache-Control: no-store
Cloudflare-CDN-Cache-Control: no-store
Surrogate-Control: no-store
Vary: Cookie
```

Hosting or Cloudflare rules can override application headers. Exclude the form pages and submission routes explicitly.

## Retention safety

Preview:

```text
No deletion
Expired record count
Total bytes
Physical-file present/missing status
```

Manual cleanup:

```text
sc_intake_delete capability
Per-action nonce
Exact confirmation: DELETE EXPIRED
Run report and audit event
```

## Remaining boundary

The package is not a bundled antivirus engine, object-storage service, resumable uploader, or sender portal. Those remain separate roadmap items.

## Request idempotency

Each rendered form receives a signed-context request UUID. The browser prevents concurrent submissions, while the server uses an atomic option lock to reject a second in-flight copy. A successful response is retained for 15 minutes so a timeout retry with the same request ID returns the original reference rather than creating another inquiry.

Abandoned locks older than one hour are removed through a throttled maintenance pass.
