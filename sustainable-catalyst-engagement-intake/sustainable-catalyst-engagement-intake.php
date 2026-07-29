<?php
/**
 * Plugin Name: Sustainable Catalyst Contact and Engagement Platform
 * Plugin URI:  https://sustainablecatalyst.com/
 * Description: Integrated advisory, support, and institutional engagement platform with canonical engagement dossiers, unified activity timelines, typed cross-product handoffs, governed intake, Teams coordination, proposals, secure client collaboration, billing, analytics, privacy, and production controls.
 * Version:     2.0.2
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

define( 'SC_EI_VERSION', '2.0.2' );
define( 'SC_EI_DB_VERSION', '2.0.0' );
define( 'SC_EI_VALIDATOR_VERSION', '1.3.0' );
define( 'SC_EI_REVIEW_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_COMMUNICATION_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_PRIVACY_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_FIT_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_PORTAL_SCHEMA_VERSION', '1.8.0' );
define( 'SC_EI_WORKFLOW_SCHEMA_VERSION', '1.3.0' );
define( 'SC_EI_GRAPH_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_ENGAGEMENT_SCHEMA_VERSION', '1.2.0' );
define( 'SC_EI_ANALYTICS_SCHEMA_VERSION', '1.1.0' );
define( 'SC_EI_HARDENING_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_WORKFLOW_CORE_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_PLATFORM_SCHEMA_VERSION', '2.0.0' );
define( 'SC_EI_LIFECYCLE_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_SUPPORT_SCHEMA_VERSION', '1.0.1' );
define( 'SC_EI_CALENDAR_SCHEMA_VERSION', '1.0.1' );
define( 'SC_EI_PROPOSAL_SCHEMA_VERSION', '1.0.1' );
define( 'SC_EI_WORKSPACE_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_BILLING_SCHEMA_VERSION', '1.0.0' );
define( 'SC_EI_UNIFIED_PLATFORM_SCHEMA_VERSION', '2.0.0' );
define( 'SC_EI_FILE', __FILE__ );
define( 'SC_EI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SC_EI_URL', plugin_dir_url( __FILE__ ) );
define( 'SC_EI_BASENAME', plugin_basename( __FILE__ ) );

require_once SC_EI_DIR . 'includes/class-sc-ei-database.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-statuses.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-capabilities.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-teams.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-conversion.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-review-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-fit-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-portal-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-proposal-governance-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workspace-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-calendar-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-communication-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-privacy-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-upload-environment.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-storage.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-storage-reconciler.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-file-scanner.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-scanner-operations.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-upload-validator.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-audit-log.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-inquiry-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-review-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-fit-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-portal-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-portal-session.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-graph-crypto.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-graph-credentials.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-graph-client.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-graph-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-proposal-governance-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workspace-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-calendar-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-engagement-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-lifecycle-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-support-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-engagement-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-lifecycle-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-support-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-template-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-communication-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-privacy-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-retention-policy-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-retention-engine.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-mailer.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-notification-service.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-attachment-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-upload-manager.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-retention.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-form-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-form-handler.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-public.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-portal-public.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-privacy.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-analytics-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-service-intelligence-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-billing-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-unified-platform-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-analytics-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-service-intelligence-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-billing-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-unified-platform-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-hardening-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-hardening-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-core-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-core-contract.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-core-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-core-service.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-platform-schema.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-platform-validation.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-pilot-operations.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-platform-repository.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-platform-public.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-diagnostics.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-rest.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-admin-list-table.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-review-list-table.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-communication-list-table.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-quarantine-list-table.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-file-access-list-table.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-review-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-fit-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-portal-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-proposal-governance-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workspace-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-calendar-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-graph-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-engagement-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-lifecycle-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-support-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-analytics-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-service-intelligence-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-billing-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-command-center-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-hardening-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-workflow-core-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-platform-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-communication-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-privacy-admin.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-activator.php';
require_once SC_EI_DIR . 'includes/class-sc-ei-plugin.php';

register_activation_hook( __FILE__, array( 'SC_EI_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SC_EI_Activator', 'deactivate' ) );

function sc_ei_bootstrap(): void {
	SC_EI_Plugin::instance()->boot();
}
add_action( 'plugins_loaded', 'sc_ei_bootstrap' );
