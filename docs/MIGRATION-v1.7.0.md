# Migration to v1.7.0

v1.7.0 is a nondestructive database migration.

## New tables

- `sc_ei_billing_profiles`
- `sc_ei_invoices`
- `sc_ei_invoice_items`
- `sc_ei_invoice_versions`
- `sc_ei_payment_handoffs`
- `sc_ei_billing_events`

The actual table prefix follows the WordPress installation.

## Version identity

- Plugin: `1.7.0`
- Database: `1.7.0`
- Platform evidence: `1.7.0`
- Portal schema: `1.8.0`
- Billing schema: `1.0.0`

Migration journal: `v1_7_0_billing_invoicing_payment_handoffs`.

## Upgrade procedure

1. Create database and protected-storage backups.
2. Replace the plugin with v1.7.0.
3. Clear WordPress, object, hosting, CDN, browser, and PHP opcode caches.
4. Open Platform Overview and repair the database contract when requested.
5. Verify the v1.7.0 billing migration journal.
6. Run Live Validation.
7. Create a controlled billing profile and invoice for a test engagement.
8. Verify Sender Portal isolation and external payment-handoff replay.
9. Re-record version-bound backup, inbox, validation, and pilot evidence.
