# Public Shortcodes and Routed Entries

## Canonical Contact and Engagement entry

```text
[sc_contact_engagement_platform]
```

Place this shortcode on the primary published Contact page. It composes the existing intake and Sender Portal entry points without creating a second submission pipeline.

## Existing supported shortcodes

```text
[sc_contact_hub]
[sc_contact_form]
[sc_engagement_inquiry]
[sc_sender_portal]
```

## Service-specific routed links

Use the canonical Contact-page URL with an `engagement` query value:

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

Routes preselect and attribute the inquiry path. They do not create separate forms, endpoints, records, or notification systems.

## Sender Portal

```text
[sc_sender_portal title="Secure Sender Portal"]
```

The Sender Portal must be placed on its own published page and excluded from full-page caching. v1.1.0 can deliberately publish a safe lifecycle stage, summary, and next step. Internal notes, qualification context, assignments, tasks, scores, and transition reasons remain private.
