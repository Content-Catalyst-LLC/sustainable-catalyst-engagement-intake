<?php
/**
 * Microsoft Graph application credential vault and non-secret connector settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Graph_Credentials {

	private const OPTION = 'sc_ei_graph_credentials';
	private const TOKEN_TRANSIENT_PREFIX = 'sc_ei_graph_token_';

	public static function defaults(): array {
		return array(
			'graph_enabled'                    => 0,
			'graph_tenant_id'                  => '',
			'graph_client_id'                  => '',
			'graph_organizer_user'             => '',
			'graph_calendar_id'                => '',
			'graph_secret_expires_at'          => '',
			'graph_include_sender_attendee'    => 0,
			'graph_require_calendar_consent'   => 1,
			'graph_allow_remote_cancel'        => 1,
			'graph_retry_enabled'              => 1,
			'graph_max_attempts'               => 6,
			'graph_retry_base_seconds'         => 60,
			'graph_retry_max_seconds'          => 3600,
			'graph_request_timeout_seconds'    => 25,
			'graph_token_skew_seconds'         => 300,
			'graph_circuit_failure_threshold'  => 5,
			'graph_circuit_cooldown_minutes'   => 15,
			'graph_reconcile_delay_seconds'    => 30,
			'graph_global_cloud_only'          => 1,
		);
	}

	public static function settings(): array {
		return wp_parse_args( get_option( 'sc_ei_settings', array() ), self::defaults() );
	}

	public static function save( array $input, int $actor_user_id ) {
		$current = self::runtime();
		$old_token_key = self::token_cache_key_for_runtime( $current );
		$tenant = self::sanitize_tenant( (string) ( $input['graph_tenant_id'] ?? '' ) );
		$client = self::sanitize_guid( (string) ( $input['graph_client_id'] ?? '' ) );
		$organizer = sanitize_email( (string) ( $input['graph_organizer_user'] ?? '' ) );
		$calendar = sanitize_text_field( (string) ( $input['graph_calendar_id'] ?? '' ) );
		$secret = trim( (string) ( $input['graph_client_secret'] ?? '' ) );
		$clear_secret = ! empty( $input['graph_clear_client_secret'] );

		if ( '' !== $tenant && ! self::valid_tenant( $tenant ) ) {
			return new WP_Error( 'graph_tenant_invalid', __( 'Enter a valid Microsoft Entra tenant GUID or verified tenant domain.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' !== $client && ! self::valid_guid( $client ) ) {
			return new WP_Error( 'graph_client_invalid', __( 'Enter a valid Microsoft Entra application client ID.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' !== $organizer && ! is_email( $organizer ) ) {
			return new WP_Error( 'graph_organizer_invalid', __( 'Enter a valid Microsoft 365 organizer user principal name.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' !== $secret && strlen( $secret ) < 12 ) {
			return new WP_Error( 'graph_secret_invalid', __( 'The Microsoft Graph client secret appears incomplete.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' !== $secret && ! SC_EI_Graph_Crypto::available() ) {
			return new WP_Error( 'graph_crypto_unavailable', __( 'The client secret cannot be stored because no supported encryption extension is available.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$stored_secret = is_array( $current ) ? (string) ( $current['client_secret'] ?? '' ) : '';
		if ( $clear_secret ) {
			$stored_secret = '';
		} elseif ( '' !== $secret ) {
			$stored_secret = $secret;
		}

		$vault = array(
			'tenant_id'          => $tenant,
			'client_id'          => $client,
			'client_secret'      => $stored_secret,
			'organizer_user'     => strtolower( $organizer ),
			'calendar_id'        => $calendar,
			'secret_expires_at'  => self::sanitize_date( (string) ( $input['graph_secret_expires_at'] ?? '' ) ),
			'secret_fingerprint' => '' !== $stored_secret ? SC_EI_Graph_Crypto::fingerprint( $stored_secret ) : '',
			'updated_by'         => $actor_user_id,
			'updated_at'         => current_time( 'mysql', true ),
		);
		$sealed = SC_EI_Graph_Crypto::seal_array( $vault );
		if ( is_wp_error( $sealed ) ) {
			return $sealed;
		}
		update_option( self::OPTION, $sealed, false );
		if ( '' !== $old_token_key ) {
			delete_site_transient( $old_token_key );
		}
		self::clear_token();

		SC_EI_Audit_Log::record(
			'graph_credentials_updated',
			'Authorized administrator updated encrypted Microsoft Graph connector credentials.',
			array(
				'tenant_fingerprint' => '' !== $tenant ? substr( hash( 'sha256', strtolower( $tenant ) ), 0, 12 ) : '',
				'client_fingerprint' => '' !== $client ? substr( hash( 'sha256', strtolower( $client ) ), 0, 12 ) : '',
				'organizer_hash'     => '' !== $organizer ? hash_hmac( 'sha256', strtolower( $organizer ), wp_salt( 'secure_auth' ) ) : '',
				'calendar_set'       => '' !== $calendar,
				'secret_set'         => '' !== $stored_secret,
				'secret_replaced'    => '' !== $secret,
				'secret_cleared'     => $clear_secret,
			),
			null,
			null,
			$actor_user_id
		);
		return self::public_status();
	}

	public static function runtime() {
		$envelope = (string) get_option( self::OPTION, '' );
		if ( '' === $envelope ) {
			return array(
				'tenant_id'          => '',
				'client_id'          => '',
				'client_secret'      => '',
				'organizer_user'     => '',
				'calendar_id'        => '',
				'secret_expires_at'  => '',
				'secret_fingerprint' => '',
				'updated_by'         => 0,
				'updated_at'         => '',
			);
		}
		return SC_EI_Graph_Crypto::open_array( $envelope );
	}

	public static function public_status(): array {
		$runtime = self::runtime();
		if ( is_wp_error( $runtime ) ) {
			return array(
				'configured'         => false,
				'decryptable'        => false,
				'error'              => $runtime->get_error_code(),
				'tenant_id_masked'   => '',
				'client_id_masked'   => '',
				'organizer_user'     => '',
				'calendar_id_masked' => '',
				'secret_set'         => false,
				'secret_fingerprint' => '',
				'secret_expires_at'  => '',
				'secret_expired'     => false,
				'updated_at'         => '',
			);
		}
		$complete = ! empty( $runtime['tenant_id'] )
			&& ! empty( $runtime['client_id'] )
			&& ! empty( $runtime['client_secret'] )
			&& ! empty( $runtime['organizer_user'] );
		$expires = (string) ( $runtime['secret_expires_at'] ?? '' );
		return array(
			'configured'         => $complete,
			'decryptable'        => true,
			'error'              => '',
			'tenant_id_masked'   => self::mask( (string) $runtime['tenant_id'] ),
			'client_id_masked'   => self::mask( (string) $runtime['client_id'] ),
			'organizer_user'     => (string) $runtime['organizer_user'],
			'calendar_id_masked' => self::mask( (string) $runtime['calendar_id'] ),
			'secret_set'         => ! empty( $runtime['client_secret'] ),
			'secret_fingerprint' => (string) ( $runtime['secret_fingerprint'] ?? '' ),
			'secret_expires_at'  => $expires,
			'secret_expired'     => '' !== $expires && strtotime( $expires . ' UTC' ) < time(),
			'updated_at'         => (string) ( $runtime['updated_at'] ?? '' ),
		);
	}

	public static function token_cache_key(): string {
		return self::token_cache_key_for_runtime( self::runtime() );
	}

	private static function token_cache_key_for_runtime( $runtime ): string {
		if ( is_wp_error( $runtime ) || ! is_array( $runtime ) ) {
			return self::TOKEN_TRANSIENT_PREFIX . 'invalid';
		}
		return self::TOKEN_TRANSIENT_PREFIX . substr(
			hash(
				'sha256',
				strtolower( (string) ( $runtime['tenant_id'] ?? '' ) )
				. '|'
				. strtolower( (string) ( $runtime['client_id'] ?? '' ) )
				. '|'
				. (string) ( $runtime['secret_fingerprint'] ?? '' )
			),
			0,
			24
		);
	}

	public static function get_token() {
		$sealed = get_site_transient( self::token_cache_key() );
		if ( ! is_string( $sealed ) || '' === $sealed ) {
			return null;
		}
		$token = SC_EI_Graph_Crypto::open_array( $sealed );
		if ( is_wp_error( $token ) ) {
			self::clear_token();
			return null;
		}
		$skew = max( 60, absint( self::settings()['graph_token_skew_seconds'] ?? 300 ) );
		if ( empty( $token['access_token'] ) || empty( $token['expires_at'] ) || absint( $token['expires_at'] ) <= time() + $skew ) {
			self::clear_token();
			return null;
		}
		return $token;
	}

	public static function set_token( string $access_token, int $expires_in ): bool {
		$expires_in = max( 60, $expires_in );
		$payload = array(
			'access_token' => $access_token,
			'expires_at'   => time() + $expires_in,
			'cached_at'    => time(),
		);
		$sealed = SC_EI_Graph_Crypto::seal_array( $payload );
		if ( is_wp_error( $sealed ) ) {
			return false;
		}
		return set_site_transient( self::token_cache_key(), $sealed, $expires_in );
	}

	public static function clear_token(): void {
		delete_site_transient( self::token_cache_key() );
	}

	public static function ready() {
		$settings = self::settings();
		$runtime = self::runtime();
		if ( empty( $settings['graph_enabled'] ) ) {
			return new WP_Error( 'graph_disabled', __( 'Microsoft Graph integration is disabled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( is_wp_error( $runtime ) ) {
			return $runtime;
		}
		foreach ( array( 'tenant_id', 'client_id', 'client_secret', 'organizer_user' ) as $field ) {
			if ( empty( $runtime[ $field ] ) ) {
				return new WP_Error( 'graph_credentials_incomplete', __( 'Microsoft Graph credentials are incomplete.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}
		if ( ! empty( $runtime['secret_expires_at'] ) && strtotime( $runtime['secret_expires_at'] . ' UTC' ) < time() ) {
			return new WP_Error( 'graph_secret_expired', __( 'The Microsoft Graph client secret is marked expired.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return $runtime;
	}

	private static function sanitize_tenant( string $value ): string {
		return strtolower( trim( sanitize_text_field( $value ) ) );
	}

	private static function sanitize_guid( string $value ): string {
		return strtolower( trim( sanitize_text_field( $value ) ) );
	}

	private static function valid_guid( string $value ): bool {
		return (bool) preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5a-f0-9][a-f0-9]{3}-[89ab0-9][a-f0-9]{3}-[a-f0-9]{12}$/i', $value );
	}

	private static function valid_tenant( string $value ): bool {
		return self::valid_guid( $value )
			|| (bool) preg_match( '/^(?=.{3,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $value );
	}

	private static function sanitize_date( string $value ): string {
		$value = trim( sanitize_text_field( $value ) );
		if ( '' === $value ) {
			return '';
		}
		$timestamp = strtotime( $value . ' UTC' );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : '';
	}

	private static function mask( string $value ): string {
		if ( '' === $value ) {
			return '';
		}
		$length = strlen( $value );
		if ( $length <= 8 ) {
			return str_repeat( '•', $length );
		}
		return substr( $value, 0, 4 ) . str_repeat( '•', max( 4, $length - 8 ) ) . substr( $value, -4 );
	}
}
