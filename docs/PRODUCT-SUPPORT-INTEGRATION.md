# Product Support and Feature Suggestions Integration

## Architecture

Contact and Engagement owns private product-support cases. Feature Suggestions owns public documentation, known issues, feature requests, voting, and product intelligence. They communicate through the typed contract:

```text
sc-product-support-handoff/1.0
```

A handoff attaches public product context to an already-created canonical inquiry. It does not create a sender identity or private inquiry by itself.

## Accepted nonpersonal context

- product
- product version
- component
- issue category
- search query
- article IDs
- known-issue reference
- resolution-attempt flag
- article-helpfulness result
- source URL

## Rejected context

The contract rejects fields whose keys indicate names, email addresses, contact data, organizations, messages, bodies, files, attachments, IP addresses, phone numbers, passwords, tokens, API keys, or other credentials.

## REST endpoints

```text
GET  /wp-json/sc-engagement-intake/v1/support/cases
GET  /wp-json/sc-engagement-intake/v1/support/cases/{id}
POST /wp-json/sc-engagement-intake/v1/support/handoffs
```

All endpoints require dedicated capabilities. No support endpoint is publicly writable.

## Public intake

Use:

```text
[sc_support_request]
```

or route the unified Contact page with:

```text
/contact/?engagement=support
```

## Sender Portal boundary

The portal can show only the case number, product/version/component, sender-safe status, approved summary, approved next step, known-issue reference, and explicitly sender-visible links. Exact errors, reproduction steps, private assignments, internal events, and unreleased implementation details are not exposed.
