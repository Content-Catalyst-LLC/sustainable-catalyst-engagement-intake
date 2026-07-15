# Integrated Advisory, Support, and Institutional Engagement Platform

v2.0.0 adds a coordination layer over the existing Contact and Engagement subsystems.

## Canonical dossier

One dossier is keyed to one canonical inquiry. The dossier records route, current phase, health, owner, sender-safe summary, sender-safe next step, relationship count, activity count, and a SHA-256 content hash. It does not copy private message bodies or replace source records.

## Typed relationships

The relationship registry indexes support cases, Teams meetings, proposals, Statements of Work, engagements, client workspaces, invoices, protected attachments, communications, and lifecycle tasks. Removing a relationship does not remove the related source record.

## Unified timeline

The Command Center assembles a chronological timeline from dossier, lifecycle, support, workflow, engagement, workspace, billing, and communication events. Visibility remains explicit and sender-facing surfaces continue to use their existing allowlists.

## Typed handoff contract

`sc-engagement-platform-handoff/2.0` accepts bounded product, version, component, route, issue, search, article, release, and resolution context. It rejects names, email addresses, phone numbers, organizations, messages, files, documents, credentials, sessions, IP addresses, card details, and bank details.

Handoff keys are unique. Replaying the same handoff returns the existing receipt instead of creating a duplicate.

## Governance boundaries

The platform does not automatically merge cases, infer identity, rank senders, make advisory or support decisions, schedule meetings, approve proposals, activate engagements, collect payments, or send communications.
