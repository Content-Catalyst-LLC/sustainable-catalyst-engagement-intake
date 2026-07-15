# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.2.1  
**Release:** Support Operations and Cross-Product Reliability Patch

v1.2.1 hardens the private support layer introduced in v1.2.0. Public support intake now creates the canonical inquiry and linked support case as one recoverable operation; failed case persistence rolls back only the newly created inquiry. Cross-product handoffs validate registered products and sources, use stable handoff identifiers, replay safely, preserve typed Knowledge Base, known-issue, Feature Suggestion, and release relationships, and reject personal or private signal fields.

The public Knowledge Base and feature-feedback system remain in Feature Suggestions. Contact and Engagement owns private cases, sender communication, diagnostic files, internal reasoning, resolution, and Sender Portal continuity. The integration contract is `sc-product-support-handoff/1.0`.

## Product support model

```text
New Support Request
→ Triage / Needs Information
→ Reproducing / Known Issue
→ Workaround Provided / Fix Planned
→ Resolved
→ Closed
```

Every transition is a deliberate administrator action with authorization, current-state validation, typed confirmation, and an audit event. The support repository does not send mail, publish a fix, promise a release date, or mutate a Feature Suggestion automatically.

## Support workspace

```text
Contact & Engagement → Support Cases
```

The workspace includes product and version context, environment details, reproduction evidence, severity, assignment, sender-safe updates, governed stages, typed product relationships, privacy-safe intelligence signals, and an audit timeline.

## Public support entry

```text
[sc_support_request]
```

or:

```text
/contact/?engagement=support
```

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

v1.2.1 retains the v1.2.0 support model and adds patch-migration, atomic persistence, cross-product handoff-recovery, product-context, and historical failure-recovery evidence to the production gate. Production requires:

- 100% readiness
- zero required failures and zero warnings
- current v1.2.1 support-reliability patch, v1.2.0 support, v1.1.1 persistence-patch, and v1.1.0 lifecycle migration evidence
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
2. Install the v1.2.1 ZIP over the existing plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Complete database, v1.0–v1.1, and v1.2.0 support migration and v1.2.1 reliability-patch repairs if shown.
6. Open **Contact & Engagement → Advisory Lifecycle** and **Support Cases** and inspect migrated records.
7. Assign owners and resolve overdue next actions or tasks.
8. Run Live Validation and repeat the controlled pilot where required.
9. Record fresh backup, inbox, live-validation, and pilot evidence for v1.2.1.
10. Promote only after the gate returns 100%, zero failures, and zero warnings.

See `docs/PRODUCT-SUPPORT-INTEGRATION.md`, `docs/MIGRATION-v1.2.0.md`, `docs/MIGRATION-v1.2.1.md`, `docs/ADVISORY-LIFECYCLE.md`, and `docs/RELEASE.md`.
