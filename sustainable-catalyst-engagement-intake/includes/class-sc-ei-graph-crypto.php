<?php
/**
 * Local encryption envelope for Microsoft Graph credentials and cached tokens.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Graph_Crypto {

	private const CONTEXT = 'sc-ei-microsoft-graph-v1';

	public static function available(): bool {
		return function_exists( 'sodium_crypto_secretbox' )
			|| ( function_exists( 'openssl_encrypt' ) && in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true ) );
	}

	public static function status(): array {
		return array(
			'available' => self::available(),
			'preferred' => function_exists( 'sodium_crypto_secretbox' ) ? 'sodium-secretbox' : 'openssl-aes-256-gcm',
			'sodium'    => function_exists( 'sodium_crypto_secretbox' ),
			'openssl'   => function_exists( 'openssl_encrypt' ) && in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true ),
		);
	}

	public static function seal_array( array $value ) {
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error( 'graph_crypto_encode_failed', __( 'The Microsoft Graph secret could not be encoded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::seal( $json );
	}

	public static function open_array( string $envelope ) {
		$json = self::open( $envelope );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		$value = json_decode( $json, true );
		if ( ! is_array( $value ) ) {
			return new WP_Error( 'graph_crypto_payload_invalid', __( 'The encrypted Microsoft Graph payload is invalid.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return $value;
	}

	public static function seal( string $plaintext ) {
		if ( ! self::available() ) {
			return new WP_Error( 'graph_crypto_unavailable', __( 'Sodium or OpenSSL AES-256-GCM is required to store Microsoft Graph credentials.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$key = self::key();
		try {
			if ( function_exists( 'sodium_crypto_secretbox' ) ) {
				$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $key );
				return wp_json_encode(
					array(
						'v'     => 1,
						'alg'   => 'sodium-secretbox',
						'nonce' => self::b64url_encode( $nonce ),
						'data'  => self::b64url_encode( $ciphertext ),
					),
					JSON_UNESCAPED_SLASHES
				);
			}

			$iv = random_bytes( 12 );
			$tag = '';
			$ciphertext = openssl_encrypt(
				$plaintext,
				'aes-256-gcm',
				$key,
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				self::CONTEXT,
				16
			);
			if ( false === $ciphertext ) {
				throw new RuntimeException( 'OpenSSL encryption failed.' );
			}
			return wp_json_encode(
				array(
					'v'    => 1,
					'alg'  => 'openssl-aes-256-gcm',
					'iv'   => self::b64url_encode( $iv ),
					'tag'  => self::b64url_encode( $tag ),
					'data' => self::b64url_encode( $ciphertext ),
				),
				JSON_UNESCAPED_SLASHES
			);
		} catch ( Throwable $error ) {
			return new WP_Error( 'graph_crypto_seal_failed', __( 'The Microsoft Graph secret could not be encrypted.', 'sustainable-catalyst-engagement-intake' ) );
		}
	}

	public static function open( string $envelope ) {
		if ( '' === trim( $envelope ) ) {
			return new WP_Error( 'graph_crypto_empty', __( 'The encrypted Microsoft Graph payload is empty.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! self::available() ) {
			return new WP_Error( 'graph_crypto_unavailable', __( 'Sodium or OpenSSL AES-256-GCM is required to read Microsoft Graph credentials.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$payload = json_decode( $envelope, true );
		if ( ! is_array( $payload ) || 1 !== absint( $payload['v'] ?? 0 ) || empty( $payload['alg'] ) || empty( $payload['data'] ) ) {
			return new WP_Error( 'graph_crypto_envelope_invalid', __( 'The Microsoft Graph encryption envelope is invalid.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$key = self::key();
		try {
			if ( 'sodium-secretbox' === $payload['alg'] ) {
				if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
					throw new RuntimeException( 'Sodium is unavailable.' );
				}
				$nonce = self::b64url_decode( (string) ( $payload['nonce'] ?? '' ) );
				$ciphertext = self::b64url_decode( (string) $payload['data'] );
				if ( strlen( $nonce ) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
					throw new RuntimeException( 'Invalid nonce.' );
				}
				$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
				if ( false === $plaintext ) {
					throw new RuntimeException( 'Authentication failed.' );
				}
				return $plaintext;
			}

			if ( 'openssl-aes-256-gcm' === $payload['alg'] ) {
				$iv = self::b64url_decode( (string) ( $payload['iv'] ?? '' ) );
				$tag = self::b64url_decode( (string) ( $payload['tag'] ?? '' ) );
				$ciphertext = self::b64url_decode( (string) $payload['data'] );
				$plaintext = openssl_decrypt(
					$ciphertext,
					'aes-256-gcm',
					$key,
					OPENSSL_RAW_DATA,
					$iv,
					$tag,
					self::CONTEXT
				);
				if ( false === $plaintext ) {
					throw new RuntimeException( 'Authentication failed.' );
				}
				return $plaintext;
			}
		} catch ( Throwable $error ) {
			return new WP_Error( 'graph_crypto_open_failed', __( 'The Microsoft Graph encrypted payload could not be authenticated or decrypted.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return new WP_Error( 'graph_crypto_algorithm_invalid', __( 'The Microsoft Graph encryption algorithm is not supported.', 'sustainable-catalyst-engagement-intake' ) );
	}

	public static function fingerprint( string $value ): string {
		return strtoupper( substr( hash_hmac( 'sha256', $value, self::key() ), -12 ) );
	}

	private static function key(): string {
		$material = self::CONTEXT . '|' . wp_salt( 'auth' ) . '|' . wp_salt( 'secure_auth' ) . '|' . wp_salt( 'nonce' );
		if ( function_exists( 'hash_hkdf' ) ) {
			return hash_hkdf( 'sha256', $material, 32, self::CONTEXT, '' );
		}
		return substr( hash_hmac( 'sha256', self::CONTEXT, $material, true ), 0, 32 );
	}

	private static function b64url_encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	private static function b64url_decode( string $value ): string {
		$padding = strlen( $value ) % 4;
		if ( $padding ) {
			$value .= str_repeat( '=', 4 - $padding );
		}
		$decoded = base64_decode( strtr( $value, '-_', '+/' ), true );
		return false === $decoded ? '' : $decoded;
	}
}
