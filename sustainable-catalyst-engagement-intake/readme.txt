=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, intake, secure upload, quarantine, scanner readiness, file audit, microsoft teams, privacy
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private contact and consulting intake with secure document quarantine operations, scanner readiness, reliable protected storage, Microsoft Teams preferences, privacy tools, and audit history.

== Description ==

Version 0.3.2 adds the operational layer for private documents collected through the compact Consulting form and advanced Contact Hub.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]`

Quarantine Operations provides:

* Cross-inquiry private document queue
* Search by file, inquiry, contact, organization, or SHA-256
* Filters for quarantine, validation, scanner, storage, category, confidentiality, and retention state
* Scanner readiness status and generated benign test file
* Single-file and guarded bulk scanner retries
* Storage and integrity rechecks
* Approval, return-to-quarantine, replacement request, retention, and rejection controls
* Maximum 50 records per bulk operation
* Configurable scanner retry batch limit
* Exact confirmation before bulk physical deletion
* Storage utilization and free-space reporting
* Private document access and operations audit
* Filtered CSV audit export with spreadsheet-formula neutralization
* Isolation guidance for reviewing untrusted files
* Human-controlled decisions with no automatic approval

Scanner-required mode:

* Newly enabling it requires a configured scanner integration.
* A recent generated benign file must be reported clean.
* The test file must be deleted successfully.
* The configured scanner provider and integration version must still match the tested configuration.
* Once enabled, the policy remains fail-closed if readiness later degrades.
* New uploads not reported clean are rejected and deleted.

No antivirus engine is bundled. The readiness test verifies that a benign file can be submitted to the integration and reported clean; it does not prove that every malicious file will be detected.

Files remain outside the WordPress Media Library and receive no public URL.

== Installation ==

1. Back up the WordPress database and private storage directory.
2. Upload and activate v0.3.2.
3. Open Engagement Intake → Diagnostics.
4. Confirm database version 0.3.2 and scanner migration fields.
5. Run Storage Probe and Storage Reconciliation.
6. Open Engagement Intake → Quarantine.
7. Run the benign scanner readiness test when an external scanner is integrated.
8. Review the queue, access audit, storage utilization, and isolation guidance.
9. Enable clean-required mode only after readiness shows Ready.
10. Test compact and advanced forms with non-sensitive documents.

== Upgrade from 0.3.1 ==

The upgrade preserves all inquiry, attachment, storage, audit, Teams, privacy, retention, and conversion data.

The attachment table gains:

* scanner attempt count
* last scanner time
* last scanner actor
* index on last scanner time

Existing attachments remain in place. Scanner attempt metadata begins with the existing stored state until a new upload or administrative rescan occurs.

== Frequently Asked Questions ==

= Does the plugin include antivirus software? =

No. The plugin provides a strict structural validator and an integration bridge. Connect an external scanner through `sc_ei_scan_attachment` and `sc_ei_scanner_probe`.

= Does a clean readiness test guarantee the scanner catches malware? =

No. It confirms that the configured integration can receive a generated benign file, report it clean, and allow the temporary test file to be deleted. It is an operational readiness signal, not proof of detection quality.

= What happens when an administrative rescan reports infected? =

The result is stored, downloads remain blocked, and the plugin attempts to delete the physical file and mark the attachment rejected. If deletion fails, the infected status remains visible and immediate administrative action is required.

= Can bulk actions approve documents automatically? =

No. An authorized human must select the documents and choose the action. Approval still requires validated content, acceptable scanner policy, and a current healthy storage and integrity check.

= Are orphan files automatically deleted? =

No. Reconciliation remains read-only. Orphan files are reported for investigation.

= What is included in the CSV audit export? =

Event metadata, inquiry reference, private document name, actor, message, and sanitized context. Physical file contents are never exported.

== Changelog ==

= 0.3.2 =
* Added cross-inquiry Quarantine Operations.
* Added scanner readiness testing, retry operations, clean-mode safeguards, guarded bulk controls, storage utilization, access reporting, CSV export, and isolation guidance.

= 0.3.1 =
* Added atomic storage commits, reconciliation, integrity tracking, retention previews, and upload reliability.

= 0.3.0 =
* Added secure document intake and quarantine.

= 0.2.2 =
* Added compact Consulting and advanced Contact intake experiences with conversion routing.
