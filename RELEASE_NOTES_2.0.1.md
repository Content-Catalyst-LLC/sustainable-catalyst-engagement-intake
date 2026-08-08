# Engagement Intake v2.0.1 — Timeline Query Preparation & Migration Recovery

## Release type

Corrective production release and migration recovery.

## Incident addressed

Engagement Intake v2.0.0 could trigger a WordPress-wide PHP fatal during the
unified-platform migration/backfill.

The timeline query templates contain:

- `%s` for the internally generated database table name
- `%d` for the inquiry identifier

v2.0.0 passed the complete template through `sprintf()` with only the
table-name argument, causing:

`ArgumentCountError: 3 arguments are required, 2 given`

## Correction

```diff
- $sql = sprintf( $spec[2], $table );
+ $sql = str_replace( '%s', $table, $spec[2] );
```

`$wpdb->prepare( $sql, $inquiry_id )` continues to bind the `%d`
inquiry identifier.

## Production recovery validation

- corrected timeline query preparation confirmed
- PHP syntax validation passed
- WordPress bootstrap returned `WORDPRESS_BOOT_OK`
- no new fatal-class PHP errors were generated after repair

## Regression requirement

Future releases must validate:

`maybe_upgrade()` → `record_migration()` → `backfill()` →
`refresh_dossier()` → `timeline()`
