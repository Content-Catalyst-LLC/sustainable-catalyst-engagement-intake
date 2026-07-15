<?php
/**
 * Billing, invoice, and external payment-handoff contracts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Billing_Schema {

	public const INVOICE_SCHEMA = 'sc-engagement-invoice/1.0';
	public const PAYMENT_HANDOFF_SCHEMA = 'sc-payment-handoff/1.0';
	public const EVENT_SCHEMA = 'sc-billing-event/1.0';

	public static function invoice_statuses(): array {
		return array(
			'draft'             => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'internal_review'   => __( 'Internal Review', 'sustainable-catalyst-engagement-intake' ),
			'approved_to_issue' => __( 'Approved to Issue', 'sustainable-catalyst-engagement-intake' ),
			'issued'            => __( 'Issued', 'sustainable-catalyst-engagement-intake' ),
			'partially_paid'    => __( 'Partially Paid', 'sustainable-catalyst-engagement-intake' ),
			'paid'              => __( 'Paid', 'sustainable-catalyst-engagement-intake' ),
			'overdue'           => __( 'Overdue', 'sustainable-catalyst-engagement-intake' ),
			'disputed'          => __( 'Disputed', 'sustainable-catalyst-engagement-intake' ),
			'void'              => __( 'Void', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function payment_statuses(): array {
		return array(
			'pending'    => __( 'Pending', 'sustainable-catalyst-engagement-intake' ),
			'authorized' => __( 'Authorized', 'sustainable-catalyst-engagement-intake' ),
			'processing' => __( 'Processing', 'sustainable-catalyst-engagement-intake' ),
			'settled'    => __( 'Settled', 'sustainable-catalyst-engagement-intake' ),
			'failed'     => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'refunded'   => __( 'Refunded', 'sustainable-catalyst-engagement-intake' ),
			'canceled'   => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function providers(): array {
		return array(
			'manual'          => __( 'Manual / External Record', 'sustainable-catalyst-engagement-intake' ),
			'stripe'          => __( 'Stripe', 'sustainable-catalyst-engagement-intake' ),
			'paypal'          => __( 'PayPal', 'sustainable-catalyst-engagement-intake' ),
			'quickbooks'      => __( 'QuickBooks', 'sustainable-catalyst-engagement-intake' ),
			'freshbooks'      => __( 'FreshBooks', 'sustainable-catalyst-engagement-intake' ),
			'bank_transfer'   => __( 'Bank Transfer Handoff', 'sustainable-catalyst-engagement-intake' ),
			'other_external'  => __( 'Other External Provider', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sanitize_currency( string $currency ): string {
		$currency = strtoupper( preg_replace( '/[^A-Z]/', '', strtoupper( $currency ) ) );
		return 3 === strlen( $currency ) ? $currency : 'USD';
	}

	public static function sanitize_status( string $status, array $allowed, string $default ): string {
		$status = sanitize_key( $status );
		return array_key_exists( $status, $allowed ) ? $status : $default;
	}

	public static function sanitize_provider( string $provider ): string {
		return self::sanitize_status( $provider, self::providers(), 'manual' );
	}

	public static function payment_metadata_forbidden_keys(): array {
		return array(
			'card', 'card_number', 'pan', 'cvv', 'cvc', 'expiry', 'expiration', 'account_number',
			'bank_account', 'routing_number', 'iban', 'swift', 'password', 'secret', 'api_key',
			'access_token', 'refresh_token', 'client_secret', 'payment_method_token', 'full_name',
			'email', 'phone', 'address', 'ip', 'ip_address', 'document', 'attachment', 'message',
		);
	}

	public static function payment_metadata_is_safe( array $metadata ): bool {
		$encoded = wp_json_encode( $metadata, JSON_UNESCAPED_SLASHES );
		if ( false === $encoded || strlen( $encoded ) > 12000 ) {
			return false;
		}
		$walk = static function ( array $value, int $depth = 0 ) use ( &$walk ): bool {
			if ( $depth > 5 ) {
				return false;
			}
			foreach ( $value as $key => $item ) {
				$key = sanitize_key( (string) $key );
				if ( in_array( $key, SC_EI_Billing_Schema::payment_metadata_forbidden_keys(), true ) ) {
					return false;
				}
				if ( is_array( $item ) ) {
					if ( ! $walk( $item, $depth + 1 ) ) {
						return false;
					}
					continue;
				}
				if ( is_object( $item ) || is_resource( $item ) ) {
					return false;
				}
				if ( is_string( $item ) ) {
					if ( strlen( $item ) > 1000 || is_email( trim( $item ) ) ) {
						return false;
					}
					if ( preg_match( '/\b(?:\d[ -]*?){13,19}\b/', $item ) || preg_match( '/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $item ) ) {
						return false;
					}
				}
			}
			return true;
		};
		return $walk( $metadata );
	}

	public static function sender_projection_keys(): array {
		return array(
			'public_id', 'invoice_number', 'status', 'currency', 'subtotal_minor', 'tax_minor',
			'total_minor', 'amount_paid_minor', 'balance_due_minor', 'issued_at', 'due_at',
			'paid_at', 'memo', 'current_version', 'line_items', 'payment_handoffs',
		);
	}

	public static function allowed_transitions(): array {
		return array(
			'draft'             => array( 'internal_review', 'void' ),
			'internal_review'   => array( 'draft', 'approved_to_issue', 'void' ),
			'approved_to_issue' => array( 'issued', 'draft', 'void' ),
			'issued'            => array( 'partially_paid', 'paid', 'overdue', 'disputed', 'void' ),
			'partially_paid'    => array( 'paid', 'overdue', 'disputed', 'void' ),
			'overdue'           => array( 'partially_paid', 'paid', 'disputed', 'void' ),
			'disputed'          => array( 'issued', 'partially_paid', 'paid', 'void' ),
			'paid'              => array(),
			'void'              => array(),
		);
	}

	public static function default_settings(): array {
		return array(
			'billing_enabled'                       => 1,
			'billing_default_currency'              => 'USD',
			'billing_default_payment_terms_days'    => 30,
			'billing_sender_portal_enabled'         => 1,
			'billing_external_payment_handoffs'     => 1,
			'billing_store_payment_instruments'     => 0,
			'billing_human_review_required'         => 1,
			'billing_require_https_payment_urls'    => 1,
			'billing_overdue_warning_days'          => 1,
			'billing_retention_days'                => 2555,
		);
	}
}
