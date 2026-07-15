<?php
/** Billing and payment-handoff administration. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class SC_EI_Billing_Admin {
	public static function register(): void {
		add_action( 'admin_post_sc_ei_billing_create_profile', array( __CLASS__, 'create_profile' ) );
		add_action( 'admin_post_sc_ei_billing_create_invoice', array( __CLASS__, 'create_invoice' ) );
		add_action( 'admin_post_sc_ei_billing_add_item', array( __CLASS__, 'add_item' ) );
		add_action( 'admin_post_sc_ei_billing_transition', array( __CLASS__, 'transition' ) );
		add_action( 'admin_post_sc_ei_billing_create_handoff', array( __CLASS__, 'create_handoff' ) );
		add_action( 'admin_post_sc_ei_billing_record_status', array( __CLASS__, 'record_status' ) );
	}
	public static function submenu(): void { add_submenu_page( 'sc-engagement-intake', __( 'Billing, Invoicing, and Payment Handoffs', 'sustainable-catalyst-engagement-intake' ), __( 'Billing & Payments', 'sustainable-catalyst-engagement-intake' ), 'sc_intake_view_billing', 'sc-engagement-intake-billing', array( __CLASS__, 'page' ) ); }
	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_billing' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		$invoice_id = absint( $_GET['invoice_id'] ?? 0 );
		$invoices = SC_EI_Billing_Repository::invoices( 250 );
		$invoice = $invoice_id ? SC_EI_Billing_Repository::find_invoice( $invoice_id ) : null;
		$items = $invoice ? SC_EI_Billing_Repository::items( $invoice_id ) : array();
		$engagements = SC_EI_Engagement_Repository::all( array( 'per_page' => 250 ) )['items'] ?? array();
		$metrics = SC_EI_Billing_Repository::metrics();
		$blockers = SC_EI_Billing_Repository::operational_blockers();
		include SC_EI_DIR . 'admin/views/billing.php';
	}
	private static function guard( string $action ): void { if ( ! current_user_can( 'sc_intake_manage_billing' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) ); check_admin_referer( $action ); }
	private static function back( int $invoice_id = 0, string $message = '' ): void { $args = array( 'page' => 'sc-engagement-intake-billing' ); if ( $invoice_id ) $args['invoice_id'] = $invoice_id; if ( $message ) $args['sc_ei_msg'] = sanitize_key( $message ); wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ), 303 ); exit; }
	public static function create_profile(): void { self::guard( 'sc_ei_billing_create_profile' ); $r=SC_EI_Billing_Repository::create_profile( absint($_POST['engagement_id']??0), array( 'organization_name'=>wp_unslash($_POST['organization_name']??''),'billing_contact_name'=>wp_unslash($_POST['billing_contact_name']??''),'billing_contact_email'=>wp_unslash($_POST['billing_contact_email']??''),'currency'=>wp_unslash($_POST['currency']??'USD'),'payment_terms_days'=>absint($_POST['payment_terms_days']??30),'sender_visible'=>!empty($_POST['sender_visible']) ), get_current_user_id() ); self::back(0,is_wp_error($r)?$r->get_error_code():'billing_profile_created'); }
	public static function create_invoice(): void { self::guard( 'sc_ei_billing_create_invoice' ); $r=SC_EI_Billing_Repository::create_invoice(absint($_POST['engagement_id']??0),array('billing_profile_id'=>absint($_POST['billing_profile_id']??0),'proposal_id'=>absint($_POST['proposal_id']??0),'sow_id'=>absint($_POST['sow_id']??0),'currency'=>wp_unslash($_POST['currency']??'USD'),'due_at'=>wp_unslash($_POST['due_at']??''),'memo'=>wp_unslash($_POST['memo']??''),'internal_note'=>wp_unslash($_POST['internal_note']??'')),get_current_user_id()); self::back(is_wp_error($r)?0:absint($r['id']??0),is_wp_error($r)?$r->get_error_code():'invoice_created'); }
	public static function add_item(): void { $id=absint($_POST['invoice_id']??0); self::guard('sc_ei_billing_add_item_'.$id); $r=SC_EI_Billing_Repository::add_item($id,array('item_type'=>wp_unslash($_POST['item_type']??'service'),'description'=>wp_unslash($_POST['description']??''),'quantity'=>wp_unslash($_POST['quantity']??1),'unit_amount_minor'=>absint($_POST['unit_amount_minor']??0),'tax_code'=>wp_unslash($_POST['tax_code']??'')),get_current_user_id()); self::back($id,is_wp_error($r)?$r->get_error_code():'invoice_item_added'); }
	public static function transition(): void { $id=absint($_POST['invoice_id']??0); self::guard('sc_ei_billing_transition_'.$id); $r=SC_EI_Billing_Repository::transition($id,wp_unslash($_POST['status']??''),wp_unslash($_POST['confirmation']??''),wp_unslash($_POST['note']??''),get_current_user_id()); self::back($id,is_wp_error($r)?$r->get_error_code():'invoice_transitioned'); }
	public static function create_handoff(): void { $id=absint($_POST['invoice_id']??0); self::guard('sc_ei_billing_create_handoff_'.$id); $r=SC_EI_Billing_Repository::create_payment_handoff($id,array('provider'=>wp_unslash($_POST['provider']??'manual'),'provider_reference'=>wp_unslash($_POST['provider_reference']??''),'checkout_url'=>wp_unslash($_POST['checkout_url']??''),'amount_minor'=>absint($_POST['amount_minor']??0),'currency'=>wp_unslash($_POST['currency']??'USD'),'expires_at'=>wp_unslash($_POST['expires_at']??''),'sender_visible'=>!empty($_POST['sender_visible']),'metadata'=>array('source'=>'admin')),get_current_user_id()); self::back($id,is_wp_error($r)?$r->get_error_code():'payment_handoff_created'); }
	public static function record_status(): void { $id=absint($_POST['handoff_id']??0); self::guard('sc_ei_billing_record_status_'.$id); $r=SC_EI_Billing_Repository::record_payment_status($id,wp_unslash($_POST['status']??''),wp_unslash($_POST['provider_event_key']??''),array('source'=>'admin_review'),get_current_user_id()); self::back(absint($_POST['invoice_id']??0),is_wp_error($r)?$r->get_error_code():'payment_status_recorded'); }
}
