=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, inquiry, consulting, workflow, privacy, audit, forms
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adaptive private contact and engagement intake for Sustainable Catalyst.

== Description ==

Version 0.2.0 adds an accessible public Contact Hub and conditional forms to the private inquiry foundation introduced in v0.1.0.

Shortcodes:

* `[sc_contact_hub]`
* `[sc_contact_form mode="general"]`
* `[sc_engagement_inquiry mode="consulting"]`

Included:

* Adaptive inquiry routing
* General and consulting modes
* Conditional engagement and media fields
* Three-step accessible form flow
* Review-before-submit
* JavaScript-enhanced REST submission
* Non-JavaScript admin-post fallback
* Private inquiry record creation
* Human-readable confirmation references
* Nonce, signed timing, honeypot, rate-limit, and duplicate controls
* Server-side conditional validation
* Privacy and authorization consent
* No public archive
* No document uploads until secure quarantine is added in v0.3.0

== Installation ==

1. Upload and activate the plugin.
2. Open Engagement Intake → Diagnostics.
3. Add `[sc_contact_hub]` to the Contact page.
4. Use the separate general or consulting shortcodes when a narrower form is preferred.

== Frequently Asked Questions ==

= Which shortcode should I use on the main Contact page? =

Use `[sc_contact_hub]`.

= Can I show only general inquiries? =

Use `[sc_contact_form mode="general"]`.

= Can I show only consulting and engagement inquiries? =

Use `[sc_engagement_inquiry mode="consulting"]`.

= Can visitors upload documents? =

Not yet. v0.2.0 explicitly blocks sensitive document submission and accepts public links only. Secure protected uploads arrive in v0.3.0.

= Are submissions private? =

Yes. They are stored in dedicated private tables and are not exposed through a public archive or unauthenticated read endpoint.

== Changelog ==

= 0.2.0 =
* Added Adaptive Contact Hub and Conditional Forms.
* Added public shortcodes, routing cards, conditional fields, review step, AJAX submission, fallback submission, confirmations, and anti-spam controls.

= 0.1.0 =
* Added private inquiry records, roles, audit history, privacy tools, diagnostics, and administration.
