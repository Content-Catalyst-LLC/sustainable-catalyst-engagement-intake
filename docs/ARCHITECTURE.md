# Architecture

## Separation of concerns

Engagement Intake is independent from Feature Suggestions.

- **Feature Suggestions:** public participation, product ideas, surveys, voting, and roadmap improvement.
- **Engagement Intake:** private contact, consulting, research, media, technical, and institutional inquiries.

## Storage

### `wp_sc_ei_inquiries`

Private contact and engagement metadata.

### `wp_sc_ei_attachments`

Attachment metadata, quarantine and approval state, integrity fingerprints, storage verification state, retention metadata, and protected physical-file paths.

### `wp_sc_ei_audit_log`

Append-oriented events for creation, status changes, notes, privacy actions, future file access, communication, and deletion.

## Access

No public post type, archive, feed, query, or unauthenticated REST endpoint is registered.

Access is controlled by dedicated capabilities:

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

## Release layers

v0.2.x established conditional public forms, Teams preferences, dual experiences, and conversion routing.  
v0.3.0 added protected document storage and quarantine.  
v0.3.1 adds atomic storage commits, request-envelope reliability, reconciliation, integrity tracking, retention previews, and cache/CDN hardening.  
v0.3.2 adds a cross-inquiry quarantine operations layer, scanner readiness and retry, guarded bulk file actions, access reporting, and isolation guidance.  
Later versions add broader administrative review, communication history, sender portal, connected scheduling, proposals, analytics, and Workflow Core.


## Dual public experiences

The Consulting page and Contact page use separate public renderers but share:

- validation
- private inquiry persistence
- Teams scheduling fields
- privacy tools
- audit history
- administration
- retention settings

This prevents duplicated records and duplicated plugin maintenance.


## Quarantine operations layer

The operational layer remains private and capability-controlled.

```text
Attachment repository
→ cross-inquiry queue
→ scanner operations
→ storage and integrity verification
→ human quarantine decision
→ access and operations audit
```

It does not expose files, create Media Library records, or merge private inquiry data into public Feature Suggestions or Knowledge Library records.
