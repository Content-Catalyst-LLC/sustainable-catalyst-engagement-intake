<?php
/**
 * Main plugin coordinator.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Plugin {

	private static ?self $instance = null;
	private bool $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain(
			'sustainable-catalyst-engagement-intake',
			false,
			dirname( SC_EI_BASENAME ) . '/languages'
		);

		SC_EI_Database::maybe_upgrade();
		SC_EI_Capabilities::install();
		SC_EI_Upload_Environment::register();
		SC_EI_Privacy::register();
		SC_EI_REST::register();
		SC_EI_Form_Handler::register();
		SC_EI_Public::register();
		SC_EI_Retention::register();

		if ( is_admin() ) {
			SC_EI_Admin::register();
		}

		do_action( 'sc_ei_loaded', $this );
	}
}
