<?php
/**
 * Roles and capabilities.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Capabilities {

	public const ALL = array(
		'sc_intake_view',
		'sc_intake_review',
		'sc_intake_download_files',
		'sc_intake_release_files',
		'sc_intake_manage_file_retention',
		'sc_intake_add_notes',
		'sc_intake_change_status',
		'sc_intake_communicate',
		'sc_intake_export',
		'sc_intake_delete',
		'sc_intake_manage_settings',
	);

	public static function install(): void {
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( self::ALL as $cap ) {
				$administrator->add_cap( $cap );
			}
		}

		add_role(
			'sc_engagement_reviewer',
			__( 'Engagement Reviewer', 'sustainable-catalyst-engagement-intake' ),
			array(
				'read'                    => true,
				'sc_intake_view'          => true,
				'sc_intake_review'        => true,
				'sc_intake_add_notes'     => true,
				'sc_intake_change_status' => true,
			)
		);

		add_role(
			'sc_engagement_manager',
			__( 'Engagement Manager', 'sustainable-catalyst-engagement-intake' ),
			array(
				'read'                     => true,
				'sc_intake_view'           => true,
				'sc_intake_review'         => true,
				'sc_intake_download_files'       => true,
				'sc_intake_release_files'        => true,
				'sc_intake_manage_file_retention'=> true,
				'sc_intake_add_notes'            => true,
				'sc_intake_change_status'  => true,
				'sc_intake_communicate'    => true,
				'sc_intake_export'         => true,
			)
		);

		$reviewer = get_role( 'sc_engagement_reviewer' );
		if ( $reviewer ) {
			foreach ( array( 'read', 'sc_intake_view', 'sc_intake_review', 'sc_intake_add_notes', 'sc_intake_change_status' ) as $capability ) {
				$reviewer->add_cap( $capability );
			}
		}

		$manager = get_role( 'sc_engagement_manager' );
		if ( $manager ) {
			foreach (
				array(
					'read',
					'sc_intake_view',
					'sc_intake_review',
					'sc_intake_download_files',
					'sc_intake_release_files',
					'sc_intake_manage_file_retention',
					'sc_intake_add_notes',
					'sc_intake_change_status',
					'sc_intake_communicate',
					'sc_intake_export',
				) as $capability
			) {
				$manager->add_cap( $capability );
			}
		}
	}

	public static function uninstall(): void {
		foreach ( array( 'administrator', 'sc_engagement_reviewer', 'sc_engagement_manager' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( self::ALL as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}

		remove_role( 'sc_engagement_reviewer' );
		remove_role( 'sc_engagement_manager' );
	}
}
