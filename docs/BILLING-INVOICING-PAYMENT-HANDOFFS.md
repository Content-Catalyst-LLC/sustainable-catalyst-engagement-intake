# Billing, Invoicing, and Payment Handoffs

Sustainable Catalyst Contact and Engagement Platform v1.7.0 adds governed billing records to engagement operations without becoming a payment processor or storing payment instruments.

## Scope

- Engagement-linked billing profiles
- Versioned invoices and line items
- Human-reviewed invoice issue and void actions
- External HTTPS payment-provider handoffs
- Replay-safe provider status events
- Sender Portal invoice and payment-status views
- Privacy export, redaction, retention, diagnostics, and audit evidence
- Aggregate billing metrics for service operations

## Security boundary

The platform does **not** collect or store card numbers, CVV/CVC values, bank-account numbers, routing numbers, credentials, provider secrets, or payment tokens. External providers handle payment collection. Payment event metadata is bounded and rejected when it contains payment instruments, credentials, personal contact fields, email addresses, card-like number patterns, or IP addresses.

## Invoice lifecycle

`Draft → Internal Review → Approved to Issue → Issued → Partially Paid / Paid / Overdue / Disputed / Void`

Every governed transition requires typed human confirmation and an audit event. Issuing creates an immutable version snapshot and SHA-256 content hash.

## Sender Portal

Only issued sender-visible invoices appear. The projection is allowlisted and excludes billing contact details, internal notes, raw provider metadata, idempotency keys, actor identifiers, and administrative audit context.

## Payment handoffs

A handoff records the external provider, external reference, HTTPS checkout URL, bounded amount/currency, expiry, status, and safe metadata. The same idempotency key returns the original handoff. Provider event keys are hashed to prevent duplicate status application.

## Important boundary

Invoices and payment-status records are operational records, not tax, accounting, banking, or legal advice. External provider settlement must be reconciled by an authorized human before relying on the record.
