# Architecture

## Separation of concerns

Engagement Intake is independent from Feature Suggestions.

- **Feature Suggestions:** public participation, product ideas, surveys, voting, and roadmap improvement.
- **Engagement Intake:** private contact, consulting, research, media, technical, and institutional inquiries.

## Storage

### `wp_sc_ei_inquiries`

Private contact and engagement metadata.

### `wp_sc_ei_attachments`

Attachment metadata and quarantine state. Physical protected storage arrives in v0.3.0.

### `wp_sc_ei_audit_log`

Append-oriented events for creation, status changes, notes, privacy actions, future file access, communication, and deletion.

## Access

No public post type, archive, feed, query, or unauthenticated REST endpoint is registered.

Access is controlled by dedicated capabilities:

- `sc_intake_view`
- `sc_intake_review`
- `sc_intake_download_files`
- `sc_intake_add_notes`
- `sc_intake_change_status`
- `sc_intake_communicate`
- `sc_intake_export`
- `sc_intake_delete`
- `sc_intake_manage_settings`

## Future extension

v0.2.0 adds conditional public forms.  
v0.3.0 adds protected document storage and quarantine.  
Later versions add communication history, retention automation, sender portal, scheduling, proposals, analytics, and Workflow Core.


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
