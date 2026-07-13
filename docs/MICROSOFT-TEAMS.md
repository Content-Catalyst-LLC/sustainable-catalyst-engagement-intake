# Microsoft Teams Scheduling Architecture

## Platform decision

Microsoft Teams is the only supported live meeting platform.

There is no generic meeting-platform dropdown and no Zoom or Google Meet integration.

## Public request model

The public form can collect:

- Preferred response method
- Teams identity
- Meeting request
- Time zone and general location
- Availability
- Duration
- Participants
- Accessibility needs
- Calendar invitation consent
- Scheduling notes

The form does not display real calendar availability or create an event.

## Administrative state machine

```text
not_requested
requested
under_review
approved
times_proposed
scheduled
completed
declined
cancelled
```

No scheduling status changes automatically because of fit scoring or form selection.

## Data handling

Scheduled local times are converted to UTC using the selected IANA time zone.

The inquiry retains:

- sender time zone
- scheduled time zone
- UTC start
- UTC end
- Teams meeting URL
- external calendar event ID

## URL policy

The default Teams URL allowlist accepts Microsoft Teams hosts. The host list is filterable through:

```php
sc_ei_teams_url_hosts
```

## Future Microsoft Graph integration

A later release may add:

- OAuth connection
- organizer identity verification
- availability lookup
- Teams event creation
- attendee invitations
- reminder synchronization
- cancellation and rescheduling
- webhook reconciliation

Credentials must not be stored in inquiry records.
