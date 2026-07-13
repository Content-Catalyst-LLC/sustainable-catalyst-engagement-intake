# Migration to v0.7.0

## Versions

```text
SC_EI_VERSION = 0.7.0
SC_EI_DB_VERSION = 0.7.0
SC_EI_FIT_SCHEMA_VERSION = 1.0.0
```

## New tables

```text
{prefix}sc_ei_fit_assessments
{prefix}sc_ei_fit_assessment_items
{prefix}sc_ei_fit_assessment_reviews
```

## New inquiry fields

```text
fit_assessment_status
current_fit_assessment_id
fit_assessment_updated_at
fit_assessment_finalized_at
fit_assessment_version
```

## Backfill

Existing inquiries receive:

```text
fit_assessment_status = not_started
fit_assessment_version = 0
```

No assessment is created.

No score is calculated.

No recommendation is inferred.

## New capabilities

```text
sc_intake_view_fit_assessments
sc_intake_create_fit_assessments
sc_intake_review_fit_assessments
sc_intake_finalize_fit_assessments
sc_intake_apply_fit_to_review
sc_intake_manage_fit_settings
sc_intake_export_fit_assessments
```

## Role migration

Engagement Reviewers receive:

- view assessments
- create and edit assessments
- record independent reviews
- export assessments

They do not receive finalization or Review Workspace application by default.

Engagement Managers receive all fit capabilities.

Administrators receive all fit capabilities.

## Preserved records

The migration preserves:

- all inquiries
- review history
- communications
- private documents
- quarantine and scanner state
- privacy requests
- consent events
- legal holds
- retention policies and actions
- audit history
- settings
- protected storage

## Non-actions

Activation does not:

- create assessments
- assign assessors
- calculate scores
- recommend services
- request second reviews
- finalize assessments
- apply conclusions
- change inquiry status
- send communication
- schedule meetings
- create proposals
- create referrals

## Upgrade sequence

1. Back up database and protected storage.
2. Upgrade to v0.7.0.
3. Confirm database version 0.7.0.
4. Confirm fit schema 1.0.0.
5. Confirm three new tables.
6. Confirm five inquiry fields.
7. Confirm fit capabilities by role.
8. Review Fit Assessment settings.
9. Open an existing inquiry.
10. Create an assessment draft.
11. Test save and reload.
12. Test concurrent edits.
13. Test submission validation.
14. Test a triggered second review.
15. Test post-submission edit reset.
16. Test typed finalization.
17. Confirm inquiry status and communication remain unchanged.
18. Test explicit Review Workspace application.
19. Test private export.
20. Test WordPress privacy export and approved erasure in staging.

## Rollback

v0.6.0 does not expose fit tables.

Preserve the fit tables and inquiry fit fields if rolling back temporarily.

Do not drop fit evidence or second-review history merely to restore the older interface.
