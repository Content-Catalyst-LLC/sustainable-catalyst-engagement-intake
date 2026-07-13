=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, intake, secure upload, quarantine, storage diagnostics, microsoft teams, privacy
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dual private intake experiences with reliable protected document quarantine, storage reconciliation, validated downloads, Microsoft Teams scheduling readiness, conversion routing, privacy tools, and audit history.

== Description ==

Version 0.3.1 is the production reliability patch for the secure document pipeline introduced in v0.3.0.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" title="Contact Sustainable Catalyst"]`

Reliability improvements include:

* Atomic staging and final file commit
* Size and SHA-256 verification after the server move
* Final verification after atomic rename
* Storage-path lock only after the first committed document
* Storage write/read/rename/delete probe
* Protection-file and directory repair
* Stale partial-upload cleanup
* Database-to-filesystem reconciliation
* Missing, altered, size-mismatched, misplaced, unresolvable, and orphan-file reporting
* Per-document manual integrity verification
* Persistent storage-status and last-verification metadata
* PHP `post_max_size` overrun interception
* Browser-selected versus server-received file-count comparison
* Server upload-temporary-directory checks
* Effective limits derived from plugin and PHP settings
* Browser, proxy, CDN, Cloudflare, and surrogate no-store headers
* Client-side three-minute upload timeout with duplicate-submission guidance
* Retention cleanup preview
* Guarded manual retention execution
* Storage utilization and free-space diagnostics
* Mobile file-input improvements

Files are never added to the WordPress Media Library and receive no public URL.

== Installation ==

1. Upload and activate the plugin.
2. Open Engagement Intake → Diagnostics.
3. Confirm database version 0.3.1.
4. Confirm the new attachment verification fields.
5. Run Storage Probe.
6. Run Storage Reconciliation.
7. Preview Expired Cleanup.
8. Confirm Fileinfo and ZipArchive when required formats are enabled.
9. Exclude form and submission routes from full-page caching.

== Upgrade from 0.3.0 ==

The upgrade preserves inquiry, attachment, Teams, conversion, privacy, and audit records.

The attachment table gains:

* storage status
* last verification time
* last verifying user
* verification source
* verification message

No physical files are moved during database migration.

== Frequently Asked Questions ==

= Does v0.3.1 automatically delete orphan files? =

No. Reconciliation is read-only. It reports orphan files for human review.

= What does Repair Storage Protections change? =

It recreates known protection files, reapplies best-effort directory permissions, removes staging files older than one hour, and runs the storage probe. It does not delete committed `.qtn` files or attachment records.

= What happens when a request exceeds post_max_size? =

The plugin detects the overrun on its marked admin-post route or REST route and returns a clear request-too-large response instead of silently accepting an empty request.

= Can retention cleanup be tested without deletion? =

Yes. The preview lists expired records, bytes, and whether each physical file is present. Manual deletion requires the deletion capability, a nonce, and the exact phrase `DELETE EXPIRED`.

= Does this release include antivirus? =

No. Strict validation remains separate from antivirus. An external scanner can connect through the documented scanner filters.

== Changelog ==

= 0.3.1 =
* Added production storage and upload reliability controls.
* Added atomic commits, reconciliation, integrity rechecks, request-envelope diagnostics, retention previews, guarded cleanup, and cache/CDN bypass hardening.

= 0.3.0 =
* Added protected document intake and quarantine.

= 0.2.2 =
* Added compact Consulting and advanced Contact intake experiences with conversion routing.

= 0.2.1 =
* Added Microsoft Teams communication preferences and scheduling readiness.
