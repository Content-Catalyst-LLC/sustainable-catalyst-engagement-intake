# Privacy and Retention Center

## Purpose

Provide a human-controlled lifecycle workspace for private engagement intake data.

The Center supports:

```text
inventory
requests
consent
restriction
legal holds
policies
preview
queue
approval
execution
verification
audit
```

## Operating principles

1. Minimize data collection.
2. Record the notice, authorization, source, and time.
3. Verify identity before disclosure or erasure.
4. Preserve records subject to a valid hold.
5. Preview before queueing.
6. Queue before approval.
7. Approve before execution.
8. Recheck holds immediately before execution.
9. Verify deletion or redaction.
10. Retain only a non-personal tombstone and audit evidence.

## Daily cron

Hook:

```text
sc_ei_cleanup_expired_attachments
```

The legacy hook name remains for compatibility.

Its v0.6.0 behavior is:

```text
scan candidates
→ create deduplicated queue actions
→ record report
```

It does not call protected-storage deletion or attachment tombstone APIs.

## Privacy states

```text
active
restricted
erasure_requested
erased
archived
```

The erased state cannot be selected through ordinary privacy-state editing. Only verified erasure execution writes it.

## Request handling

Before disclosing or erasing:

- confirm requester identity or record why verification was waived
- identify all matching inquiry records
- review active legal holds
- review private documents
- review existing retention actions
- document the response and result

## Consent ledger

Do not use a consent event to imply a legal conclusion.

Record:

- the exact authorization type
- action
- version
- source
- basis
- evidence
- occurrence time

A withdrawal event restricts processing for review.

## Legal holds

Use a hold when records must be preserved for:

- counsel instruction
- dispute
- investigation
- audit
- contract
- insurance
- regulatory response
- other documented preservation need

Review holds periodically.

Do not release a hold merely because a normal retention date arrived.

## Policy versions

A policy change creates a new version.

Do not rewrite old versions because queued actions and historical decisions must remain explainable.

## Execution checklist

### Before approving

- verify target
- verify policy and version
- verify due date
- verify request or operational rationale
- verify holds
- verify dependencies
- verify proposed action is proportionate

### Before executing

- recheck holds
- recheck target still exists
- recheck action remains approved
- type the exact execution phrase
- verify storage and database health
- confirm backup and recovery posture where appropriate

### After executing

- review verification result
- review audit entry
- resolve related privacy request
- inspect failed or blocked dependencies
- do not disclose erased content in follow-up communication

## Failure handling

A failed action remains visible.

Do not repeatedly retry destructive operations without understanding:

- storage permission failure
- path mismatch
- missing target
- database update failure
- legal-hold conflict
- undeleted document dependency
- transaction failure

## Tombstones

Tombstones may preserve:

- reference
- categorical status
- action type and state
- policy key and version
- timestamps
- internal actor IDs
- checksums and sizes where appropriate
- audit evidence

They should not preserve:

- names
- email addresses
- phone numbers
- Teams addresses or URLs
- message bodies
- project narratives
- accessibility details
- participant addresses
- private document files
- request narratives
- consent evidence
- hold narratives
- transport error narratives
