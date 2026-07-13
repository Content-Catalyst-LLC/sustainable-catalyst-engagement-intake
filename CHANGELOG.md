# Changelog

## 0.2.0 — Adaptive Contact Hub and Conditional Forms

- Added `[sc_contact_hub]`.
- Added `[sc_contact_form mode="general"]`.
- Added `[sc_engagement_inquiry mode="consulting"]`.
- Added ten public inquiry routes.
- Added conditional engagement, timeline, stakeholder, materials, and media fields.
- Added three-step accessible form flow.
- Added review-before-submit.
- Added AJAX submission through a public write-only REST route.
- Added non-JavaScript `admin-post.php` fallback with all conditional fields exposed safely.
- Added confirmation references.
- Added server-side conditional validation.
- Added nonce validation.
- Added signed form timing.
- Added honeypot protection.
- Added email-based hourly rate limiting.
- Added duplicate-submission suppression.
- Added privacy and sharing-authorization consent.
- Added public form settings and diagnostics.
- Added dynamic no-cache protection for nonce-bearing public forms.
- Preserved private tables, roles, audit history, privacy tools, and administration from v0.1.0.
- Kept physical file uploads disabled until protected storage and quarantine arrive in v0.3.0.

## 0.1.0 — Private Inquiry Records and Plugin Foundation

- Added dedicated inquiry, attachment metadata, and audit tables.
- Added private administration, roles, statuses, privacy tools, and diagnostics.
