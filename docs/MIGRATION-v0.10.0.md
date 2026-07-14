# Migration to v0.10.0

Versions:

- Plugin and database: 0.10.0
- Portal schema: 1.3.0
- Workflow schema: 1.1.0
- Graph schema: 1.0.0
- Engagement schema: 1.0.0
- Analytics schema: 1.0.0

New table:

`{prefix}sc_ei_analytics_snapshots`

New capabilities:

- `sc_intake_view_analytics`
- `sc_intake_manage_analytics`
- `sc_intake_export_analytics`

New daily schedule:

`sc_ei_analytics_daily_snapshot`

Upgrade by backing up the database, installing v0.10.0, clearing caches, opening Diagnostics, confirming the analytics table and schedule, then reviewing the dashboard with a staging dataset.
