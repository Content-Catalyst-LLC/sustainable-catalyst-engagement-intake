# Migration v1.1.1 — Inquiry Persistence and Lifecycle Reliability Patch

This is a nondestructive patch release. It does not add, remove, rename, or truncate database tables or columns. The database schema version remains `1.1.0`.

## Runtime correction

New inquiry records now initialize `qualification_score` as integer `0`, matching the existing `smallint unsigned NOT NULL DEFAULT 0` database definition. A score of zero means qualification has not begun; the canonical state remains `qualification_status = not_started`.

## Verification

The upgrade records `v1_1_1_inquiry_persistence_lifecycle_reliability` only after the complete write-path contract verifies:

- required plugin tables
- required inquiry columns
- platform governance columns
- lifecycle tables and columns

The stored database version is not advanced when this contract is incomplete.

## Recovery

After installing v1.1.1:

1. Clear PHP opcode and application caches.
2. Open Contact & Engagement → Platform Overview.
3. Run database repair and the v1.1.1 persistence-patch repair if shown.
4. Resolve the historical failed live-validation event only after a new validation succeeds.
5. Run Live Validation again and confirm the temporary inquiry is created, transitioned, and removed.

No existing inquiries, lifecycle events, documents, portal records, proposals, or engagement records are modified by this patch.
