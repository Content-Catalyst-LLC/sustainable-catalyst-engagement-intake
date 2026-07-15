# Proposal Versioning, Approval, and Engagement Conversion Reliability

## Commit boundary

A sender proposal response is not canonical unless the proposal state and immutable approval receipt both persist. If receipt creation fails, the proposal state is restored and a protected reliability event is recorded.

## Replay behavior

Identical proposal and SOW actions return the existing immutable receipt. A conflicting replay is rejected. Proposal and SOW version creation use bounded retry for concurrent version-number allocation.

## Current-version rule

A SOW can be approved or sender-approved only when it belongs to the proposal's current published version. Publishing a new proposal version cannot silently retain stale sender-facing SOW approval.

## Engagement conversion

Conversion requires an externally contracted proposal and sender-approved SOW for the current version. Retrying conversion reuses the existing engagement and repairs missing conversion receipt or proposal status evidence rather than creating a duplicate engagement.

## Production evidence

Readiness checks invalid immutable hashes, missing sender/SOW/conversion receipts, stale active SOWs, converted-state mismatches, and patch migration evidence.
