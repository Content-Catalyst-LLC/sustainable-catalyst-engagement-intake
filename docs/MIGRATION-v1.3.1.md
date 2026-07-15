# Migration to v1.3.1

This nondestructive patch keeps database version **1.3.0** and advances the calendar evidence schema to **1.0.1**. Activation verifies the existing calendar tables, records `v1_3_1_scheduling_reminder_timezone_reliability`, repairs bounded reminder-state inconsistencies, and preserves all inquiries, support cases, meetings, communications, and files.

After installation, run the calendar reliability repair and Live Validation. Re-record version-bound backup, email, and pilot evidence before Production.
