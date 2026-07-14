# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.1.1  
**Release:** Inquiry Persistence and Lifecycle Reliability Patch

v1.1.1 is a focused production-blocking reliability patch for the v1.1.0 advisory lifecycle. It repairs new inquiry persistence under MySQL strict mode, adds runtime verification of the complete inquiry write-path schema, records protected database diagnostics, and prevents the stored database version from advancing when the required contract is incomplete. No destructive migration is performed and the database schema version remains 1.1.0.

## Lifecycle model

```text
New Inquiry
→ Under Review
→ Needs Information / Qualified
→ Meeting Requested / Meeting Scheduled
→ Proposal in Preparation / Proposal Sent
→ Accepted
→ Active Engagement
→ Completed / Declined / Archived
```

All stage changes are explicit administrator actions. They require authorization, a nonce, current-state validation, a typed confirmation, and—when configured—a reason and assigned owner. The platform does not automatically accept, reject, qualify, schedule, publish, contract, or activate an engagement.

## Preserved v1.1.0 records

The nondestructive database upgrade adds:

```text
{prefix}sc_ei_lifecycle_events
{prefix}sc_ei_lifecycle_notes
{prefix}sc_ei_lifecycle_tasks
```

Existing inquiries receive lifecycle fields and are backfilled from their current legacy status. Existing inquiry, review, portal, meeting, proposal, document, privacy, analytics, and engagement records remain intact.

## Advisory Lifecycle workspace

```text
Contact & Engagement → Advisory Lifecycle
```

The workspace provides:

- stage, owner, priority, next action, and due-date management
- structured qualification and readiness context
- internal-only notes, including sensitive-note marking
- assigned follow-up tasks with idempotent due reminders
- linked Microsoft Teams offers, proposals, and engagements
- audited transition and activity history
- sender-facing summary and next-step controls
- stage, source, service, timing, qualification, proposal, and acceptance metrics

## Sender Portal boundary

Portal users can see only deliberately published information:

- a safe public stage label
- an approved sender-facing summary
- an approved next step
- existing authorized meetings, proposals, documents, and messages

Internal notes, qualification rationale, assignments, task details, scores, decision-authority assessments, and transition reasons are not rendered in the Sender Portal.

## Advisory routes

The canonical Contact page supports routed entry links without creating separate submission systems:

```text
/contact/?engagement=advisory
/contact/?engagement=ai-assurance
/contact/?engagement=evidence-systems
/contact/?engagement=knowledge-architecture
/contact/?engagement=technical-storytelling
/contact/?engagement=responsible-ai
/contact/?engagement=collaboration
/contact/?engagement=media
/contact/?engagement=technical
/contact/?engagement=partnership
/contact/?engagement=workshop
/contact/?engagement=monthly-advisory
```

## Production gate

v1.1.1 retains the v1.1.0 launch gate and adds an inquiry-persistence contract check. Production requires:

- 100% readiness
- zero required failures and zero warnings
- current v1.1.1 persistence-patch and v1.1.0 lifecycle migration evidence
- recent successful live validation
- externally confirmed inbox delivery
- completed controlled-pilot evidence
- current database and protected-storage backup evidence
- no critical events or operational blockers
- no overdue lifecycle tasks or next actions
- typed human promotion to Production

Repository tests do not replace validation on the live WordPress host.

## Upgrade

1. Back up the database and protected storage.
2. Install the v1.1.1 ZIP over the existing plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Complete database, v1.1.0 lifecycle, and v1.1.1 persistence-patch repairs if shown.
6. Open **Contact & Engagement → Advisory Lifecycle** and inspect backfilled inquiries.
7. Assign owners and resolve overdue next actions or tasks.
8. Run Live Validation and repeat the controlled pilot where required.
9. Record fresh backup, inbox, and pilot evidence for v1.1.1.
10. Promote only after the gate returns 100%, zero failures, and zero warnings.

See `docs/ADVISORY-LIFECYCLE.md`, `docs/MIGRATION-v1.1.0.md`, `docs/MIGRATION-v1.1.1.md`, and `docs/RELEASE.md`.
