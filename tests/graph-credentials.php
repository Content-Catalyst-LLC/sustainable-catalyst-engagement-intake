<?php
$plugin = dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake';
$credentials = file_get_contents( $plugin . '/includes/class-sc-ei-graph-credentials.php' );
$crypto = file_get_contents( $plugin . '/includes/class-sc-ei-graph-crypto.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-graph-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/microsoft-graph.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );

$checks = array(
	'credential option is separate from normal settings' => strpos( $credentials, "private const OPTION = 'sc_ei_graph_credentials'" ) !== false,
	'client secret stored only in encrypted vault payload' => strpos( $credentials, "'client_secret'      => \$stored_secret" ) !== false
		&& strpos( $credentials, 'SC_EI_Graph_Crypto::seal_array( $vault )' ) !== false,
	'secret is never redisplayed' => strpos( $view, 'Stored — enter only to replace' ) !== false
		&& strpos( $view, "value=\"<?php echo esc_attr( \$credentials['client_secret']" ) === false,
	'old token cache invalidated on rotation' => strpos( $credentials, '$old_token_key = self::token_cache_key_for_runtime( $current )' ) !== false
		&& strpos( $credentials, 'delete_site_transient( $old_token_key )' ) !== false,
	'encrypted site token cache' => strpos( $credentials, 'SC_EI_Graph_Crypto::seal_array( $payload )' ) !== false
		&& strpos( $credentials, 'set_site_transient' ) !== false
		&& strpos( $credentials, 'SC_EI_Graph_Crypto::open_array' ) !== false,
	'token skew and expiry' => strpos( $credentials, 'graph_token_skew_seconds' ) !== false
		&& strpos( $credentials, "absint( \$token['expires_at'] ) <= time() + \$skew" ) !== false,
	'credential masking' => strpos( $credentials, "'tenant_id_masked'" ) !== false
		&& strpos( $credentials, "'secret_fingerprint'" ) !== false,
	'typed settings confirmation' => strpos( $admin, "'SAVE GRAPH SETTINGS'" ) !== false,
	'typed connector controls' => strpos( $admin, "'TEST GRAPH'" ) !== false
		&& strpos( $admin, "'RESET GRAPH CIRCUIT'" ) !== false
		&& strpos( $admin, "'CLEAR GRAPH TOKEN'" ) !== false,
	'Graph settings capability separated' => strpos( $admin, 'sc_intake_manage_graph_settings' ) !== false,
	'full uninstall clears credential and token state' => strpos( $uninstall, "delete_option( 'sc_ei_graph_credentials' )" ) !== false
		&& strpos( $uninstall, '_site_transient_sc_ei_graph_token_' ) !== false,
	'authenticated encryption algorithms' => strpos( $crypto, 'sodium_crypto_secretbox' ) !== false
		&& strpos( $crypto, 'aes-256-gcm' ) !== false,
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Graph credential checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v1.0.0 Graph credential checks passed.\n";
