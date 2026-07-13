<?php
/**
 * Private document access and operations audit table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class SC_EI_File_Access_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'sc_ei_file_event',
				'plural'   => 'sc_ei_file_events',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'created_at' => __( 'Date', 'sustainable-catalyst-engagement-intake' ),
			'event_type' => __( 'Event', 'sustainable-catalyst-engagement-intake' ),
			'file'       => __( 'Private Document', 'sustainable-catalyst-engagement-intake' ),
			'inquiry'    => __( 'Inquiry', 'sustainable-catalyst-engagement-intake' ),
			'actor'      => __( 'Actor', 'sustainable-catalyst-engagement-intake' ),
			'message'    => __( 'Details', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'created_at' => array( 'created_at', true ),
			'event_type' => array( 'event_type', false ),
			'inquiry'    => array( 'reference', false ),
			'actor'      => array( 'actor', false ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No private document access or operations events match the current filters.', 'sustainable-catalyst-engagement-intake' );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
				return esc_html( get_date_from_gmt( $item['created_at'], 'M j, Y g:i a' ) );

			case 'event_type':
				$types = SC_EI_Audit_Log::file_event_types();
				return sprintf(
					'<span class="sc-ei-audit-event sc-ei-audit-event--%1$s">%2$s</span>',
					esc_attr( $item['event_type'] ),
					esc_html( $types[ $item['event_type'] ] ?? ucwords( str_replace( '_', ' ', $item['event_type'] ) ) )
				);

			case 'file':
				return $item['original_name']
					? sprintf( '<strong>%s</strong>', esc_html( $item['original_name'] ) )
					: '<span class="description">' . esc_html__( 'System-level event', 'sustainable-catalyst-engagement-intake' ) . '</span>';

			case 'inquiry':
				if ( empty( $item['inquiry_id'] ) || empty( $item['reference'] ) ) {
					return '—';
				}
				$url = add_query_arg(
					array(
						'page'    => 'sc-engagement-intake',
						'action'  => 'view',
						'inquiry' => absint( $item['inquiry_id'] ),
					),
					admin_url( 'admin.php' )
				);
				return sprintf(
					'<strong><a href="%1$s">%2$s</a></strong><br><span class="description">%3$s</span>',
					esc_url( $url ),
					esc_html( $item['reference'] ),
					esc_html( $item['contact_name'] ?: '' )
				);

			case 'actor':
				if ( empty( $item['actor_user_id'] ) ) {
					return '<span class="description">' . esc_html__( 'System', 'sustainable-catalyst-engagement-intake' ) . '</span>';
				}
				return sprintf(
					'<strong>%1$s</strong><br><span class="description">%2$s</span>',
					esc_html( $item['actor_name'] ?: 'User #' . absint( $item['actor_user_id'] ) ),
					esc_html( $item['actor_email'] ?: '' )
				);

			case 'message':
				$context = json_decode( (string) $item['context_json'], true );
				$context = is_array( $context ) ? $context : array();
				$summary = array();
				foreach ( array( 'new_status', 'storage_status', 'integrity_status', 'provider', 'source', 'deleted_count' ) as $key ) {
					if ( isset( $context[ $key ] ) && '' !== (string) $context[ $key ] ) {
						$summary[] = ucwords( str_replace( '_', ' ', $key ) ) . ': ' . sanitize_text_field( (string) $context[ $key ] );
					}
				}

				return esc_html( $item['event_message'] )
					. ( $summary ? '<br><span class="description">' . esc_html( implode( ' · ', $summary ) ) . '</span>' : '' );

			default:
				return '';
		}
	}

	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'sc_ei_file_audit_per_page', 25 );
		$page     = $this->get_pagenum();

		$result = SC_EI_Audit_Log::query_file_events(
			array(
				'event_type' => isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( $_GET['event_type'] ) ) : '',
				'actor'      => isset( $_GET['actor'] ) ? absint( $_GET['actor'] ) : 0,
				'date_from'  => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
				'date_to'    => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
				'search'     => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
				'page'       => $page,
				'per_page'   => $per_page,
				'orderby'    => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at',
				'order'      => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC',
			)
		);

		$this->items = $result['items'];
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => $result['total_pages'],
			)
		);
	}

	protected function extra_tablenav( $which ): void {
		// Filters are rendered in the dedicated GET form above the table.
	}

}
