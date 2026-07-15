# Migration to v1.3.0

## Purpose

v1.3.0 introduces Microsoft Teams and Calendar Coordination without replacing the existing meeting-offer, Microsoft Graph, advisory, or support systems.

## Database changes

The migration advances `sc_ei_db_version` to `1.3.0` only after the full required database contract verifies. It:

- expands `{prefix}sc_ei_meeting_offers` with meeting type, organizer, participants, agenda, preparation, sender summary, calendar provider/reference, reschedule history, cancellation link-revocation, post-meeting, and follow-up fields
- creates `{prefix}sc_ei_meeting_reminders` with idempotency, due, status, communication, retry, and audit fields
- records `v1_3_0_microsoft_teams_calendar_coordination`

The migration is safe to rerun. Existing records are preserved and receive database defaults.

## Upgrade procedure

1. Back up the WordPress database and protected storage.
2. Install v1.3.0 over the current plugin.
3. Clear all caches, including PHP OPcache.
4. Open Platform Overview and run database repair if required.
5. Verify the v1.3.0 migration journal.
6. Open Calendar Coordination and inspect existing meetings.
7. Run Live Validation.
8. Complete a controlled scheduling, rescheduling, cancellation, and Sender Portal test.
9. Record fresh version-bound evidence.

## Rollback

Do not delete the new table or columns. Restore the prior code only after confirming it tolerates the expanded schema, or restore the complete pre-upgrade database and plugin backup together.
