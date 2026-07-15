# Sustainable Catalyst Contact and Engagement Platform

**Version:** 1.3.1  
**Release:** Scheduling, Reminder, and Time-Zone Reliability Patch

v1.3.1 stabilizes the Microsoft Teams and Calendar Coordination layer introduced in v1.3.0. It keeps the platform review-first: no public booking calendar is exposed, reminder jobs prepare records for human review rather than sending automatically, and the local WordPress meeting record remains canonical.

## Reliability improvements

- rejects nonexistent spring-forward local times
- rejects ambiguous fall-back local times
- stores accepted meeting and follow-up times in UTC with an explicit IANA time zone
- keeps cancellation and post-meeting notices eligible after the meeting enters its terminal state
- closes orphaned and stale reminder records through a bounded repair
- requires an accepted or recorded outbound communication before a reminder can be marked sent
- rolls back rescheduling when reminder regeneration fails
- stages post-meeting context and requested follow-up tasks before completion becomes canonical
- exposes reminder, terminal-notice, time-zone, canceled-link, and follow-up blockers in Production Readiness

## Human-control boundary

Background work may identify due reminders and mark them ready for review. It does not send them. Microsoft Graph remains optional, and no meeting, calendar event, communication, lifecycle transition, or follow-up is created without an authorized action.

## Migration

The v1.3.1 patch is nondestructive:

- plugin version: `1.3.1`
- database version: `1.3.0` (unchanged)
- platform evidence schema: `1.3.1`
- calendar schema: `1.0.1`
- migration journal: `v1_3_1_scheduling_reminder_timezone_reliability`

Activation verifies the existing v1.3.0 calendar schema and performs bounded reminder-state repair. It does not drop, rewrite, or merge inquiries, support cases, meetings, communications, private files, or Sender Portal records.

## Upgrade

1. Back up the WordPress database and protected engagement storage.
2. Install the v1.3.1 ZIP over the existing plugin.
3. Clear WordPress, object, host, CDN, browser, and PHP opcode caches.
4. Open **Contact & Engagement → Platform Overview**.
5. Run the v1.3.1 calendar reliability repair if shown.
6. Run Live Validation.
7. Confirm the test email externally.
8. Test one advisory meeting and one support troubleshooting meeting.
9. Re-record version-bound backup, inbox, validation, and pilot evidence.
10. Promote only at 100%, zero required failures, and zero warnings.

See `docs/SCHEDULING-REMINDER-TIMEZONE-RELIABILITY.md`, `docs/MIGRATION-v1.3.1.md`, and `docs/RELEASE.md`.
