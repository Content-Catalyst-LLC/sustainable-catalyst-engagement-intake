# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.3.0
**Release:** Microsoft Teams and Calendar Coordination

v1.3.0 adds a governed scheduling and meeting-operations layer over the platform's existing inquiry, advisory, support, Sender Portal, and Microsoft Graph foundations. Meetings remain review-first: the public site does not expose unrestricted calendar availability, and no meeting, reminder, Graph event, lifecycle transition, or follow-up is created without an authorized workflow action.

## Calendar coordination model

```text
Meeting considered
→ availability requested
→ time proposed
→ sender accepts
→ Teams meeting confirmed
→ reviewed reminders
→ meeting completed or canceled
→ follow-up recorded
```

The release supports advisory discovery, Sustainable AI Assurance review, product-support troubleshooting, research collaboration, institutional discussion, media interviews, workshops, proposal review, engagement review, project closeout, and other approved meeting types.

## Administration

```text
Contact & Engagement → Calendar Coordination
```

The workspace provides:

- meeting type, purpose, organizer, and participant context
- explicit IANA time zones and UTC scheduling
- proposed, accepted, scheduled, completed, canceled, expired, and superseded states
- agenda and preparation requests
- Microsoft Teams join URLs and Microsoft Graph calendar references
- rescheduling history and duplicate-event safeguards
- reviewable invitation, 24-hour, one-hour, reschedule, cancellation, and follow-up reminders
- post-meeting internal notes, sender-visible summary, decisions, open questions, and follow-up tasks
- sender-safe status and next-step controls

## Human-control boundary

The platform does not expose an unrestricted public booking calendar. It does not automatically create or delete Microsoft Graph events, send meeting reminders, schedule a sender, accept an engagement, resolve a support case, or publish internal meeting notes. Background processing only marks due reminder records for authorized review.

## Sender Portal boundary

Authorized senders can see only approved meeting information:

- meeting number, type, title, purpose, and status
- confirmed date, time, and time zone
- active approved Microsoft Teams link
- approved agenda and preparation requests
- approved sender summary and next step
- approved post-meeting summary
- cancellation or rescheduling state

Organizer email, participant lists, internal notes, decisions, open questions, Graph credentials, internal event references, and unreleased information remain private.

## Microsoft Graph boundary

Microsoft Graph remains optional. The local WordPress record is canonical for engagement context. Credentials remain encrypted in the existing connector layer, not in inquiry or meeting metadata. Human-confirmed operations use stable transaction identifiers, retry controls, reconciliation, and explicit cancellation handling.

## Database migration

The nondestructive v1.3.0 migration:

- expands the existing `{prefix}sc_ei_meeting_offers` table with calendar-coordination fields
- adds `{prefix}sc_ei_meeting_reminders`
- records `v1_3_0_microsoft_teams_calendar_coordination`
- preserves inquiries, support cases, lifecycle records, portal records, documents, proposals, engagements, communications, and previous meeting offers

Database version advances to `1.3.0`. Existing meetings receive safe defaults and remain available for review.

## Production gate

Production requires:

- 100% readiness, zero required failures, and zero warnings
- verified v1.3.0 database columns and migration evidence
- scheduled calendar reminder job with its registered callback
- no scheduled meeting without an explicit time zone
- no canceled meeting retaining an active Teams or Graph join URL
- no overdue calendar follow-up without a task
- no unresolved Microsoft Graph reconciliation requirement
- recent live validation, external inbox confirmation, backup attestation, and controlled-pilot evidence
- typed human promotion to Production

Repository tests do not replace validation on the live WordPress host.

## Upgrade

1. Back up the database and protected storage.
2. Install the v1.3.0 ZIP over the existing plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Run database and v1.3.0 calendar migration repairs if shown.
6. Open **Calendar Coordination** and inspect existing meeting records.
7. Run Live Validation and confirm the temporary meeting is created, scheduled, rescheduled, canceled, and cleaned up.
8. Complete controlled advisory and support scheduling tests in the Sender Portal.
9. Re-record version-bound backup, inbox, live-validation, and pilot evidence.
10. Promote only after the gate returns 100%, zero failures, and zero warnings.

See `docs/MICROSOFT-TEAMS-CALENDAR-COORDINATION.md`, `docs/MIGRATION-v1.3.0.md`, `docs/MICROSOFT-GRAPH-RELIABILITY.md`, `docs/SENDER-PORTAL.md`, and `docs/RELEASE.md`.
