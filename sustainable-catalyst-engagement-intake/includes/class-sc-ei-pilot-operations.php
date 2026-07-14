<?php
/**
 * Pilot evidence, routed public entry points, and public-launch operational evidence.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Pilot_Operations {

	private const PILOT_OPTION = 'sc_ei_platform_pilot_evidence';
	private const MAIL_OPTION  = 'sc_ei_platform_external_mail_evidence';
	private const MAX_AGE_DAYS = 14;

	public static function route_map(): array {
		$defaults = array(
			'general'          => array( 'type' => 'general', 'service' => '', 'label' => __( 'General inquiry', 'sustainable-catalyst-engagement-intake' ) ),
			'advisory'         => array( 'type' => 'consulting', 'service' => 'strategic_consultation', 'label' => __( 'Advisory engagement', 'sustainable-catalyst-engagement-intake' ) ),
			'ai-assurance'     => array( 'type' => 'consulting', 'service' => 'evidence_systems_diagnostic', 'label' => __( 'Sustainable AI Assurance', 'sustainable-catalyst-engagement-intake' ) ),
			'collaboration'    => array( 'type' => 'research_collaboration', 'service' => 'research_collaboration', 'label' => __( 'Research collaboration', 'sustainable-catalyst-engagement-intake' ) ),
			'media'            => array( 'type' => 'speaking_media', 'service' => 'speaking_media', 'label' => __( 'Media or speaking request', 'sustainable-catalyst-engagement-intake' ) ),
			'technical'        => array( 'type' => 'platform_technical', 'service' => 'open_source_technical', 'label' => __( 'Technical platform inquiry', 'sustainable-catalyst-engagement-intake' ) ),
			'partnership'      => array( 'type' => 'institutional_partnership', 'service' => 'institutional_partnership', 'label' => __( 'Institutional partnership', 'sustainable-catalyst-engagement-intake' ) ),
			'workshop'         => array( 'type' => 'workshop_training', 'service' => 'training_workshop', 'label' => __( 'Workshop or training', 'sustainable-catalyst-engagement-intake' ) ),
			'monthly-advisory' => array( 'type' => 'monthly_advisory', 'service' => 'monthly_advisory', 'label' => __( 'Monthly advisory', 'sustainable-catalyst-engagement-intake' ) ),
		);
		$filtered = apply_filters( 'sc_ei_public_engagement_routes', $defaults );
		if ( ! is_array( $filtered ) ) {
			return $defaults;
		}
		$routes = array_merge( $defaults, $filtered );
		foreach ( $routes as $key => $definition ) {
			if ( ! is_array( $definition ) ) {
				$routes[ $key ] = $defaults[ $key ] ?? $defaults['general'];
			}
		}
		return $routes;
	}

	public static function resolve_route( string $requested ): array {
		$key = sanitize_title( $requested );
		$aliases = array(
			'ai_assurance' => 'ai-assurance',
			'aiassurance' => 'ai-assurance',
			'consulting' => 'advisory',
			'research' => 'collaboration',
			'speaking' => 'media',
			'press' => 'media',
			'institutional' => 'partnership',
			'monthly_advisory' => 'monthly-advisory',
		);
		$key = $aliases[ $key ] ?? $key;
		$routes = self::route_map();
		$route = $routes[ $key ] ?? $routes['general'];
		$type = sanitize_key( (string) ( $route['type'] ?? 'general' ) );
		$service = sanitize_key( (string) ( $route['service'] ?? '' ) );
		if ( ! isset( SC_EI_Form_Schema::all_public_types()[ $type ] ) ) {
			$type = 'general';
			$service = '';
		}
		if ( $service && ! isset( SC_EI_Form_Schema::service_interests()[ $service ] ) ) {
			$service = '';
		}
		return array(
			'key' => isset( $routes[ $key ] ) ? $key : 'general',
			'type' => $type,
			'service' => $service,
			'label' => sanitize_text_field( (string) ( $route['label'] ?? '' ) ),
			'applied' => isset( $routes[ $key ] ) && 'general' !== $key,
		);
	}

	public static function current_route(): array {
		$requested = '';
		if ( isset( $_GET['engagement'] ) ) {
			$requested = wp_unslash( $_GET['engagement'] );
		} elseif ( isset( $_GET['sc_engagement'] ) ) {
			$requested = wp_unslash( $_GET['sc_engagement'] );
		}
		return self::resolve_route( (string) $requested );
	}

	public static function route_contract_evidence(): array {
		$checks = array();
		foreach ( self::route_map() as $key => $definition ) {
			$route = self::resolve_route( $key );
			$checks[ $key ] = isset( SC_EI_Form_Schema::all_public_types()[ $route['type'] ] )
				&& ( '' === $route['service'] || isset( SC_EI_Form_Schema::service_interests()[ $route['service'] ] ) );
		}
		return array(
			'passed' => ! in_array( false, $checks, true ),
			'checks' => $checks,
			'detail' => sprintf( '%d/%d routed entry contracts valid', count( array_filter( $checks ) ), count( $checks ) ),
		);
	}

	public static function pilot_evidence(): array {
		$value = get_option( self::PILOT_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function external_mail_evidence(): array {
		$value = get_option( self::MAIL_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function pilot_complete_and_fresh(): bool {
		$value = self::pilot_evidence();
		$required = array( 'general_inquiry', 'advisory_inquiry', 'ai_assurance_inquiry', 'private_upload', 'admin_notification', 'sender_acknowledgment', 'portal_isolation', 'mobile_browser', 'rollback_verified' );
		foreach ( $required as $key ) {
			if ( empty( $value['checks'][ $key ] ) ) {
				return false;
			}
		}
		return SC_EI_VERSION === (string) ( $value['plugin_version'] ?? '' )
			&& absint( $value['controlled_inquiry_count'] ?? 0 ) >= 5
			&& self::fresh_timestamp( (string) ( $value['recorded_at'] ?? '' ) );
	}

	public static function external_mail_confirmed_and_fresh(): bool {
		$value = self::external_mail_evidence();
		return ! empty( $value['confirmed'] )
			&& SC_EI_VERSION === (string) ( $value['plugin_version'] ?? '' )
			&& is_email( (string) ( $value['recipient'] ?? '' ) )
			&& strlen( trim( (string) ( $value['reference'] ?? '' ) ) ) >= 3
			&& self::fresh_timestamp( (string) ( $value['confirmed_at'] ?? '' ) );
	}

	public static function record_pilot_evidence( array $input, int $actor_user_id ) {
		$count = max( 0, min( 1000, absint( $input['controlled_inquiry_count'] ?? 0 ) ) );
		$checks = array();
		foreach ( array( 'general_inquiry', 'advisory_inquiry', 'ai_assurance_inquiry', 'private_upload', 'admin_notification', 'sender_acknowledgment', 'portal_isolation', 'mobile_browser', 'rollback_verified' ) as $key ) {
			$checks[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}
		$reference = sanitize_textarea_field( (string) ( $input['reference'] ?? '' ) );
		if ( $count < 5 || in_array( 0, $checks, true ) || strlen( trim( $reference ) ) < 3 ) {
			return new WP_Error( 'pilot_evidence_incomplete', __( 'Confirm every pilot test, record at least five controlled inquiries, and add a short evidence reference.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$value = array(
			'schema' => 'sc-platform-pilot-evidence/1.0',
			'plugin_version' => SC_EI_VERSION,
			'controlled_inquiry_count' => $count,
			'checks' => $checks,
			'reference' => $reference,
			'recorded_by' => $actor_user_id,
			'recorded_at' => current_time( 'mysql', true ),
		);
		update_option( self::PILOT_OPTION, $value, false );
		SC_EI_Audit_Log::record( 'platform_pilot_evidence_recorded', 'Controlled pilot and public-launch evidence was recorded by authorized staff.', array( 'controlled_inquiry_count' => $count, 'checks' => $checks ), null, null, $actor_user_id );
		return $value;
	}

	public static function confirm_external_mail( string $recipient, string $reference, int $actor_user_id ) {
		$recipient = sanitize_email( $recipient );
		$reference = sanitize_text_field( $reference );
		if ( ! is_email( $recipient ) || strlen( trim( $reference ) ) < 3 ) {
			return new WP_Error( 'external_mail_evidence_invalid', __( 'Enter the monitored recipient and a message, provider, or inbox reference.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$value = array(
			'schema' => 'sc-platform-external-mail-evidence/1.0',
			'plugin_version' => SC_EI_VERSION,
			'confirmed' => true,
			'recipient' => $recipient,
			'reference' => $reference,
			'confirmed_by' => $actor_user_id,
			'confirmed_at' => current_time( 'mysql', true ),
		);
		update_option( self::MAIL_OPTION, $value, false );
		SC_EI_Audit_Log::record( 'platform_external_mail_confirmed', 'Authorized staff confirmed external inbox receipt of a platform email.', array( 'recipient_domain' => self::email_domain( $recipient ), 'reference' => $reference ), null, null, $actor_user_id );
		return $value;
	}

	public static function operational_summary(): array {
		global $wpdb;
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$today = gmdate( 'Y-m-d 00:00:00' );
		$week = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
		$inquiry = (array) $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
					SUM(CASE WHEN status IN ('new','under_review') THEN 1 ELSE 0 END) AS review_queue,
					SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS created_today,
					SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS created_week
				FROM {$inquiries}",
				$today,
				$week
			),
			ARRAY_A
		);
		foreach ( array( 'new_count', 'review_queue', 'created_today', 'created_week' ) as $key ) {
			$inquiry[ $key ] = absint( $inquiry[ $key ] ?? 0 );
		}
		$communications = SC_EI_Communication_Repository::metrics();
		$attachments = SC_EI_Attachment_Repository::operational_summary();
		$portal = SC_EI_Portal_Repository::metrics();
		$hardening = SC_EI_Hardening_Repository::metrics();
		$blockers = array();
		if ( absint( $communications['failed'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d failed communication(s)', absint( $communications['failed'] ) );
		if ( absint( $communications['follow_up_due'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d overdue follow-up(s)', absint( $communications['follow_up_due'] ) );
		if ( absint( $attachments['quarantined_count'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d quarantined attachment(s)', absint( $attachments['quarantined_count'] ) );
		if ( absint( $attachments['infected_count'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d infected attachment(s)', absint( $attachments['infected_count'] ) );
		if ( absint( $attachments['scan_attention_count'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d attachment scan issue(s)', absint( $attachments['scan_attention_count'] ) );
		if ( absint( $attachments['storage_attention_count'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d attachment storage issue(s)', absint( $attachments['storage_attention_count'] ) );
		if ( absint( $attachments['expired_count'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d attachment retention action(s) due', absint( $attachments['expired_count'] ) );
		if ( absint( $portal['locked'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d active portal lockout(s)', absint( $portal['locked'] ) );
		if ( absint( $portal['failed_today'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d portal failure event(s) today', absint( $portal['failed_today'] ) );
		if ( absint( $portal['activation_rollbacks_today'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d portal activation rollback(s) today', absint( $portal['activation_rollbacks_today'] ) );
		if ( absint( $hardening['open_critical'] ?? 0 ) > 0 ) $blockers[] = sprintf( '%d open critical reliability event(s)', absint( $hardening['open_critical'] ) );
		return array(
			'inquiries' => $inquiry,
			'communications' => $communications,
			'attachments' => $attachments,
			'portal' => $portal,
			'hardening' => $hardening,
			'blockers' => $blockers,
			'clear' => empty( $blockers ),
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	private static function fresh_timestamp( string $timestamp ): bool {
		$time = $timestamp ? strtotime( $timestamp . ' UTC' ) : false;
		return false !== $time && $time >= time() - self::MAX_AGE_DAYS * DAY_IN_SECONDS;
	}

	private static function email_domain( string $email ): string {
		$parts = explode( '@', strtolower( $email ) );
		return 2 === count( $parts ) ? sanitize_text_field( $parts[1] ) : '';
	}
}
