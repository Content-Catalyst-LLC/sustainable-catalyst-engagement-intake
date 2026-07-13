# Sustainable Catalyst Engagement Intake

**Version:** 0.2.2  
**Release:** Dual Intake Experiences and Conversion Routing

One private WordPress inquiry system now powers two purpose-built public experiences.

## Consulting page

```text
[sc_engagement_inquiry
  mode="compact"
  source="consulting-page"
  entry_cta="discuss-an-engagement"
  title="Discuss an Engagement"
]
```

The compact form is designed to convert visitors who have already read the Consulting page. It uses published engagement and fee guidance, asks for a bounded problem and outcome, and offers email-first follow-up or a Microsoft Teams fit-call request.

## Contact page

```text
[sc_contact_hub
  mode="advanced"
  source="contact-page"
  entry_cta="contact-hub"
  title="Contact Sustainable Catalyst"
]
```

The advanced hub routes general, consulting, research, technical, workshop, advisory, media, open-source, institutional, and other inquiries into conditional fields and a review step.

## Private conversion metadata

Every inquiry can record:

- Form variant
- Source page
- Entry CTA
- Conversion route
- Guidance flags
- Referring URL
- Inquiry type
- Requested service
- Budget
- Teams request and scheduling state

## Guidance boundaries

Published-fee and route guidance is advisory only. It never:

- blocks submission
- approves or rejects an inquiry
- calculates a fit score
- changes status automatically
- schedules a meeting automatically

## Event hooks

PHP:

```php
sc_ei_form_rendered
sc_ei_public_inquiry_created
sc_ei_conversion_routed
```

Browser custom events:

```text
scEi:formView
scEi:routeSelected
scEi:compactServiceSelected
scEi:compactNextStepSelected
scEi:reviewOpened
scEi:submissionStarted
scEi:submissionSuccess
scEi:submissionError
scEi:validationError
```

No analytics vendor is hard-coded.

## Repository

```text
https://github.com/Content-Catalyst-LLC/sustainable-catalyst-engagement-intake
```
