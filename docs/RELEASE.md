# Release Notes — v0.3.2

## Purpose

Provide one private operational surface for reviewing all active documents, testing scanner readiness, retrying scans, managing quarantine state and retention, auditing file access, and following safer isolation practices.

## Administrative workspace

`Engagement Intake → Quarantine`

### Quarantine Queue

The queue is cross-inquiry and displays:

- file name, extension, size, and SHA-256 prefix
- inquiry reference, contact, and organization
- document category and confidentiality
- quarantine status
- scanner status, provider, attempts, last scan, and message
- storage and integrity status
- retention date
- download count and latest access
- upload time

### Scanner Readiness

The readiness card combines:

- integration probe
- provider
- optional integration version
- generated benign test result
- test freshness
- test-file deletion confirmation
- clean-required mode state

A provider or integration-version change invalidates the prior readiness test.

### Access and Operations Audit

The report includes file events, actor, date, file, inquiry, message, and selected context. A filtered CSV export is available to authorized users.

### Isolation Guidance

The guidance is operational, not merely informational. It establishes that quarantined and non-clean files should be reviewed on an isolated endpoint with macros and remote content disabled.

## Bulk safety

Bulk operations require:

- `sc_intake_bulk_file_actions`
- a per-action capability
- WordPress nonce
- selected attachment IDs
- maximum 50 selected records
- configurable scanner retry cap
- current per-file state checks
- exact confirmation before physical deletion
- aggregate and per-file audit details

No bulk action silently approves documents.

## Scanner policy

The built-in validator is not antivirus.

New clean-required mode activation is blocked unless:

1. the integration probe reports configured
2. a generated benign test was run recently
3. the test was reported clean
4. the generated file was deleted
5. the current provider still matches
6. the current integration version still matches when supplied

The test verifies the clean operational path. It does not measure malware detection accuracy.

## Infected files

When an administrative rescan reports infected:

1. scan status and provider are stored
2. download remains blocked
3. the plugin attempts physical deletion
4. successful deletion marks the attachment rejected
5. failed deletion leaves the infected state visible for immediate intervention

## Migration

v0.3.2 adds scanner attempt and last-scan metadata. It does not move physical files or rewrite existing quarantine decisions.

## Recommended deployment sequence

1. Back up database and private storage.
2. Upgrade to v0.3.2.
3. Confirm database migration in Diagnostics.
4. Run Storage Probe.
5. Run Storage Reconciliation.
6. Open Quarantine Operations.
7. Review scanner and storage attention queues.
8. Integrate the external scanner.
9. Run the benign readiness test.
10. Enable clean-required mode only when Ready.
11. Test a clean non-sensitive document.
12. Test scanner error handling in a staging environment.
13. Review access audit and CSV export.
14. Confirm retention and bulk deletion permissions are limited to appropriate administrators.
