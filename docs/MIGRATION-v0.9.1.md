# Migration to v0.9.1

## Versions

```text
SC_EI_VERSION = 0.9.1
SC_EI_DB_VERSION = 0.9.1
SC_EI_WORKFLOW_SCHEMA_VERSION = 1.1.0
SC_EI_GRAPH_SCHEMA_VERSION = 1.0.0
```

Portal schema remains:

```text
SC_EI_PORTAL_SCHEMA_VERSION = 1.2.0
```

## New table

```text
{prefix}sc_ei_graph_operations
```

## Expanded meeting-offer fields

v0.9.1 adds Graph state, transaction, event, calendar, join-link, request, error, retry, remote-time, and reconciliation fields.

Existing meeting records receive database defaults:

```text
graph_sync_status = not_requested
graph_attempt_count = 0
all remote identifiers empty
```

No remote event is created during migration.

## New capabilities

```text
sc_intake_view_graph
sc_intake_manage_graph_settings
sc_intake_create_graph_events
sc_intake_reconcile_graph_events
sc_intake_cancel_graph_events
sc_intake_export_graph_operations
```

## New schedules

```text
sc_ei_graph_catchup
sc_ei_graph_process_queue
```

The hourly catch-up recovers stale locks and processes due operations.

The single-event process hook runs near a scheduled retry.

## New options

```text
sc_ei_graph_schema_version
sc_ei_graph_credentials
sc_ei_graph_circuit
sc_ei_graph_last_health
```

`sc_ei_graph_credentials` contains only an encrypted envelope.

## Upgrade sequence

1. Back up database and protected storage.
2. Upgrade from v0.9.0.
3. Clear caches.
4. Confirm DB 0.9.1.
5. Confirm workflow schema 1.1.0.
6. Confirm Graph schema 1.0.0.
7. Confirm the Graph operation table.
8. Confirm all Graph meeting fields.
9. Confirm role capabilities.
10. Confirm hourly catch-up.
11. Leave Graph disabled initially.
12. Configure and scope the Entra application.
13. Save encrypted credentials.
14. Enable Graph.
15. Run the health test.
16. Test one staging event.
17. Verify manual fallback.

## Rollback

Before temporarily returning to v0.9.0:

1. disable Graph
2. let active operations finish or document them
3. preserve the Graph operation table
4. preserve Graph linkage fields
5. preserve encrypted credentials unless intentionally rotating or deleting
6. understand that v0.9.0 does not display Graph operation state
7. use manual Teams URL finalization

Do not drop Graph records merely to restore the earlier interface.
