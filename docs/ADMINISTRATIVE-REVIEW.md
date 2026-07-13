# Administrative Review Operations

## Review principle

The workspace records professional judgment in a traceable format.

It does not provide an automated fit score.

## Recommended workflow

### 1. Ownership

Claim or assign the inquiry.

Avoid parallel review unless the manager deliberately reassigns responsibility.

### 2. Triage

Review:

- sender identity and organization
- inquiry type
- requested service
- source page and conversion route
- timing and deadline
- Teams meeting request
- private document state
- privacy or retention concerns

### 3. Substantive review

Record:

- problem and desired outcome
- scope boundaries
- audience and stakeholders
- evidence availability
- technical requirements
- decision dependencies
- capacity and budget alignment
- conflicts or independence concerns

### 4. Fit judgment

Choose one:

```text
Undecided
Strong Fit
Possible Fit
Needs Clarification
Limited Fit
Not a Fit
Referral Candidate
```

Also record confidence.

Confidence is not a probability.

### 5. Risk and readiness

Record:

- risk level
- evidence readiness
- scope clarity

Use narrative fields for the reasons and uncertainties behind these categories.

### 6. Next step

Choose an explicit internal recommendation.

The recommendation does not perform the action.

### 7. Inquiry status

Select the inquiry status separately.

This separation prevents hidden status inference.

### 8. Checklist and rationale

Complete the checklist and document rationale before review completion.

### 9. Escalation

Use escalation when:

- conflict or independence requires another reviewer
- legal, privacy, security, or reputational concern is material
- scope crosses capability boundaries
- a high-risk document or claim affects the decision
- pricing or institutional authority exceeds the reviewer’s mandate

### 10. Handoff

A review marked Handoff Ready or Completed remains internal.

Notifications, fit calls, proposals, and sender communication are implemented in later releases.

## Queue operating views

### Open Queue

All reviews not completed.

### My Reviews

Open reviews assigned to the current user.

### Unassigned

Open reviews without an owner.

### Escalations

Requested or active escalations.

### Completed

Reviews explicitly marked completed.

### Review Method

Internal operating guidance.

## Version conflicts

When two reviewers open the same inquiry:

1. both see the same version
2. first save increments the version
3. second save fails
4. second reviewer reloads and reconciles changes

The failure is intentional and prevents silent overwrite.

## Review packet

The JSON packet is a private administrative artifact.

It contains personal and confidential information. Store and share it only through approved private channels.
