<?php
/** v1.7.0 Billing, Invoicing, and Payment Handoffs contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-billing-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-billing-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-billing-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/billing.php' );
$portal = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$privacy_repo = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$communications = file_get_contents( $plugin . '/includes/class-sc-ei-communication-schema.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$readme = file_get_contents( $plugin . '/readme.txt' );
$checks = array(
    'v1.7.0 identity' => false !== strpos( $main, 'Version:     1.7.0' ) && false !== strpos( $main, "SC_EI_DB_VERSION', '1.7.0'" ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '1.7.0'" ) && false !== strpos( $main, "SC_EI_PORTAL_SCHEMA_VERSION', '1.8.0'" ) && false !== strpos( $main, "SC_EI_BILLING_SCHEMA_VERSION', '1.0.0'" ),
    'billing modules loaded' => false !== strpos( $main, 'class-sc-ei-billing-schema.php' ) && false !== strpos( $main, 'class-sc-ei-billing-repository.php' ) && false !== strpos( $main, 'class-sc-ei-billing-admin.php' ),
    'six billing tables' => false !== strpos( $db, '$sql_billing_profiles' ) && false !== strpos( $db, '$sql_invoices' ) && false !== strpos( $db, '$sql_invoice_items' ) && false !== strpos( $db, '$sql_invoice_versions' ) && false !== strpos( $db, '$sql_payment_handoffs' ) && false !== strpos( $db, '$sql_billing_events' ),
    'database contract includes billing' => false !== strpos( $db, 'billing_columns_exist' ) && false !== strpos( $db, "'billing_profiles'" ) && false !== strpos( $db, "'payment_handoffs'" ),
    'nondestructive migration journal' => false !== strpos( $repo, "MIGRATION_KEY = 'v1_7_0_billing_invoicing_payment_handoffs'" ) && false !== strpos( $repo, "'no_destructive_migration'    => true" ),
    'invoice and handoff schemas' => false !== strpos( $schema, 'sc-engagement-invoice/1.0' ) && false !== strpos( $schema, 'sc-payment-handoff/1.0' ) && false !== strpos( $schema, 'sc-billing-event/1.0' ),
    'governed invoice lifecycle' => false !== strpos( $schema, "'approved_to_issue'" ) && false !== strpos( $schema, "'partially_paid'" ) && false !== strpos( $repo, 'allowed_transitions' ) && false !== strpos( $repo, '$expected = strtoupper' ),
    'versioned issued invoices' => false !== strpos( $repo, 'create_version' ) && false !== strpos( $repo, 'snapshot_json' ) && false !== strpos( $repo, 'content_hash' ) && false !== strpos( $repo, "'issued_at'" ),
    'positive line item required' => false !== strpos( $repo, 'invoice_empty' ) && false !== strpos( $repo, 'unit_amount_minor' ) && false !== strpos( $repo, 'recalculate' ),
    'payment instruments forbidden' => false !== strpos( $schema, 'payment_metadata_forbidden_keys' ) && false !== strpos( $schema, "'card_number'" ) && false !== strpos( $schema, "'routing_number'" ) && false !== strpos( $schema, "'payment_method_token'" ),
    'bounded metadata validation' => false !== strpos( $schema, 'strlen( $encoded ) > 12000' ) && false !== strpos( $schema, 'is_email' ) && false !== strpos( $schema, '13,19' ) && false !== strpos( $schema, 'depth > 5' ),
    'HTTPS handoff boundary' => false !== strpos( $repo, 'payment_handoff_https_required' ) && false !== strpos( $repo, "'https' !== strtolower" ),
    'idempotent handoffs and events' => false !== strpos( $repo, 'idempotency_key' ) && false !== strpos( $repo, 'provider_event_key' ) && false !== strpos( $repo, 'immutable_hash' ),
    'sender projection allowlist' => false !== strpos( $schema, 'sender_projection_keys' ) && false !== strpos( $repo, 'array_intersect_key' ),
    'sender portal billing view' => false !== strpos( $portal, 'Invoices and external payment handoffs' ) && false !== strpos( $portal, 'payment_handoffs' ) && false !== strpos( $portal, 'invoice_number' ),
    'least privilege billing administration' => false !== strpos( $admin, "sc_intake_view_billing" ) && false !== strpos( $admin, "sc_intake_manage_billing" ) && false !== strpos( $admin, 'check_admin_referer' ),
    'admin human confirmation' => false !== strpos( $view, 'Typed confirmation uses: STATUS INVOICE-NUMBER' ) && false !== strpos( $view, 'Record transition' ) && false !== strpos( $view, 'Billing, Invoicing, and Payment Handoffs' ),
    'production readiness contract' => false !== strpos( $platform, 'billing_columns' ) && false !== strpos( $platform, 'billing_migration_journal' ) && false !== strpos( $platform, 'billing_privacy_contract' ) && false !== strpos( $platform, 'billing_operational_blockers' ),
    'live validation workflow' => false !== strpos( $validation, 'billing_invoicing_payment_handoffs' ) && false !== strpos( $validation, "'card_number' => '4242424242424242'" ) && false !== strpos( $validation, 'sc-contact-engagement-live-validation/2.0' ),
    'privacy export and inventory' => false !== strpos( $privacy, 'SC_EI_Billing_Repository::export_for_inquiry' ) && false !== strpos( $privacy, 'sc-engagement-intake-invoices' ) && false !== strpos( $privacy_repo, "'billing_profiles'" ),
    'reviewable billing communications' => false !== strpos( $communications, "'invoice_issued'" ) && false !== strpos( $communications, "'payment_reminder'" ) && false !== strpos( $communications, "'payment_received'" ) && false !== strpos( $communications, "'invoice_voided'" ),
    'uninstall option cleanup' => false !== strpos( $uninstall, "delete_option( 'sc_ei_billing_schema_version' )" ),
    'stable tag advances' => false !== strpos( $readme, 'Stable tag: 1.7.0' ),
);
// The sender projection check above is source-backed; avoid loading WordPress classes in this static contract test.
$checks['sender projection allowlist'] = false !== strpos( $schema, 'sender_projection_keys' ) && false !== strpos( $repo, 'array_intersect_key' ) && false === strpos( substr( $schema, strpos( $schema, 'sender_projection_keys' ), 900 ), 'metadata_json' );
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Billing and payment-handoff checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
echo 'Sustainable Catalyst Contact and Engagement Platform v1.7.0 billing checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
