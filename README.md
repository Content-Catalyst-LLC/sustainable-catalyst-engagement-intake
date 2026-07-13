# Sustainable Catalyst Engagement Intake

**Version:** 0.3.2  
**Release:** Quarantine Operations and Scanner Readiness

v0.3.2 turns the secure document intake introduced in v0.3.0 and stabilized in v0.3.1 into an operational administrative system.

## Public forms

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
```

```text
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
```

## Quarantine Operations

Open:

```text
Engagement Intake → Quarantine
```

The workspace contains:

1. **Quarantine Queue**
2. **Access and Operations Audit**
3. **Isolation Guidance**

### Queue filters

- quarantine status
- validation status
- scanner status
- storage and integrity status
- document category
- confidentiality classification
- retention state
- filename, SHA-256, inquiry, contact, or organization search

### Guarded bulk operations

- retry external scan
- verify storage and integrity
- approve for controlled use
- return to quarantine
- request replacement
- set retention date
- reject and delete physical files

Bulk processing is limited to 50 selected records. Scanner retries have a separately configurable limit. Bulk rejection requires the exact phrase:

```text
REJECT SELECTED
```

## Scanner readiness

No malware scanner is bundled.

The integration bridge remains:

```php
add_filter( 'sc_ei_scan_attachment', function( array $default, string $absolute_path, array $metadata ): array {
    return array(
        'status'   => 'clean', // clean, infected, error, skipped, not_configured
        'provider' => 'your-scanner',
        'message'  => 'Scan completed.',
    );
}, 10, 3 );
```

```php
add_filter( 'sc_ei_scanner_probe', function(): array {
    return array(
        'configured'         => true,
        'provider'           => 'your-scanner',
        'message'            => 'Scanner reachable.',
        'integration_version'=> '1.0.0',
        'supports_test_file' => true,
    );
} );
```

### Clean-required activation gate

Newly enabling clean-required mode requires:

- configured probe
- recent scanner readiness test
- test result `clean`
- generated test file deleted
- current provider matches tested provider
- current integration version matches tested version when both are supplied

The readiness test uses a generated benign TXT file containing no submitted user data. It verifies the clean path through the integration. It does not prove malware-detection coverage.

When clean-required mode is enabled, any new upload not reported `clean` is deleted and rejected. If readiness later expires or the integration fails, the policy remains fail-closed until an administrator restores readiness or deliberately disables the setting.

## Administrative rescanning

A private document rescan:

1. verifies storage existence, size, path area, and SHA-256
2. calls the external scanner
3. records provider, message, attempt count, time, and actor
4. records an audit event
5. deletes and rejects a file reported infected
6. leaves an infected file blocked and visible when physical deletion fails

## Access and operations audit

The report includes:

- quarantine intake
- scanner-policy rejection
- authorized download
- download blocked by integrity mismatch
- integrity checks
- scanner retries
- quarantine status changes
- retention changes
- deletion
- bulk operations
- scanner readiness tests
- storage reconciliation
- audit export

Filtered CSV export is capped at 5,000 events and neutralizes values beginning with `=`, `+`, `-`, or `@` to reduce spreadsheet formula execution risk.

## Isolation guidance

The plugin advises administrators to:

- treat every non-clean or failed scan as untrusted
- verify storage and SHA-256 before download
- use a patched isolated workstation or disposable virtual machine
- keep macros, external templates, and automatic links disabled
- avoid privileged sign-ins and synchronized public folders
- approve only necessary documents with understood provenance
- request replacement or delete unsafe and unnecessary files

## Storage and reliability foundation

v0.3.2 retains:

- atomic protected storage commit
- post-move size and SHA-256 checks
- request idempotency
- request-size diagnostics
- storage probe and repair
- read-only reconciliation
- retention preview and guarded cleanup
- browser, CDN, and Cloudflare no-store headers
- no Media Library attachment
- no public file URL

## Operational boundary

A structurally valid file and a clean scanner result reduce risk but do not prove a document is safe. External scanner quality, endpoint isolation, human review, hosting controls, and incident response remain operational responsibilities.
