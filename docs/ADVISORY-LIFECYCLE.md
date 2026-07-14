# Advisory Operations and Engagement Lifecycle

## Purpose

The v1.1.0 lifecycle layer manages the work that follows an inquiry without turning the platform into an autonomous decision maker. It provides a traceable workspace for qualification, ownership, meetings, proposals, follow-up, active engagements, and completion.

## Governed stages

- New Inquiry
- Under Review
- Needs Information
- Qualified
- Meeting Requested
- Meeting Scheduled
- Proposal in Preparation
- Proposal Sent
- Accepted
- Active Engagement
- Completed
- Declined
- Archived

The allowed-transition map prevents accidental jumps. A privileged administrator must use the typed transition confirmation shown in the workspace. A transition reason and assigned owner can be required by settings.

## Qualification

The qualification record can capture:

- organizational challenge and desired outcome
- current systems and constraints
- expected timeline
- stakeholders
- decision authority
- funding status
- privacy and security requirements
- Sustainable AI Assurance applicability
- Microsoft Teams readiness
- qualification status, score, and rationale

Qualification supports human review. It does not rank senders or automatically accept or reject an inquiry.

## Internal workspace

Each inquiry can contain:

- lifecycle owner and priority
- next action and due date
- internal notes and sensitive-note markers
- assigned tasks and completion status
- linked Microsoft Teams meeting offers
- linked proposals and versions
- linked engagement records
- complete transition and lifecycle event history

Internal notes and qualification rationale never become sender-facing merely because a portal account exists.

## Sender-facing lifecycle

The Sender Portal receives a deliberate safe projection:

- public stage label
- approved sender lifecycle summary
- approved next action

The portal does not expose owner IDs, qualification scores, internal notes, sensitive flags, task details, decision-authority assessments, funding assessments, or transition reasons.

## Follow-up reminders

Lifecycle reminders run hourly. Due-task delivery is idempotent through `last_reminded_at` and is disabled by default until an administrator enables lifecycle task email. The reminder is an internal operational notice; it does not change inquiry status or contact the sender.

## Teams, proposals, and engagement handoff

The lifecycle workspace links to the existing human-controlled Microsoft Teams, proposal, and engagement systems. It does not create meetings, publish proposals, attest contracts, or activate engagements automatically.

## Privacy and retention

Lifecycle events, notes, and tasks participate in WordPress privacy export. Approved erasure redacts personal content while retaining the minimum operational and integrity history required by the platform. Existing legal holds and retention controls continue to apply.

## Production operations

Overdue lifecycle tasks and overdue inquiry next actions are required readiness blockers. Production promotion remains unavailable until the queue is clean and all other v1.0.3/v1.1.0 evidence is current.
