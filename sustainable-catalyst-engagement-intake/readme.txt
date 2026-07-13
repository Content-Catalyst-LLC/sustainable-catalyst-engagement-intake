=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, inquiry, consulting, workflow, privacy, audit
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private inquiry records and administrative workflow foundation for the Sustainable Catalyst contact and advisory system.

== Description ==

Version 0.1.0 establishes the private data and permissions layer for a future adaptive Contact Hub.

Included:

* Dedicated private inquiry table
* Dedicated attachment metadata table
* Dedicated audit history table
* Twelve-status review workflow
* Ten inquiry categories
* Administrator, manager, and reviewer capabilities
* Private admin list and detail screens
* Status changes and private internal notes
* Authenticated REST diagnostics and inquiry reads
* WordPress privacy exporter and eraser
* Retention defaults and uninstall controls
* Administration diagnostics
* No public inquiry archive
* No public submission endpoint in this release

Secure physical document upload is scheduled for v0.3.0. The attachment metadata foundation is installed now, but v0.1.0 does not accept public files.

== Installation ==

1. Upload and activate the plugin.
2. Open Engagement Intake in WordPress administration.
3. Review Diagnostics and Settings.
4. Assign the Engagement Reviewer or Engagement Manager role only to trusted users.

== Frequently Asked Questions ==

= Does v0.1.0 replace the Contact form? =

Not yet. This release establishes private records, roles, administration, privacy, and audit foundations. Adaptive public forms arrive in v0.2.0.

= Can visitors upload documents? =

Not in v0.1.0. Secure upload quarantine, validation, protected storage, and download controls are planned for v0.3.0.

= Are inquiry records public? =

No. There is no public archive, query, or unauthenticated REST endpoint.

= Does deactivation delete data? =

No. Deactivation preserves all records. Uninstall deletes data only when the administrator explicitly enables the uninstall deletion setting.

== Changelog ==

= 0.1.0 =
* Initial private inquiry records and plugin foundation.
