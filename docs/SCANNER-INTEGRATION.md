# External Scanner Integration

## Boundary

Engagement Intake does not bundle an antivirus engine.

The plugin provides:

- protected file path
- validated metadata
- scanner result schema
- readiness probe
- benign readiness test
- retry operations
- clean-required policy
- audit history

The external integration is responsible for scanning quality, availability, authentication, rate limits, privacy, data residency, and incident response.

## Scan filter

```php
add_filter(
    'sc_ei_scan_attachment',
    function( array $default, string $absolute_path, array $metadata ): array {
        // Call the scanner using the private local file.
        return array(
            'status'   => 'clean',
            'provider' => 'clamav-local',
            'message'  => 'No threat detected.',
        );
    },
    10,
    3
);
```

Allowed statuses:

```text
clean
infected
error
skipped
not_configured
```

Unknown statuses are converted to `error`.

Exceptions are caught and converted to an `integration_exception` error.

## Probe filter

```php
add_filter(
    'sc_ei_scanner_probe',
    function(): array {
        return array(
            'configured'          => true,
            'provider'            => 'clamav-local',
            'message'             => 'Daemon reachable.',
            'integration_version' => '1.2.0',
            'supports_test_file'  => true,
        );
    }
);
```

Use a stable provider identifier. Change the integration version when behavior, scanner routing, or policy materially changes. A provider/version change invalidates the previous readiness test.

## Metadata

Administrative retry metadata can include:

```text
attachment_id
inquiry_id
original_name
mime_type
detected_mime
extension
size_bytes
sha256
quarantine_status
operation_source
request_mode
```

The readiness test includes:

```text
test_mode = scanner_readiness
contains_user_data = no
generated_by = engagement-intake-v0.8.1
```

## Readiness test

The test:

1. creates a generated benign TXT file
2. sends it through `sc_ei_scan_attachment`
3. requires `clean`
4. deletes the generated file
5. stores provider, version, result, message, actor, and time
6. records an audit event

The test does not use EICAR or another malware signature. It verifies clean-path integration readiness without intentionally creating malware-test content.

## Clean-required mode

New activation requires a recent clean test.

When active:

- `clean` uploads are retained in quarantine
- `infected` uploads are deleted and rejected
- `error`, `skipped`, and `not_configured` uploads are deleted and rejected

If readiness later expires, new uploads still fail closed until the scanner is restored or the setting is deliberately disabled.

## Operational recommendations

- Prefer a local or private-network scanner when possible.
- Avoid sending confidential files to third parties without a reviewed data-processing agreement.
- Set explicit scanner timeouts.
- Return `error` rather than `clean` when the scanner response is ambiguous.
- Keep the bulk retry limit conservative.
- Monitor scanner service logs separately from WordPress.
- Test clean, error, timeout, and infected-result handling in staging.
- Do not log document content.
