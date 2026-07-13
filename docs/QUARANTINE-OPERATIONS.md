# Quarantine Operations

## Purpose

The Quarantine workspace is the private operational center for documents submitted through Engagement Intake.

It does not expose documents publicly and does not create WordPress Media Library records.

## Views

### Quarantine Queue

Use the queue to review active documents across all inquiries.

Recommended triage order:

1. infected
2. missing, hash mismatch, unresolvable, or size mismatch
3. scanner error or skipped
4. retention expired
5. replacement requested
6. not configured
7. clean and healthy
8. approved

### Access and Operations Audit

Use the audit to answer:

- who downloaded a file
- who verified integrity
- which scanner ran
- when quarantine state changed
- who changed retention
- who deleted or rejected a file
- when a scanner readiness test ran
- when a bulk action ran
- when an audit report was exported

### Isolation Guidance

Use the guidance as the minimum operating procedure for untrusted documents.

## Bulk actions

### Retry external scan

Requires:

```text
sc_intake_manage_scanner
```

The configured scanner bulk limit is applied even when more records are selected.

### Verify storage and integrity

Requires:

```text
sc_intake_download_files
```

Checks path containment, physical existence, expected area, size, and SHA-256.

### Approve for controlled use

Requires:

```text
sc_intake_release_files
```

Blocked when:

- validation is not `validated`
- scanner status is `infected`
- clean-required mode is active and status is not `clean`
- storage or SHA-256 verification fails

### Return to quarantine

Moves an approved file back to the protected quarantine area and records the status change.

### Request replacement

Moves the current file to quarantine when necessary and records a replacement-request state.

### Set retention date

Requires:

```text
sc_intake_manage_file_retention
```

The selected local date is converted to the end of that day in UTC using the WordPress site timezone.

### Reject and delete

Requires:

```text
sc_intake_delete
```

Also requires:

```text
REJECT SELECTED
```

The physical file must be deleted before metadata is marked rejected. Failed deletion is reported as a failed bulk action.

## Limits

```text
Maximum selected records: 50
Default scanner retry limit: 25
Maximum scanner retry setting: 50
Audit CSV maximum: 5,000 rows
```

## Human control

The queue is a decision-support and operations system.

It does not:

- automatically approve
- automatically classify business fit
- automatically disclose documents
- automatically delete reconciliation orphans
- override capability checks
- bypass scanner or integrity requirements
