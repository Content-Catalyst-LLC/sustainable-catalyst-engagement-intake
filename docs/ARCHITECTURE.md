# Architecture

## Separation of concerns

Engagement Intake is independent from Feature Suggestions.

- **Feature Suggestions:** public participation, product ideas, surveys, voting, and roadmap improvement.
- **Engagement Intake:** private contact, consulting, research, media, technical, and institutional inquiries.

## Storage

### `wp_sc_ei_inquiries`

Private contact and engagement metadata.

### `wp_sc_ei_attachments`

Attachment metadata, quarantine and approval state, integrity fingerprints, storage verification state, retention metadata, and protected physical-file paths.

### `wp_sc_ei_audit_log`

Append-oriented events for creation, status changes, notes, privacy actions, future file access, communication, and deletion.

## Access

No public post type, archive, feed, query, or unauthenticated REST endpoint is registered.

Access is controlled by dedicated capabilities:

- `sc_intake_view`
- `sc_intake_review`
- `sc_intake_download_files`
- `sc_intake_release_files`
- `sc_intake_manage_file_retention`
- `sc_intake_add_notes`
- `sc_intake_change_status`
- `sc_intake_communicate`
- `sc_intake_export`
- `sc_intake_delete`
- `sc_intake_manage_settings`

## Release layers

v0.2.x established conditional public forms, Teams preferences, dual experiences, and conversion routing.  
v0.3.0 added protected document storage and quarantine.  
v0.3.1 adds atomic storage commits, request-envelope reliability, reconciliation, integrity tracking, retention previews, and cache/CDN hardening.  
v0.3.2 adds a cross-inquiry quarantine operations layer, scanner readiness and retry, guarded bulk file actions, access reporting, and isolation guidance.  
v0.4.0 adds a human-controlled administrative review layer with ownership, due dates, manual judgments, checklists, escalation, immutable snapshots, and explicit handoff recommendations.  
v0.5.0 adds reviewed communications, immutable transport events, versioned templates, opt-in notifications, follow-up state, and manual inbound and Teams interaction records. Later versions add sender portal, connected scheduling, proposals, analytics, and Workflow Core.


## Dual public experiences

The Consulting page and Contact page use separate public renderers but share:

- validation
- private inquiry persistence
- Teams scheduling fields
- privacy tools
- audit history
- administration
- retention settings

This prevents duplicated records and duplicated plugin maintenance.


## Quarantine operations layer

The operational layer remains private and capability-controlled.

```text
Attachment repository
→ cross-inquiry queue
→ scanner operations
→ storage and integrity verification
→ human quarantine decision
→ access and operations audit
```

It does not expose files, create Media Library records, or merge private inquiry data into public Feature Suggestions or Knowledge Library records.


## Administrative review architecture

The review layer separates the fast current-state record from immutable review history.

```text
Current inquiry review fields
→ optimistic review-version check
→ transactional inquiry update
→ immutable review snapshot
→ audit ledger
```

The current inquiry row powers queues, assignments, due-state reporting, and filters.

The dedicated review table preserves each successful human-authored snapshot, including:

- reviewer
- previous and current stage
- assignment
- priority and due date
- fit decision and confidence
- risk
- evidence readiness
- scope clarity
- recommended next step
- rationale and information gaps
- checklist
- escalation
- inquiry status
- review version

The review layer does not calculate a fit score or infer a status from a recommendation.

## Workspace separation

```text
Administrative Review Workspace
  human judgment, ownership, rationale, checklist, escalation

Quarantine Operations
  file validation, scanner state, integrity, retention, access

Full Inquiry Record
  complete intake, Teams preference, notes, files, audit

Public Forms
  sender-facing intake only
```

Risky file operations remain in Quarantine and the full inquiry record. Review Workspace links to those surfaces instead of duplicating download or deletion controls.


## Communication architecture

The communication layer separates current thread state from immutable message and transport history.

```text
Inquiry communication state
→ version-locked draft
→ explicit reviewed send
→ mail transport
→ immutable communication event
→ inquiry aggregate and follow-up
```

Tables:

```text
communications
communication_events
communication_templates
```

`communications` stores the message or interaction record.

`communication_events` stores creation, edit, send attempt, accepted, failed, suppression, retry, and cancellation events.

`communication_templates` stores immutable versions. New versions archive earlier active versions rather than rewriting them.

## Automation architecture

