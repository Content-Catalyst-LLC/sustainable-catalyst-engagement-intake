# Migration to v1.2.0

## Support Operations and Product Intelligence Integration

This is a **nondestructive** database migration. Existing inquiries, lifecycle records, documents, communications, Sender Portal records, meetings, proposals, engagements, privacy records, analytics, and reliability evidence are preserved.

The database version advances from `1.1.0` to `1.2.0` and adds four tables:

```text
{prefix}sc_ei_support_cases
{prefix}sc_ei_support_case_events
{prefix}sc_ei_support_case_links
{prefix}sc_ei_support_signals
```

The migration journal key is:

```text
v1_2_0_support_operations_product_intelligence
```

## Post-upgrade verification

1. Open **Contact & Engagement → Platform Overview**.
2. Run **Repair database contract** if the v1.2.0 support schema is incomplete.
3. Run **Verify v1.2.0 support migration** if its journal is missing.
4. Run **Repair scheduled jobs** to schedule the daily privacy-safe signal digest.
5. Open **Contact & Engagement → Support Cases**.
6. Add `[sc_support_request]` to the Support page or use `/contact/?engagement=support`.
7. Run Live Validation again. v1.1.1 evidence does not satisfy the v1.2.0 gate.
8. Record fresh backup, external mail, and pilot evidence for v1.2.0.

## Privacy boundary

Support cases may contain private diagnostic information and remain inside Contact and Engagement. Product-intelligence signals are aggregated, nonpersonal records. The typed handoff contract rejects sender identity, messages, files, credentials, contact details, and organization fields before signal storage.

## Rollback

Create a database and protected-storage backup before upgrading. Rolling the plugin code back without restoring the database leaves the new support tables in place but does not delete or rewrite existing records. Do not drop those tables unless a separately reviewed data-retention decision authorizes removal.
