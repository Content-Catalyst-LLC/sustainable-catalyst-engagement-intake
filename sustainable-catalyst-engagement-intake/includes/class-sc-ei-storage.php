<?php
/**
 * Protected private storage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Storage {

	private const MARKER = '.sc-ei-private-storage';
	private const FALLBACK_DIR = 'sc-engagement-intake-private';

	public static function base_dir(): string {
		$defaults = class_exists( 'SC_EI_Admin' ) ? SC_EI_Admin::default_settings() : array( 'private_storage_path' => '' );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), $defaults );

		if ( defined( 'SC_EI_PRIVATE_STORAGE_PATH' ) && is_string( SC_EI_PRIVATE_STORAGE_PATH ) && '' !== trim( SC_EI_PRIVATE_STORAGE_PATH ) ) {
			return self::normalize_path( SC_EI_PRIVATE_STORAGE_PATH );
		}

		$locked = get_option( 'sc_ei_storage_base_dir', '' );
		if ( is_string( $locked ) && '' !== trim( $locked ) ) {
			return self::normalize_path( $locked );
		}

		if ( ! empty( $settings['private_storage_path'] ) ) {
			return self::normalize_path( (string) $settings['private_storage_path'] );
		}

		$wordpress_root = self::normalize_path( ABSPATH );
		$web_root       = self::web_root();
		$parent         = self::normalize_path( dirname( untrailingslashit( $web_root ) ) );
		$outside        = self::normalize_path( trailingslashit( $parent ) . self::FALLBACK_DIR );

		if ( self::parent_is_writable( $outside ) && ! self::path_is_within( $outside, $web_root ) ) {
			return $outside;
		}

		return self::normalize_path( trailingslashit( WP_CONTENT_DIR ) . self::FALLBACK_DIR );
	}

	public static function ensure(): bool {
		$base = self::base_dir();

		if ( ! wp_mkdir_p( $base ) ) {
			return false;
		}

		foreach ( array( 'quarantine', 'approved' ) as $directory ) {
			if ( ! wp_mkdir_p( trailingslashit( $base ) . $directory ) ) {
				return false;
			}
		}

		self::write_protection_files( $base );
		self::write_protection_files( trailingslashit( $base ) . 'quarantine' );
		self::write_protection_files( trailingslashit( $base ) . 'approved' );

		$marker = trailingslashit( $base ) . self::MARKER;
		if ( ! file_exists( $marker ) ) {
			@file_put_contents( $marker, "Sustainable Catalyst Engagement Intake private storage\n" . SC_EI_VERSION . "\n", LOCK_EX );
			@chmod( $marker, 0600 );
		}

		return is_dir( $base ) && is_writable( $base ) && file_exists( $marker );
	}

	public static function storage_health(): array {
		$base            = self::base_dir();
		$root            = self::normalize_path( ABSPATH );
		$document_root   = self::web_root();

		$utilization = self::utilization();
		$permissions = is_dir( $base ) ? substr( sprintf( '%o', fileperms( $base ) ), -4 ) : '';

		return array(
			'path'                  => $base,
			'exists'                => is_dir( $base ),
			'writable'              => is_dir( $base ) && is_writable( $base ),
			'marker'                => file_exists( trailingslashit( $base ) . self::MARKER ),
			'outside_wordpress_root'=> ! self::path_is_within( $base, $root ),
			'outside_document_root' => ! self::path_is_within( $base, $document_root ),
			'document_root'         => $document_root,
			'constant_override'     => defined( 'SC_EI_PRIVATE_STORAGE_PATH' ),
			'locked_path'           => (string) get_option( 'sc_ei_storage_base_dir', '' ),
			'protection_files'      => self::protection_files_exist( $base ),
			'base_is_symlink'       => is_link( $base ),
			'base_permissions'      => $permissions,
			'quarantine_writable'   => is_dir( trailingslashit( $base ) . 'quarantine' ) && is_writable( trailingslashit( $base ) . 'quarantine' ),
			'approved_writable'     => is_dir( trailingslashit( $base ) . 'approved' ) && is_writable( trailingslashit( $base ) . 'approved' ),
			'managed_files'         => $utilization['managed_files'],
			'managed_bytes'         => $utilization['managed_bytes'],
			'staging_files'         => $utilization['staging_files'],
			'disk_free_bytes'       => $utilization['disk_free_bytes'],
			'disk_total_bytes'      => $utilization['disk_total_bytes'],
		);
	}

	public static function quarantine_relative_path( string $inquiry_public_id, string $attachment_public_id ): string {
		$year_month = gmdate( 'Y/m' );
		$prefix     = substr( hash( 'sha256', $inquiry_public_id ), 0, 2 );

		return implode(
			'/',
			array(
				'quarantine',
				$year_month,
				$prefix,
				sanitize_key( $inquiry_public_id ),
				sanitize_key( $attachment_public_id ) . '.qtn',
			)
		);
	}

	public static function approved_relative_path( string $relative_path ): string {
		$relative_path = self::sanitize_relative_path( $relative_path );
		if ( str_starts_with( $relative_path, 'quarantine/' ) ) {
			return 'approved/' . substr( $relative_path, strlen( 'quarantine/' ) );
		}
		return $relative_path;
	}

	public static function absolute_path( string $relative_path ): ?string {
		$relative = self::sanitize_relative_path( $relative_path );
		if ( '' === $relative ) {
			return null;
		}

		$base      = trailingslashit( self::base_dir() );
		$absolute  = self::normalize_path( $base . $relative );

		if ( ! self::path_is_within( $absolute, self::normalize_path( $base ) ) ) {
			return null;
		}

		return $absolute;
	}

	public static function store_uploaded_file( string $temporary_path, string $relative_path ): bool {
		$size   = is_file( $temporary_path ) ? (int) filesize( $temporary_path ) : 0;
		$sha256 = is_file( $temporary_path ) ? hash_file( 'sha256', $temporary_path ) : '';

		$result = self::store_uploaded_file_verified(
			$temporary_path,
			$relative_path,
			$size,
			is_string( $sha256 ) ? $sha256 : ''
		);

		return ! is_wp_error( $result );
	}

	public static function store_uploaded_file_verified(
		string $temporary_path,
		string $relative_path,
		int $expected_size,
		string $expected_sha256
	) {
		if ( ! self::ensure() ) {
			return new WP_Error( 'storage_unavailable', __( 'Protected storage could not be initialized.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( ! is_file( $temporary_path ) || ! is_readable( $temporary_path ) ) {
			return new WP_Error( 'temporary_file_unreadable', __( 'The server temporary upload file is unavailable or unreadable.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$transaction_base = self::base_dir();
		$destination      = self::absolute_path( $relative_path );
		if ( ! $destination ) {
			return new WP_Error( 'storage_path_invalid', __( 'The protected storage path could not be resolved safely.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$directory = dirname( $destination );
		if ( ! wp_mkdir_p( $directory ) ) {
			return new WP_Error( 'storage_directory_failed', __( 'The protected storage directory could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::write_protection_files( $directory, true );
		@chmod( $directory, 0700 );

		if ( file_exists( $destination ) ) {
			return new WP_Error( 'storage_collision', __( 'A protected file already exists at the generated destination.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$staging = $destination . '.part-' . str_replace( '-', '', wp_generate_uuid4() );
		$moved   = is_uploaded_file( $temporary_path ) && move_uploaded_file( $temporary_path, $staging );

		if ( ! $moved && apply_filters( 'sc_ei_allow_non_http_upload_move', false, $temporary_path, $staging ) ) {
			$moved = @rename( $temporary_path, $staging );
		}

		if ( ! $moved || ! is_file( $staging ) ) {
			return new WP_Error( 'storage_move_failed', __( 'The validated file could not be moved into protected staging storage.', 'sustainable-catalyst-engagement-intake' ) );
		}

		@chmod( $staging, 0600 );
		clearstatcache( true, $staging );

		$actual_size = (int) filesize( $staging );
		$actual_hash = hash_file( 'sha256', $staging );
		$size_ok     = $expected_size > 0 && $actual_size === $expected_size;
		$hash_ok     = is_string( $actual_hash )
			&& preg_match( '/^[a-f0-9]{64}$/', strtolower( $expected_sha256 ) )
			&& hash_equals( strtolower( $expected_sha256 ), strtolower( $actual_hash ) );

		if ( ! $size_ok || ! $hash_ok ) {
			wp_delete_file( $staging );
			return new WP_Error(
				'post_move_verification_failed',
				__( 'The file changed or was truncated while moving into protected storage, so it was removed.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		if ( ! @rename( $staging, $destination ) ) {
			wp_delete_file( $staging );
			return new WP_Error( 'storage_commit_failed', __( 'The protected file could not be committed atomically.', 'sustainable-catalyst-engagement-intake' ) );
		}

		@chmod( $destination, 0600 );
		clearstatcache( true, $destination );

		if ( ! is_file( $destination ) || (int) filesize( $destination ) !== $expected_size || ! self::verify_integrity( $relative_path, $expected_sha256 ) ) {
			wp_delete_file( $destination );
			return new WP_Error( 'storage_commit_verification_failed', __( 'The committed protected file failed its final integrity verification and was removed.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( ! defined( 'SC_EI_PRIVATE_STORAGE_PATH' ) && false === get_option( 'sc_ei_storage_base_dir', false ) ) {
			$locked = add_option( 'sc_ei_storage_base_dir', $transaction_base, '', false );
			if ( ! $locked ) {
				$current_lock = (string) get_option( 'sc_ei_storage_base_dir', '' );
				if ( wp_normalize_path( untrailingslashit( $current_lock ) ) !== wp_normalize_path( untrailingslashit( $transaction_base ) ) ) {
					wp_delete_file( $destination );
					return new WP_Error( 'storage_lock_race', __( 'The protected storage path changed during the upload transaction, so the file was removed.', 'sustainable-catalyst-engagement-intake' ) );
				}
			}
		}

		return array(
			'relative_path' => $relative_path,
			'absolute_path' => $destination,
			'size_bytes'    => $actual_size,
			'sha256'        => strtolower( $actual_hash ),
		);
	}

	public static function move_to_approved( string $relative_path ): ?string {
		$source = self::absolute_path( $relative_path );
		if ( ! $source || ! is_file( $source ) ) {
			return null;
		}

		$approved_relative = self::approved_relative_path( $relative_path );
		$destination       = self::absolute_path( $approved_relative );
		if ( ! $destination ) {
			return null;
		}

		if ( ! wp_mkdir_p( dirname( $destination ) ) || file_exists( $destination ) ) {
			return null;
		}
		self::write_protection_files( dirname( $destination ) );

		if ( ! @rename( $source, $destination ) ) {
			return null;
		}

		@chmod( $destination, 0600 );
		return $approved_relative;
	}

	public static function move_to_quarantine( string $relative_path ): ?string {
		$source = self::absolute_path( $relative_path );
		if ( ! $source || ! is_file( $source ) ) {
			return null;
		}

		$relative_path = self::sanitize_relative_path( $relative_path );
		$quarantine_relative = str_starts_with( $relative_path, 'approved/' )
			? 'quarantine/' . substr( $relative_path, strlen( 'approved/' ) )
			: $relative_path;

		if ( $quarantine_relative === $relative_path ) {
			return $relative_path;
		}

		$destination = self::absolute_path( $quarantine_relative );
		if ( ! $destination || ! wp_mkdir_p( dirname( $destination ) ) || file_exists( $destination ) ) {
			return null;
		}
		self::write_protection_files( dirname( $destination ) );

		if ( ! @rename( $source, $destination ) ) {
			return null;
		}

		@chmod( $destination, 0600 );
		return $quarantine_relative;
	}

	public static function delete_file( string $relative_path ): bool {
		$absolute = self::absolute_path( $relative_path );
		if ( ! $absolute ) {
			return false;
		}
		if ( ! is_file( $absolute ) ) {
			return true;
		}

		return wp_delete_file( $absolute );
	}

	public static function verify_integrity( string $relative_path, string $expected_sha256 ): bool {
		$absolute = self::absolute_path( $relative_path );
		if ( ! $absolute || ! is_file( $absolute ) || ! preg_match( '/^[a-f0-9]{64}$/', $expected_sha256 ) ) {
			return false;
		}

		$actual = hash_file( 'sha256', $absolute );
		return is_string( $actual ) && hash_equals( strtolower( $expected_sha256 ), strtolower( $actual ) );
	}

	public static function stream_download( array $attachment ): void {
		$absolute = self::absolute_path( (string) $attachment['relative_path'] );
		if ( ! $absolute || ! is_file( $absolute ) ) {
			wp_die( esc_html__( 'The private file is unavailable.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}

		$filename = sanitize_file_name( (string) $attachment['original_name'] );
		if ( '' === $filename ) {
			$filename = 'private-document.' . sanitize_key( (string) $attachment['extension'] );
		}

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: DENY' );
		header( "Content-Security-Policy: sandbox; default-src 'none'" );
		header( 'Referrer-Policy: no-referrer' );
		header( 'Cross-Origin-Resource-Policy: same-origin' );
		header( 'X-Download-Options: noopen' );
		$download_mime = 'approved' === (string) ( $attachment['quarantine_status'] ?? '' )
			? sanitize_mime_type( (string) $attachment['mime_type'] )
			: 'application/octet-stream';
		header( 'Content-Type: ' . $download_mime );
		header( 'Content-Length: ' . (string) filesize( $absolute ) );
		header( 'Content-Disposition: attachment; filename="' . self::ascii_filename( $filename ) . '"; filename*=UTF-8\'\'' . rawurlencode( $filename ) );

		$handle = fopen( $absolute, 'rb' );
		if ( false === $handle ) {
			wp_die( esc_html__( 'The private file could not be opened.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 500 ) );
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, 1024 * 1024 );
			if ( false === $buffer ) {
				break;
			}
			echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			flush();
		}

		fclose( $handle );
		exit;
	}

	public static function repair(): array {
		$base = self::base_dir();
		$ok   = self::ensure();

		foreach ( array( $base, trailingslashit( $base ) . 'quarantine', trailingslashit( $base ) . 'approved' ) as $directory ) {
			if ( is_dir( $directory ) ) {
				@chmod( $directory, 0700 );
				self::write_protection_files( $directory, true );
			}
		}

		$stale = self::cleanup_stale_staging_files();
		$probe = self::probe();

		return array(
			'ok'                    => $ok && ! empty( $probe['ok'] ),
			'storage'               => self::storage_health(),
			'probe'                 => $probe,
			'stale_staging_deleted' => $stale,
		);
	}

	public static function probe(): array {
		$started = microtime( true );
		$base    = self::base_dir();
		$result  = array(
			'ok'           => false,
			'write'        => false,
			'read'         => false,
			'rename'       => false,
			'delete'       => false,
			'completed_at' => current_time( 'mysql', true ),
			'message'      => '',
		);

		if ( ! self::ensure() ) {
			$result['message'] = 'Protected storage could not be initialized.';
			update_option( 'sc_ei_last_storage_probe', $result, false );
			return $result;
		}

		try {
			$token = bin2hex( random_bytes( 24 ) );
		} catch ( Throwable $exception ) {
			$result['message'] = 'Cryptographic probe token generation failed.';
			update_option( 'sc_ei_last_storage_probe', $result, false );
			return $result;
		}

		$first   = trailingslashit( $base ) . '.probe-' . str_replace( '-', '', wp_generate_uuid4() ) . '.tmp';
		$second  = $first . '.renamed';
		$written = @file_put_contents( $first, $token, LOCK_EX );

		$result['write'] = false !== $written && is_file( $first );
		if ( $result['write'] ) {
			@chmod( $first, 0600 );
			$contents       = @file_get_contents( $first );
			$result['read'] = is_string( $contents ) && hash_equals( $token, $contents );
			$result['rename'] = @rename( $first, $second ) && is_file( $second );
			$delete_path      = $result['rename'] ? $second : $first;
			$result['delete'] = ! file_exists( $delete_path ) || wp_delete_file( $delete_path );
		}

		$result['ok'] = $result['write'] && $result['read'] && $result['rename'] && $result['delete'];
		$result['duration_seconds'] = round( microtime( true ) - $started, 4 );
		$result['message'] = $result['ok']
			? 'Protected storage write, read, rename, and delete probe passed.'
			: 'At least one protected storage probe stage failed.';

		update_option( 'sc_ei_last_storage_probe', $result, false );
		return $result;
	}

	public static function latest_probe(): array {
		$probe = get_option( 'sc_ei_last_storage_probe', array() );
		return is_array( $probe ) ? $probe : array();
	}

	public static function managed_file_inventory( int $limit = 3000 ): array {
		$base  = self::base_dir();
		$files = array();
		$staging = array();
		$truncated = false;

		if ( ! is_dir( $base ) ) {
			return array( 'files' => array(), 'staging' => array(), 'truncated' => false );
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $item ) {
			if ( $item->isLink() || ! $item->isFile() ) {
				continue;
			}

			$absolute = wp_normalize_path( $item->getPathname() );
			$relative = ltrim( substr( $absolute, strlen( trailingslashit( wp_normalize_path( $base ) ) ) ), '/' );

			if ( str_contains( $relative, '.part-' ) ) {
				if ( count( $staging ) < 100 ) {
					$staging[] = array(
						'relative_path' => $relative,
						'size_bytes'    => (int) $item->getSize(),
						'modified_at'   => gmdate( 'Y-m-d H:i:s', $item->getMTime() ),
					);
				}
				continue;
			}

			if ( ! str_ends_with( strtolower( $relative ), '.qtn' ) ) {
				continue;
			}

			if ( count( $files ) >= $limit ) {
				$truncated = true;
				break;
			}

			$files[] = array(
				'relative_path' => $relative,
				'size_bytes'    => (int) $item->getSize(),
				'modified_at'   => gmdate( 'Y-m-d H:i:s', $item->getMTime() ),
			);
		}

		return array(
			'files'     => $files,
			'staging'   => $staging,
			'truncated' => $truncated,
		);
	}

	public static function utilization(): array {
		$inventory = self::managed_file_inventory( 10000 );
		$bytes     = array_sum( array_map( static fn( array $file ): int => (int) $file['size_bytes'], $inventory['files'] ) );
		$base      = self::base_dir();

		return array(
			'managed_files'   => count( $inventory['files'] ),
			'managed_bytes'   => $bytes,
			'staging_files'   => count( $inventory['staging'] ),
			'inventory_capped'=> ! empty( $inventory['truncated'] ),
			'disk_free_bytes' => is_dir( $base ) ? max( 0, (int) @disk_free_space( $base ) ) : 0,
			'disk_total_bytes'=> is_dir( $base ) ? max( 0, (int) @disk_total_space( $base ) ) : 0,
		);
	}

	public static function cleanup_stale_staging_files( int $older_than_seconds = HOUR_IN_SECONDS ): int {
		$base = self::base_dir();
		if ( ! is_dir( $base ) ) {
			return 0;
		}

		$deleted  = 0;
		$cutoff   = time() - max( 300, $older_than_seconds );
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $item ) {
			if ( $item->isLink() || ! $item->isFile() || ! str_contains( $item->getFilename(), '.part-' ) ) {
				continue;
			}
			if ( $item->getMTime() <= $cutoff && wp_delete_file( $item->getPathname() ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	public static function delete_storage_tree(): bool {
		$base   = self::base_dir();
		$marker = trailingslashit( $base ) . self::MARKER;

		if ( ! is_dir( $base ) || ! file_exists( $marker ) ) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( $item->isLink() ) {
				wp_delete_file( $item->getPathname() );
			} elseif ( $item->isDir() ) {
				@rmdir( $item->getPathname() );
			} else {
				wp_delete_file( $item->getPathname() );
			}
		}

		return @rmdir( $base );
	}

	private static function write_protection_files( string $directory, bool $force = false ): void {
		if ( ! is_dir( $directory ) || ! is_writable( $directory ) ) {
			return;
		}

		$files = array(
			'.htaccess' => "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|py|sh|js|html?)$\">\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n</FilesMatch>\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><directoryBrowse enabled=\"false\"/><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>\n",
			'index.php'  => "<?php\nhttp_response_code( 403 );\nexit;\n",
			'index.html' => '',
		);

		foreach ( $files as $name => $contents ) {
			$path = trailingslashit( $directory ) . $name;
			if ( $force || ! file_exists( $path ) ) {
				@file_put_contents( $path, $contents, LOCK_EX );
				@chmod( $path, 0600 );
			}
		}
	}

	private static function protection_files_exist( string $directory ): bool {
		foreach ( array( '.htaccess', 'web.config', 'index.php' ) as $file ) {
			if ( ! file_exists( trailingslashit( $directory ) . $file ) ) {
				return false;
			}
		}
		return true;
	}

	private static function sanitize_relative_path( string $path ): string {
		$path = str_replace( '\\', '/', trim( $path ) );
		$path = ltrim( $path, '/' );

		if ( '' === $path || str_contains( $path, "\0" ) || str_contains( $path, '../' ) || str_contains( $path, '/..' ) ) {
			return '';
		}

		$parts = array_filter( explode( '/', $path ), static fn( string $part ): bool => '' !== $part && '.' !== $part && '..' !== $part );
		foreach ( $parts as $part ) {
			if ( ! preg_match( '/^[A-Za-z0-9._-]+$/', $part ) ) {
				return '';
			}
		}

		return implode( '/', $parts );
	}

	private static function normalize_path( string $path ): string {
		$path = wp_normalize_path( $path );
		return untrailingslashit( $path );
	}

	private static function path_is_within( string $path, string $root ): bool {
		$path = trailingslashit( strtolower( self::normalize_path( $path ) ) );
		$root = trailingslashit( strtolower( self::normalize_path( $root ) ) );
		return str_starts_with( $path, $root );
	}

	private static function web_root(): string {
		$document_root = isset( $_SERVER['DOCUMENT_ROOT'] )
			? trim( wp_unslash( (string) $_SERVER['DOCUMENT_ROOT'] ) )
			: '';

		if ( '' !== $document_root && is_dir( $document_root ) ) {
			return self::normalize_path( $document_root );
		}

		return self::normalize_path( ABSPATH );
	}

	private static function parent_is_writable( string $path ): bool {
		$parent = dirname( $path );
		return is_dir( $parent ) && is_writable( $parent );
	}

	private static function ascii_filename( string $filename ): string {
		$ascii = remove_accents( $filename );
		$ascii = preg_replace( '/[^A-Za-z0-9._-]+/', '-', $ascii );
		return trim( (string) $ascii, '-.' ) ?: 'private-document';
	}
}
