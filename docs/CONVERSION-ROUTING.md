# Conversion Routing

## Goal

Measure which public page and intake experience generated an inquiry without combining private inquiry data with a public analytics system.

## Stored fields

- Form variant
- Source page
- Entry CTA
- Conversion route
- Guidance flags
- Referring form URL

## Conversion route

The route usually follows the requested engagement for consulting inquiries and the inquiry type for general Contact Hub inquiries.

Examples:

```text
strategic_consultation
knowledge_platform_build
research_collaboration
speaking_media
institutional_partnership
```

## Guidance flags

Examples:

```text
platform_build_budget_guidance
sprint_budget_guidance
fit_call_scope_guidance
route_recommendation_requested
```

A guidance flag is not a fit score. It only records that a selected service, budget, or request pattern may require human clarification.

## Browser events

Custom events are emitted through `window` and can be consumed by a privacy-conscious analytics layer.

No event handler sends data outside WordPress by default.

## PHP events

`sc_ei_conversion_routed` fires after the inquiry record is created and includes the private record plus conversion metadata.
