# Sustainable Catalyst Engagement Intake

**Version:** 0.2.1  
**Release:** Microsoft Teams Communication Preferences and Scheduling Readiness

A private WordPress contact and engagement intake system for Sustainable Catalyst.

## Live meeting platform

Microsoft Teams is the only supported live meeting platform in v0.2.1.

This release does not create Teams events automatically. It collects scheduling preferences, records consent, supports private review, stores approved Teams meeting details, normalizes scheduled times to UTC, and maintains an audit history.

## Public shortcodes

```text
[sc_contact_hub]
```

```text
[sc_contact_form mode="general"]
```

```text
[sc_engagement_inquiry mode="consulting"]
```

## Teams readiness capabilities

- Preferred response method
- Conditional Teams email or phone
- Teams meeting request
- Browser time-zone suggestion
- Manual IANA time zone
- City and country
- Preferred weekdays and time windows
- Preferred duration
- Participant count and emails
- Accessibility or accommodation notes
- Calendar invitation consent
- Scheduling notes
- Human-controlled scheduling status
- Teams meeting URL
- Local-to-UTC meeting conversion
- Calendar event ID
- Admin filtering and audit history

## Scheduling workflow

```text
Inquiry submitted
→ Requested
→ Under Review
→ Approved
→ Times Proposed
→ Scheduled
→ Completed
```

Alternative outcomes:

```text
Declined
Cancelled
Not Requested
```

## Recommended Contact page embed

```text
[sc_contact_hub title="Contact Sustainable Catalyst"]
```

## Repository

```text
https://github.com/Content-Catalyst-LLC/sustainable-catalyst-engagement-intake
```
