# Sustainable Catalyst Engagement Intake

**Version:** 0.9.2  
**Release:** Proposal and Engagement Handoff

## End-to-end workflow

```text
public inquiry
→ administrative review
→ human-controlled fit assessment
→ secure sender portal
→ Teams consultation
→ versioned proposal
→ sender intent response
→ external contract recorded
→ controlled engagement handoff
→ onboarding readiness
→ human activation
→ active / paused / completed / canceled
```

No stage automatically performs the next legally or operationally significant stage.

## Core invariant

```text
one contracted proposal
→ at most one engagement
```

The `engagements.proposal_id` field is unique, and the repository checks for an existing handoff before beginning its transaction.

## Atomic handoff

```text
START TRANSACTION
→ create handoff_pending engagement
→ create immutable commercial snapshot
→ attach snapshot
→ seed onboarding requirements
→ record handoff and snapshot events
COMMIT
```

Any persistence failure performs `ROLLBACK`. The contracted proposal remains unchanged.

## Snapshot model

The handoff snapshot includes the exact contracted proposal version and its existing content hash, plus an independent hash of the complete handoff payload.

```text
proposal_content_hash
content_hash
```

Normal workflow never edits the snapshot. Approved privacy erasure replaces the personal payload with a limited tombstone and updates the snapshot hash so integrity remains verifiable.

## Engagement states

```text
handoff_pending
ready_for_setup
active
paused
completed
canceled
```

Allowed operational transitions:

```text
handoff_pending → ready_for_setup
ready_for_setup → active
active → paused / completed / canceled
paused → active / completed / canceled
handoff_pending / ready_for_setup → canceled
```

## Readiness gate

Readiness checks:

```text
contract reference present
owner assigned
snapshot present
snapshot hash valid
all required onboarding items complete or waived
source proposal still contracted
snapshot proposal version still matches
privacy state permits activation
```

Activation reruns all checks.

## Typed controls

```text
HANDOFF <PROPOSAL-NUMBER>
READY <ENGAGEMENT-NUMBER>
ACTIVATE <ENGAGEMENT-NUMBER>
PAUSE <ENGAGEMENT-NUMBER>
RESUME <ENGAGEMENT-NUMBER>
COMPLETE <ENGAGEMENT-NUMBER>
CANCEL <ENGAGEMENT-NUMBER>
```

## New tables

```text
{prefix}sc_ei_engagements
{prefix}sc_ei_engagement_snapshots
{prefix}sc_ei_engagement_requirements
{prefix}sc_ei_engagement_events
```

## New capabilities

```text
sc_intake_view_engagements
sc_intake_create_engagement_handoffs
sc_intake_manage_engagements
sc_intake_activate_engagements
sc_intake_complete_engagements
sc_intake_export_engagements
```

Reviewers receive view access. Managers receive operational handoff capabilities. Administrators receive all capabilities.

## Portal permission

```text
view_engagements
```

Existing access records retain their existing permission JSON. Reissue an invitation to add the new permission.

## Integration package

Private handoff export schema:

```text
sc-engagement-handoff-package/1.0
```

Contains:

```text
engagement record
commercial snapshot and integrity state
onboarding requirements
event ledger
Workbench handoff metadata
Decision Studio handoff metadata
fixed no-automation boundaries
```

It does not provision either platform.

## Fixed boundaries

```text
automatic activation = false
automatic provisioning = false
automatic invoice = false
automatic payment = false
electronic signature = false
contract generation = false
```

## Public shortcodes

```text
[sc_engagement_inquiry mode="compact" source="consulting-page" entry_cta="discuss-an-engagement" title="Discuss an Engagement"]
[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub" title="Contact Sustainable Catalyst"]
[sc_sender_portal title="Secure Sender Portal"]
```

No additional public shortcode is required.
