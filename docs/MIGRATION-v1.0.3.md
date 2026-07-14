# Migration to v1.0.3

## Scope

v1.0.3 is a nondestructive public-launch hardening release. It introduces option-backed launch evidence and runtime validation but does not add or alter database tables or columns.

## Version state

- Plugin: `1.0.3`
- Database: `1.0.0`
- Platform evidence schema: `1.0.2`
- Migration key: `v1_0_3_pilot_findings_public_launch_hardening`

## Preserved data

The upgrade preserves inquiries, documents, Sender Portal records, messages, review records, fit assessments, Teams records, proposals, engagements, analytics, reliability events, Workflow Core state, settings, and historical migration journals.

## New option-backed evidence

- `sc_ei_platform_pilot_evidence`
- `sc_ei_platform_external_mail_evidence`

The options are removed by full uninstall. Normal deactivation or upgrade does not remove them.

## Upgrade procedure

1. Back up the WordPress database and protected storage.
2. Install v1.0.3 over v1.0.2.
3. Clear all relevant caches.
4. Open Platform Overview and verify the v1.0.3 journal.
5. Run guided repairs if required.
6. Run Live Validation.
7. Record external inbox evidence, pilot evidence, and current backup evidence.
8. Resolve operational blockers before Production.

## Rollback

A code rollback to v1.0.2 does not require a database rollback because v1.0.3 does not change the database schema. Preserve a database and protected-storage backup before any rollback. v1.0.2 will ignore the new v1.0.3 evidence options.
