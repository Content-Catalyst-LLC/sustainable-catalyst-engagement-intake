<?php
/**
 * Restricted Microsoft Graph REST client for calendar-backed Teams events.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Graph_Client {

	private const GRAPH_RESOURCE = 'https://graph.microsoft.com';
	private const GRAPH_BASE = self::GRAPH_RESOURCE . '/v1.0';
	private const LOGIN_BASE = 'https://login.microsoftonline.com';
	private const CIRCUIT_OPTION = 'sc_ei_graph_circuit';

	public static function health() {
		$credentials = SC_EI_Graph_Credentials::ready();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}
		$path = self::calendar_base_path( $credentials );
		$result = self::request( 'GET', $path . '?$select=id,name,canEdit,owner', null, array(), true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return array(
			'ok'                => true,
			'calendar_id'       => sanitize_text_field( (string) ( $result['data']['id'] ?? '' ) ),
			'calendar_name'     => sanitize_text_field( (string) ( $result['data']['name'] ?? '' ) ),
			'calendar_can_edit' => ! empty( $result['data']['canEdit'] ),
			'organizer_user'    => (string) $credentials['organizer_user'],
			'request_id'        => (string) $result['request_id'],
			'client_request_id' => (string) $result['client_request_id'],
			'response_status'   => absint( $result['status'] ),
			'checked_at'        => current_time( 'mysql', true ),
		);
	}

	public static function create_event( array $payload, string $client_request_id = '' ) {
		$credentials = SC_EI_Graph_Credentials::ready();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}
		$path = self::calendar_base_path( $credentials ) . '/events';
		return self::request(
			'POST',
			$path,
			$payload,
			array( 'client-request-id' => self::client_request_id( $client_request_id ) )
		);
	}

	public static function get_event( string $event_id, string $client_request_id = '' ) {
		$credentials = SC_EI_Graph_Credentials::ready();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}
		$select = implode(
			',',
			array(
				'id',
				'subject',
				'start',
				'end',
				'isOnlineMeeting',
				'onlineMeetingProvider',
				'onlineMeeting',
				'webLink',
				'iCalUId',
				'changeKey',
				'lastModifiedDateTime',
				'isCancelled',
				'transactionId',
			)
		);
		$path = self::events_base_path( $credentials ) . '/' . rawurlencode( $event_id ) . '?$select=' . rawurlencode( $select );
		return self::request(
			'GET',
			$path,
			null,
			array( 'client-request-id' => self::client_request_id( $client_request_id ) )
		);
	}

	public static function delete_event( string $event_id, string $client_request_id = '' ) {
		$credentials = SC_EI_Graph_Credentials::ready();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}
		$path = self::events_base_path( $credentials ) . '/' . rawurlencode( $event_id );
		return self::request(
			'DELETE',
			$path,
			null,
			array( 'client-request-id' => self::client_request_id( $client_request_id ) )
		);
	}

	public static function request(
		string $method,
		string $path,
		?array $body = null,
		array $headers = array(),
		bool $ignore_circuit = false
	) {
		$method = strtoupper( sanitize_key( $method ) );
		if ( ! in_array( $method, array( 'GET', 'POST', 'PATCH', 'DELETE' ), true ) ) {
			return new WP_Error( 'graph_method_invalid', __( 'The Microsoft Graph request method is not allowed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! str_starts_with( $path, '/users/' ) ) {
			return new WP_Error( 'graph_path_invalid', __( 'The Microsoft Graph request path is outside the permitted calendar scope.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! $ignore_circuit ) {
			$circuit = self::circuit_status();
			if ( ! empty( $circuit['open'] ) ) {
				return new WP_Error(
					'graph_circuit_open',
					__( 'Microsoft Graph requests are temporarily paused after repeated failures.', 'sustainable-catalyst-engagement-intake' ),
					array(
						'retryable'     => true,
						'retry_after'   => max( 1, absint( $circuit['open_until'] ) - time() ),
						'circuit_state' => $circuit,
					)
				);
			}
		}

		$client_request_id = self::client_request_id( (string) ( $headers['client-request-id'] ?? '' ) );
		unset( $headers['client-request-id'] );
		$token = self::access_token();
		if ( is_wp_error( $token ) ) {
			self::record_failure( $token->get_error_code(), true );
			return $token;
		}

		$result = self::send( $method, $path, $body, $token, $client_request_id, $headers );
		if (
			is_array( $result )
			&& 401 === absint( $result['status'] ?? 0 )
			&& empty( $result['_token_refresh_attempted'] )
		) {
			SC_EI_Graph_Credentials::clear_token();
			$fresh_token = self::access_token( true );
			if ( ! is_wp_error( $fresh_token ) ) {
				$result = self::send( $method, $path, $body, $fresh_token, $client_request_id, $headers );
				if ( is_array( $result ) ) {
					$result['_token_refresh_attempted'] = true;
				}
			}
		}

		if ( is_wp_error( $result ) ) {
			self::record_failure( $result->get_error_code(), true );
			return $result;
		}
		if ( ! empty( $result['ok'] ) ) {
			self::record_success();
			return $result;
		}

		$retryable = ! empty( $result['retryable'] );
		self::record_failure( (string) ( $result['error_code'] ?? 'graph_request_failed' ), $retryable );
		return new WP_Error(
			(string) ( $result['error_code'] ?: 'graph_request_failed' ),
			(string) ( $result['error_message'] ?: __( 'Microsoft Graph rejected the calendar request.', 'sustainable-catalyst-engagement-intake' ) ),
			$result
		);
	}

	public static function access_token( bool $force = false ) {
		if ( ! $force ) {
			$cached = SC_EI_Graph_Credentials::get_token();
			if ( is_array( $cached ) && ! empty( $cached['access_token'] ) ) {
				return (string) $cached['access_token'];
			}
		}

		$credentials = SC_EI_Graph_Credentials::ready();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}
		$url = self::LOGIN_BASE . '/' . rawurlencode( (string) $credentials['tenant_id'] ) . '/oauth2/v2.0/token';
		$settings = SC_EI_Graph_Credentials::settings();
		$response = wp_safe_remote_post(
			$url,
			array(
				'timeout'     => max( 10, min( 60, absint( $settings['graph_request_timeout_seconds'] ?? 25 ) ) ),
				'redirection' => 0,
				'sslverify'   => true,
				'headers'     => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'Sustainable-Catalyst-Engagement-Intake/' . SC_EI_VERSION,
				),
				'body'        => array(
					'client_id'     => (string) $credentials['client_id'],
					'scope'         => self::GRAPH_RESOURCE . '/.default',
					'client_secret' => (string) $credentials['client_secret'],
					'grant_type'    => 'client_credentials',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'graph_token_transport_failed',
				__( 'Microsoft Entra token acquisition could not reach the identity service.', 'sustainable-catalyst-engagement-intake' ),
				array(
					'retryable' => true,
					'detail'    => self::redact( $response->get_error_message() ),
				)
			);
		}
		$status = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $status || ! is_array( $data ) || empty( $data['access_token'] ) ) {
			$code = sanitize_key( (string) ( $data['error'] ?? 'graph_token_rejected' ) );
			$message = self::redact( (string) ( $data['error_description'] ?? __( 'Microsoft Entra rejected the application credential request.', 'sustainable-catalyst-engagement-intake' ) ) );
			return new WP_Error(
				$code ?: 'graph_token_rejected',
				$message,
				array(
					'retryable' => in_array( $status, array( 408, 429, 500, 502, 503, 504 ), true ),
					'status'    => $status,
				)
			);
		}
		$expires_in = max( 60, absint( $data['expires_in'] ?? 3599 ) );
		SC_EI_Graph_Credentials::set_token( (string) $data['access_token'], $expires_in );
		return (string) $data['access_token'];
	}

	public static function circuit_status(): array {
		$state = wp_parse_args(
			get_option( self::CIRCUIT_OPTION, array() ),
			array(
				'consecutive_failures' => 0,
				'open_until'           => 0,
				'last_error_code'      => '',
				'last_failure_at'      => '',
				'last_success_at'      => '',
			)
		);
		$open_until = absint( $state['open_until'] );
		$state['open'] = $open_until > time();
		if ( ! $state['open'] && $open_until ) {
			$state['open_until'] = 0;
			update_option( self::CIRCUIT_OPTION, $state, false );
		}
		return $state;
	}

	public static function reset_circuit( int $actor_user_id = 0 ): void {
		$state = array(
			'consecutive_failures' => 0,
			'open_until'           => 0,
			'last_error_code'      => '',
			'last_failure_at'      => '',
			'last_success_at'      => current_time( 'mysql', true ),
		);
		update_option( self::CIRCUIT_OPTION, $state, false );
		SC_EI_Audit_Log::record(
			'graph_circuit_reset',
			'Authorized user reset the Microsoft Graph connector circuit breaker.',
			array(),
			null,
			null,
			$actor_user_id
		);
	}

	public static function retry_delay( int $attempt, int $retry_after = 0 ): int {
		$settings = SC_EI_Graph_Credentials::settings();
		$maximum = max( 60, min( DAY_IN_SECONDS, absint( $settings['graph_retry_max_seconds'] ?? 3600 ) ) );
		if ( $retry_after > 0 ) {
			return min( $maximum, max( 1, $retry_after ) );
		}
		$base = max( 15, min( 900, absint( $settings['graph_retry_base_seconds'] ?? 60 ) ) );
		$delay = min( $maximum, $base * ( 2 ** max( 0, min( 8, $attempt - 1 ) ) ) );
		try {
			$jitter = random_int( 0, max( 1, (int) floor( $delay * 0.2 ) ) );
		} catch ( Throwable $error ) {
			$jitter = 0;
		}
		return min( $maximum, $delay + $jitter );
	}

	private static function send(
		string $method,
		string $path,
		?array $body,
		string $token,
		string $client_request_id,
		array $extra_headers
	) {
		$settings = SC_EI_Graph_Credentials::settings();
		$headers = array_merge(
			array(
				'Authorization'            => 'Bearer ' . $token,
				'Accept'                   => 'application/json',
				'Content-Type'             => 'application/json',
				'client-request-id'        => $client_request_id,
				'return-client-request-id' => 'true',
				'Prefer'                   => 'outlook.timezone="UTC"',
				'User-Agent'               => 'Sustainable-Catalyst-Engagement-Intake/' . SC_EI_VERSION,
			),
			$extra_headers
		);
		$args = array(
			'method'      => $method,
			'timeout'     => max( 10, min( 60, absint( $settings['graph_request_timeout_seconds'] ?? 25 ) ) ),
			'redirection' => 0,
			'sslverify'   => true,
			'headers'     => $headers,
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body, JSON_UNESCAPED_SLASHES );
		}
		$response = wp_safe_remote_request( self::GRAPH_BASE . $path, $args );
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'graph_transport_failed',
				__( 'The Microsoft Graph calendar request did not receive a response.', 'sustainable-catalyst-engagement-intake' ),
				array(
					'retryable'        => true,
					'retry_after'      => 0,
					'client_request_id'=> $client_request_id,
					'detail'           => self::redact( $response->get_error_message() ),
					'method'           => $method,
					'path'             => $path,
				)
			);
		}

		$status = absint( wp_remote_retrieve_response_code( $response ) );
		$response_headers = wp_remote_retrieve_headers( $response );
		$raw = (string) wp_remote_retrieve_body( $response );
		$data = '' !== trim( $raw ) ? json_decode( $raw, true ) : array();
		$request_id = sanitize_text_field(
			(string) (
				$response_headers['request-id']
				?? $response_headers['x-ms-request-id']
				?? ( is_array( $data ) ? ( $data['error']['innerError']['request-id'] ?? '' ) : '' )
			)
		);
		$retry_after = self::parse_retry_after( (string) ( $response_headers['retry-after'] ?? '' ) );
		$ok = ( $status >= 200 && $status < 300 );
		$error_code = '';
		$error_message = '';
		if ( ! $ok ) {
			$error_code = sanitize_key( (string) ( is_array( $data ) ? ( $data['error']['code'] ?? '' ) : '' ) );
			if ( '' === $error_code ) {
				$error_code = 'graph_http_' . $status;
			}
			$error_message = self::redact(
				(string) (
					is_array( $data )
						? ( $data['error']['message'] ?? __( 'Microsoft Graph returned an error.', 'sustainable-catalyst-engagement-intake' ) )
						: __( 'Microsoft Graph returned an unreadable error.', 'sustainable-catalyst-engagement-intake' )
				)
			);
		}
		return array(
			'ok'                => $ok,
			'status'            => $status,
			'data'              => is_array( $data ) ? $data : array(),
			'error_code'        => $error_code,
			'error_message'     => $error_message,
			'retryable'         => in_array( $status, array( 408, 425, 429, 500, 502, 503, 504 ), true ),
			'retry_after'       => $retry_after,
			'request_id'        => $request_id,
			'client_request_id' => $client_request_id,
			'method'            => $method,
			'path'              => $path,
		);
	}

	private static function calendar_base_path( array $credentials ): string {
		$user = rawurlencode( (string) $credentials['organizer_user'] );
		if ( ! empty( $credentials['calendar_id'] ) ) {
			return '/users/' . $user . '/calendars/' . rawurlencode( (string) $credentials['calendar_id'] );
		}
		return '/users/' . $user . '/calendar';
	}

	private static function events_base_path( array $credentials ): string {
		return self::calendar_base_path( $credentials ) . '/events';
	}

	private static function client_request_id( string $value = '' ): string {
		$value = strtolower( trim( sanitize_text_field( $value ) ) );
		return preg_match( '/^[a-f0-9-]{36}$/', $value ) ? $value : wp_generate_uuid4();
	}

	private static function parse_retry_after( string $value ): int {
		$value = trim( $value );
		if ( '' === $value ) {
			return 0;
		}
		if ( ctype_digit( $value ) ) {
			return absint( $value );
		}
		$timestamp = strtotime( $value );
		return $timestamp ? max( 0, $timestamp - time() ) : 0;
	}

	private static function record_success(): void {
		$state = self::circuit_status();
		$state['consecutive_failures'] = 0;
		$state['open_until'] = 0;
		$state['last_error_code'] = '';
		$state['last_success_at'] = current_time( 'mysql', true );
		update_option( self::CIRCUIT_OPTION, $state, false );
	}

	private static function record_failure( string $code, bool $counts ): void {
		if ( ! $counts ) {
			return;
		}
		$settings = SC_EI_Graph_Credentials::settings();
		$state = self::circuit_status();
		$state['consecutive_failures'] = absint( $state['consecutive_failures'] ) + 1;
		$state['last_error_code'] = sanitize_key( $code );
		$state['last_failure_at'] = current_time( 'mysql', true );
		$threshold = max( 2, min( 20, absint( $settings['graph_circuit_failure_threshold'] ?? 5 ) ) );
		if ( $state['consecutive_failures'] >= $threshold ) {
			$state['open_until'] = time() + max( 1, absint( $settings['graph_circuit_cooldown_minutes'] ?? 15 ) ) * MINUTE_IN_SECONDS;
		}
		update_option( self::CIRCUIT_OPTION, $state, false );
	}

	private static function redact( string $value ): string {
		$value = preg_replace( '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $value );
		$value = preg_replace( '/eyJ[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{10,}/', '[redacted-token]', $value );
		$value = preg_replace( '/client_secret=[^&\s]+/i', 'client_secret=[redacted]', $value );
		return mb_substr( sanitize_textarea_field( $value ), 0, 2000 );
	}
}
