# Human-Controlled Fit Assessment

## Purpose

Help authorized reviewers form a disciplined, evidence-backed judgment about whether and how Sustainable Catalyst should continue evaluating an inquiry.

The workspace is not an automated eligibility engine.

## Control model

```text
facts and private records
→ human criterion ratings
→ transparent advisory summary
→ human recommendation
→ independent review when required
→ typed finalization
→ optional explicit Review Workspace application
```

No stage automatically changes the inquiry.

## Rating scale

```text
Strong Concern = 0
Weak = 1
Partial = 2
Good = 3
Strong = 4
Not Assessed = excluded until complete
Not Applicable = excluded from denominator
```

## Advisory calculation

For applicable, assessed criteria:

```text
score =
Σ(rating value × criterion weight)
÷
Σ(4 × criterion weight)
× 100
```

This is not a probability, eligibility score, risk model, or prediction.

Do not treat it as one.

## Evidence standard

A useful evidence note should identify:

- the relevant inquiry statement
- a private document and page or section
- a communication date
- a stakeholder statement
- a known operational constraint
- an explicitly stated uncertainty

Avoid copying unnecessary sensitive content into the assessment.

Use private record pointers where possible.

## Material concerns

Mark a criterion as a material concern only when it may independently affect:

- whether the work should proceed
- whether a second review is required
- whether scope must change
- whether additional expertise is needed
- whether independence or public trust could be impaired
- whether privacy, safety, or legal review is required

Every material concern needs a clear note.

## Human recommendation

The recommendation is selected by the assessor.

The score does not populate or change it.

The assessor should explain:

- what supports the recommendation
- what remains uncertain
- what conditions would change it
- which service route is proportionate
- what boundary or referral is needed
- what evidence was missing

## Second review

Second review should test the reasoning rather than merely endorse the score.

The reviewer should inspect:

- the original inquiry
- key private documents
- material concerns
- conflict and independence notes
- privacy and retention state
- recommendation rationale
- proposed service route
- scope boundary
- uncertainty and conditions

An Agree disposition confirms the submitted recommendation, route, and boundary exactly.

Proposed changes require a changes-requested disposition.

## Assistance disclosure

Disclose assistance used for:

- formatting
- summarization
- extraction
- comparison
- analytical prompts
- drafting support

The human assessor remains responsible for:

- source verification
- criterion ratings
- material concerns
- recommendation
- scope boundary
- service route
- rationale
- attestation

## Finalization

Typed finalization:

```text
FINALIZE <assessment-id>
```

freezes the version.

It does not create a business decision outside the assessment record.

## Application

Typed application:

```text
APPLY <assessment-id>
```

copies selected conclusions into a new Review Workspace snapshot.

It does not change inquiry status.

Status changes, communication, scheduling, proposal delivery, and referral remain separate human actions.

## Reassessment

Create a new assessment version when:

- the inquiry materially changes
- new documents arrive
- scope is reframed
- budget or timing changes
- conflict information changes
- privacy restrictions change
- a finalized assessment is no longer current

Do not overwrite a finalized version.

## Privacy

Assessment narratives can contain sensitive inferences and private evidence.

Limit access.

Do not export casually.

Approved inquiry erasure removes narratives and source references while retaining non-personal categorical and audit evidence where appropriate.