```text
explicit setting
→ trigger
→ dedupe key
→ rendered versioned template
→ normal communication record
→ normal mailer
→ normal events and audit
```

Automation does not have a separate hidden delivery path.

All notification policies default to off.

## External-system boundary

v0.5.0 uses WordPress `wp_mail()` and records only transport acceptance or failure.

It does not read a mailbox, consume bounce webhooks, prove delivery, or create Microsoft Teams meetings.

Teams, phone, and in-person interactions are manually recorded in the same private timeline.


## v0.6.0 privacy lifecycle architecture

```text
Private record
→ privacy state
→ policy version
→ deterministic candidate
→ deduplicated action
→ legal-hold and dependency check
→ approval
→ typed execution
→ verification
→ tombstone and audit
```

Five tables separate case, evidence, preservation, policy, and execution state:

```text
privacy_requests
consent_events
legal_holds
retention_policies
retention_actions
```

The daily retention compatibility hook is queue-only.

The WordPress eraser is also a bridge into reviewed lifecycle actions rather than a direct destructive path.

Execution remains inside the retention engine and rechecks legal holds immediately before mutation.


## v0.7.0 human-controlled fit architecture

```text
inquiry
→ versioned assessment
→ criterion items
→ human recommendation
→ independent review events
→ finalized assessment
→ explicitly applied review snapshot
```

Tables:

```text
fit_assessments
fit_assessment_items
fit_assessment_reviews
```

The assessment header stores the human conclusion and lifecycle state.

Criterion items store ratings, weights, evidence, concerns, and source references.

Second-review rows preserve independent review history.

The repository contains no inquiry-status mutation or mail-delivery path.


## v0.8.0 sender portal architecture
```text
one-time invitation
→ email challenge
→ session credential
→ permission and privacy gates
→ sender-safe view or write
→ portal event and audit
```

Portal access, sessions, and events are separated from WordPress users.

Portal-visible communications remain in the communication ledger and require explicit visibility state.

Portal uploads enter the existing protected quarantine architecture.


## v0.8.1 authentication and recovery architecture

```text
invitation credential
→ token-first verification
→ email challenge
→ atomic access/inquiry/session transaction
→ __Host session cookie
```

Recovery is a separate workflow:

```text
generic public request
→ keyed-IP rate limit
→ internal exact match
→ pending recovery record
→ human decision
→ normal invitation reissue
→ one-time administrative link display
```

The recovery workflow never calls mail transport.


## v0.9.0 Teams scheduling and proposal architecture

```text
workflow schema
→ meeting and proposal repositories
→ capability-gated administrator actions
→ secure sender portal actions
→ workflow event ledger
→ audit, privacy, review, REST, and Diagnostics
```

Meeting and proposal records are separate from inquiry review and portal authentication records.

Proposal versions are immutable. Published and pending version pointers prevent a draft revision from replacing sender-visible content before publication.


## v0.9.1 Microsoft Graph connector architecture

```text
Graph credential vault
→ encrypted token cache
→ restricted Graph client
→ durable operation repository
→ human-triggered administrator actions
→ Teams workflow reconciliation
→ audit, privacy, export, and Diagnostics
```

The background queue processes only preauthorized operations. It does not autonomously select meetings to book.

Request payloads are encrypted separately from operation metadata.

Graph state is linked to but remains distinct from local meeting workflow state.


## v0.10.0 engagement handoff architecture

```text
contracted proposal
→ atomic engagement repository
→ immutable snapshot
→ onboarding requirements
→ readiness gate
→ typed activation
→ event ledger
→ portal, export, review, REST, privacy, and Diagnostics
```

The commercial proposal and operational engagement remain separate records. Integration packages are export-only.


## v0.11.0 hardening architecture

```text
SC_EI_Hardening_Schema
→ fixed defaults and bounded settings

SC_EI_Hardening_Repository
→ request IDs
→ durable rate limits
→ deduplicated health events
→ watchdog and pruning
→ headers and accessibility helpers
→ incident locks and pause state

SC_EI_Hardening_Admin
→ Reliability workspace
→ typed human operations
→ redacted export
```

The hardening layer observes technical state and gates public mutations. It does not alter fit, proposal, contract, engagement, or privacy decisions.
## v0.12.0 Workflow Core architecture

```text
authoritative repositories
→ canonical workflow case projection
→ idempotent command ledger
→ signed versioned handoff
→ durable outbox
→ registered internal WordPress adapter
```

The core is a projection and integration layer, not a replacement domain model.

