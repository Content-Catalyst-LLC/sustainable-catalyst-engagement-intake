<?php
/**
 * Plugin Name: Sustainable Catalyst Engagement Intake
 * Plugin URI:  https://sustainablecatalyst.com/
 * Description: Dual private intake experiences with a compact Consulting form, an advanced Contact Hub, conversion routing, Microsoft Teams scheduling readiness, audit history, privacy tools, and administrative workflow.
 * Version:     0.2.2
 * Author:      Content Catalyst LLC
 * Author URI:  https://sustainablecatalyst.com/
 * Text Domain: sustainable-catalyst-engagement-intake
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SC_EI_VERSION', '0.2.2' );
define( 'SC_EI_DB_VERSION', '0.2.2' );
define( 'SC_EI_FILE', __FILE__ );
define( 'SC_EI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SC_EI_URL', plugin_dir_url( __FILE__ ) );
define( 'SC_EI_BASENAME', plugin_basename( __FILE__ ) );

require_once SC_EI_DIR . 'includes/class-sc-ei-database.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-statuses.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-capabilities.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-teams.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-conversion.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-audit-log.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-inquiry-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-attachment-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-form-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-form-handler.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-public.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-privacy.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-diagnostics.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-rest.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-admin-list-table.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-activator.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-plugin.php';

register_activation_hook( __FILE__, array( 'SC_EI_Activator', 'activate' ) );

function sc_ei_bootstrap(): void {
	SC_EI_Plugin::instance()->boot();
}
add_action( 'plugins_loaded', 'sc_ei_bootstrap' );
