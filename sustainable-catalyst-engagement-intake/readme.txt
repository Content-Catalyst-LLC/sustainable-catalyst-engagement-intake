=== Sustainable Catalyst Engagement Intake ===
Contributors: content-catalyst
Tags: contact, consulting, intake, microsoft teams, scheduling, conversion, privacy, audit, forms
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dual private intake experiences for Sustainable Catalyst: a compact Consulting form and an advanced Contact Hub.

== Description ==

Version 0.2.2 introduces two deliberately different public experiences that write into one private inquiry, Teams, privacy, and audit system.

Recommended shortcodes:

* Consulting page: `[sc_engagement_inquiry mode="compact" source="consulting-page" title="Discuss an Engagement"]`
* Contact page: `[sc_contact_hub mode="advanced" source="contact-page" title="Contact Sustainable Catalyst"]`

The compact Consulting intake includes:

* Name, email, and organization
* Published consulting engagement choices
* Published-fee guidance
* Problem and desired outcome
* Budget range
* Desired start date
* Relevant public link
* Email-first or Microsoft Teams fit-call next step
* Conditional Teams email, time zone, availability, and calendar consent

The advanced Contact Hub includes:

* Ten inquiry routes
* Route-specific helper guidance
* Conditional consulting, technical, workshop, media, institutional, and Teams fields
* Three-step Route, Details, and Review flow
* Private confirmation reference

Both experiences record:

* Form variant
* Source page
* Entry CTA
* Conversion route
* Non-blocking guidance flags
* Referring form URL
* Microsoft Teams preferences and scheduling state
* Audit events

Guidance never approves, rejects, scores, or automatically schedules an inquiry.

== Installation ==

1. Upload and activate the plugin.
2. Open Engagement Intake → Diagnostics.
3. Confirm the v0.2.2 inquiry migration fields are present.
4. Add the compact shortcode to the Consulting page.
5. Add the advanced shortcode to the Contact page.
6. Exclude both pages from static full-page caching if the cache layer ignores WordPress no-cache headers.

== Frequently Asked Questions ==

= Which form belongs on the Consulting page? =

Use:

`[sc_engagement_inquiry mode="compact" source="consulting-page" title="Discuss an Engagement"]`

= Which form belongs on the Contact page? =

Use:

`[sc_contact_hub mode="advanced" source="contact-page" title="Contact Sustainable Catalyst"]`

= Does pricing guidance block submission? =

No. Guidance helps visitors choose a realistic starting point, but it never blocks submission or determines fit.

= Does the form automatically schedule Microsoft Teams meetings? =

No. Meeting requests remain pending until reviewed and approved.

= Can visitors upload documents? =

Not yet. Secure protected uploads arrive in v0.3.0.

== Changelog ==

= 0.2.2 =
* Added Compact Consulting Intake and Advanced Contact Hub modes.
* Added source attribution, entry CTA attribution, conversion routes, guidance flags, published-fee guidance, admin filters, and conversion event hooks.

= 0.2.1 =
* Added Microsoft Teams communication preferences and scheduling readiness.

= 0.2.0 =
* Added Adaptive Contact Hub and Conditional Forms.

= 0.1.0 =
* Added private inquiry records, roles, audit history, privacy tools, diagnostics, and administration.
