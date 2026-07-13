# Migration to v0.3.0

## Database

`SC_EI_DB_VERSION` advances to `0.3.0`.

The existing attachment foundation table is expanded with:

- detected MIME
- signature type
- validator version
- document category
- document notes
- confidentiality
- scanner provider and message
- integrity status
- approval metadata
- rejection metadata
- replacement-request timestamp
- deletion actor
- download count
- last download timestamp

Existing inquiry, conversion-routing, Teams, privacy, and audit records are preserved.

## Roles

Existing Engagement Manager roles receive:

- `sc_intake_download_files`
- `sc_intake_release_files`
- `sc_intake_manage_file_retention`

Administrators receive all plugin capabilities.

Reviewers do not receive file-download or release capabilities by default.

## Storage

Activation creates protection infrastructure but does not lock the selected path.

The effective path is locked when the first accepted file is stored.

Before accepting a production upload:

1. Open Engagement Intake → Settings.
2. Configure an absolute path when automatic storage is not outside the document root.
3. Open Diagnostics.
4. Confirm the path, marker, protection files, Fileinfo, ZipArchive, and retention cron.
5. Test with a non-sensitive file.

## Cron

A daily event named `sc_ei_cleanup_expired_attachments` is scheduled.

Deactivation removes the scheduled event. Reactivation restores it.

## Rollback warning

Do not roll back to v0.2.2 after accepting files without preserving:

- the expanded attachment table
- the locked storage path option
- the physical private-storage directory

v0.2.2 cannot administer or delete v0.3.0 document files.
