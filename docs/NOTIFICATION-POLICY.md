# Notification Policy

## Default state

Every automatic notification policy is disabled.

## Sender acknowledgment

Trigger:

```text
successful public inquiry creation
```

Audience:

```text
inquiry contact email
```

Controls:

- opt-in setting
- do-not-email suppression
- one dedupe key per inquiry
- plain-text acknowledgment template
- no documents
- transport event history

## Internal new-inquiry alert

Trigger:

```text
successful public inquiry creation
```

Audience:

```text
configured internal recipients
```

The message contains minimal intake context and a direction to open the private WordPress workspace.

## Review due reminder

Trigger:

```text
review due within configured lead window or overdue
```

Audience:

```text
assigned reviewer
```

Deduplication:

```text
one per inquiry, recipient, and UTC day
```

## Follow-up reminder

Trigger:

```text
next_follow_up_at is due and communication thread is not closed
```

Audience:

```text
assigned reviewer, otherwise configured internal recipients
```

## Escalation alert

Trigger:

```text
review enters requested or under-review escalation state
```

Audience:

```text
configured escalation recipients, otherwise internal recipients
```

Deduplication includes the review version.

## Transport policy

- plain text
- no attachments
- authorized sender address
- explicit reply-to address
- WordPress mail transport
- accepted is not delivered
- failed attempts remain visible
- no automatic retry loop
- manual retry available
- transport test contains no inquiry data

## Operational recommendations

- configure SPF, DKIM, and DMARC for the sender domain through the hosting mail provider
- monitor provider logs separately
- use a transactional provider or authenticated SMTP appropriate to the hosting environment
- verify bounce handling outside this release
- keep automated messages minimal
- avoid sensitive inquiry narratives in internal alerts
- confirm consent and lawful basis before expanding external automation
