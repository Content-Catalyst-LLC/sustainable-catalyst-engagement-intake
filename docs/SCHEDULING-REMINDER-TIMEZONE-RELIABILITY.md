# Scheduling, Reminder, and Time-Zone Reliability

## Civil-time safety

Meeting and follow-up inputs require an IANA time zone. Local clock values that do not exist during spring-forward transitions or occur twice during fall-back transitions are rejected rather than silently changed.

## Reminder state

Pre-meeting reminders are reviewable only while a meeting remains scheduled. Cancellation reminders are reviewable only after cancellation, and post-meeting reminders only after completion. Stale and orphaned records are closed by a bounded repair.

## Human-reviewed delivery

A reminder may be marked sent only when it is ready for review and linked to an accepted or recorded outbound communication for the same inquiry. Background jobs never send meeting communications automatically.

## Compensating behavior

A reschedule is rolled back when reminder regeneration fails. Completion stages post-meeting context and a requested lifecycle task before the final status change, with cleanup when a step fails.
