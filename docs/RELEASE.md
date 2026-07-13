# Release Notes — v0.2.1

## Purpose

Prepare the Contact Hub for a Microsoft Teams-centered communication and scheduling workflow without exposing an unrestricted booking calendar or connecting external Microsoft credentials before the internal workflow is ready.

## Public additions

- Preferred response method
- Microsoft Teams email
- Phone number
- Teams meeting request
- Time zone
- City and country
- Preferred weekdays
- Preferred time windows
- Preferred duration
- Participant count
- Participant emails
- Accessibility or accommodation needs
- Calendar invitation consent
- Scheduling notes

## Private workflow

Each inquiry receives a scheduling status:

- Not Requested
- Requested
- Under Review
- Approved
- Times Proposed
- Scheduled
- Completed
- Declined
- Cancelled

Authorized reviewers can store:

- Microsoft Teams meeting URL
- Scheduled local start and end
- Scheduled time zone
- Calendar event ID
- Private scheduling note

Scheduled times are converted to UTC in storage and displayed in the selected time zone.

## Important boundary

v0.2.1 does not:

- expose an unrestricted calendar
- automatically approve meetings
- connect to Microsoft Graph
- create or send Teams invitations
- accept physical documents

It establishes the fields, consent, migration, review controls, and audit trail needed for those later steps.

## Scheduled-state integrity

A record cannot be moved to Scheduled unless calendar invitation consent is present and the administrator supplies a valid Teams URL, start time, end time, and time zone.
