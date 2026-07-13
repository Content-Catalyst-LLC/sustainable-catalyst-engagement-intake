# Release Notes — v0.2.2

## Purpose

Create the strongest intake experience for Sustainable Catalyst by separating conversion-focused consulting intake from broad institutional contact routing while preserving one private administrative system.

## Compact Consulting Intake

Designed for visitors who have already reviewed the Consulting page.

It collects:

- Name
- Email
- Organization
- Best-fit engagement
- Budget range
- Problem
- Desired outcome
- Desired start date
- Public link
- Email-first or Teams fit-call next step

A Teams fit-call request reveals only:

- Teams email
- Time zone
- General availability
- Calendar invitation consent

## Advanced Contact Hub

Designed for the Contact page.

It supports ten inquiry routes and conditional detail collection for:

- General questions
- Consulting
- Research collaboration
- Platform and technical work
- Workshops and training
- Monthly advisory
- Speaking, media, and press
- Open-source work
- Institutional partnership
- Other inquiries

## Conversion routing

Private records now include:

- `form_variant`
- `source_page`
- `entry_cta`
- `conversion_route`
- `guidance_flags`

The referring URL remains in private metadata.

## Guidance

The compact form can explain:

- free fit-call boundaries
- $375 strategic consultation
- $1,500 diagnostic
- $5,000–$8,500 strategy sprint
- platform builds beginning at $12,000
- $1,500–$4,500 workshops
- $2,500–$6,000+ monthly advisory
- custom institutional partnership scope

Guidance is educational and non-blocking.

## Event hooks

The release includes PHP and browser events for privacy-conscious conversion measurement. No GA4, Microsoft, Meta, or other analytics provider is enabled automatically.

## Boundaries

v0.2.2 does not:

- create a fit score
- automatically approve or reject
- expose a public inquiry record
- create a Teams meeting
- expose live calendar availability
- accept physical files
