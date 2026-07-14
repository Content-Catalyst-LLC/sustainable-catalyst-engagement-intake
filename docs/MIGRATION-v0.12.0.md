# Migration to v0.12.0

## Versions

```text
SC_EI_VERSION = 0.12.0
SC_EI_DB_VERSION = 0.12.0
SC_EI_WORKFLOW_CORE_SCHEMA_VERSION = 1.0.0
```

Inherited schemas remain unchanged.

## New tables

```text
{prefix}sc_ei_workflow_cases
{prefix}sc_ei_workflow_commands
{prefix}sc_ei_workflow_handoffs
{prefix}sc_ei_workflow_outbox
```

## New options

```text
sc_ei_workflow_core_schema_version
sc_ei_workflow_core_last_sync
sc_ei_workflow_core_last_outbox
```

## New schedules

```text
sc_ei_workflow_core_sync
sc_ei_workflow_core_outbox
sc_ei_workflow_core_sync_inquiry
```

## New capabilities

```text
sc_intake_view_workflow_core
sc_intake_manage_workflow_core
sc_intake_prepare_workflow_handoffs
sc_intake_dispatch_workflow_outbox
sc_intake_acknowledge_workflow_handoffs
sc_intake_export_workflow_core
sc_intake_export_workflow_core_private
```

## Migration behavior

The database upgrade creates empty Workflow Core tables. It does not alter existing inquiry, review, fit, proposal, contract, engagement, Graph, portal, or privacy records.

Run `SYNC WORKFLOW CORE` after upgrade to build canonical projections.

## Rollback

Before returning temporarily to v0.11.0:

1. disable Workflow Core synchronization and outbox processing
2. document pending or processing outbox rows
3. preserve all four Workflow Core tables
4. preserve handoff signatures and payloads
5. understand that v0.11.0 will not display Workflow Core state
6. do not delete the tables merely to restore the earlier interface
