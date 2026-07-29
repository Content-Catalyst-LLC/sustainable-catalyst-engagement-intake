# Migration v2.0.2

## Purpose

Repair Bluehost MySQL table creation failures caused by the unquoted reserved identifier `schema`.

## Recovery scope

The upgrade creates these tables if missing before normal WordPress `dbDelta()` reconciliation:

- `sc_ei_proposal_approvals`
- `sc_ei_service_intelligence_findings`
- `sc_ei_payment_handoffs`
- `sc_ei_platform_handoffs`

The site-specific WordPress database prefix is applied at runtime. Existing tables and records are not dropped or replaced.

## Safety

Activation remains fail-closed if any recovery-critical table cannot be created. Column probes do not run against absent tables, and a five-minute migration lock prevents overlapping retries.
