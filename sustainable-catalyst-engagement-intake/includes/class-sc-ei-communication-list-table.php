<?php
/**
 * Cross-inquiry communication history table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class SC_EI_Communication_List_Table extends WP_List_Table {

	private string $view = 'history';

	public function __construct( string $view = 'history' ) {
		$this->view = $view;
		parent::__construct(
			array(
				'singular' => 'sc_ei_communication',
				'plural'   => 'sc_ei_communications',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'reference'          => __( 'Inquiry', 'sustainable-catalyst-engagement-intake' ),
			'direction_channel'  => __( 'Direction / Channel', 'sustainable-catalyst-engagement-intake' ),
			'type_status'        => __( 'Type / State', 'sustainable-catalyst-engagement-intake' ),
			'subject'            => __( 'Subject / Summary', 'sustainable-catalyst-engagement-intake' ),
			'parties'            => __( 'Parties', 'sustainable-catalyst-engagement-intake' ),
			'assignment'         => __( 'Owner / Follow-up', 'sustainable-catalyst-engagement-intake' ),
			'attempts'           => __( 'Transport', 'sustainable-catalyst-engagement-intake' ),
			'created_at'         => __( 'Occurred / Created', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'reference'         => array( 'reference', false ),
			'direction_channel' => array( 'direction', false ),
			'type_status'       => array( 'status', false ),
			'assignment'        => array( 'next_follow_up_at', false ),
			'created_at'        => array( 'created_at', true ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No communications match the current filters.', 'sustainable-catalyst-engagement-intake' );
	}

	public function column_reference( $item ): string {
		$url = SC_EI_Communication_Admin::thread_url( absint( $item['inquiry_id'] ) );
		$actions = array(
			'thread' => sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Open thread', 'sustainable-catalyst-engagement-intake' ) ),
			'inquiry' => sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'    => 'sc-engagement-intake',
							'action'  => 'view',
							'inquiry' => absint( $item['inquiry_id'] ),
						),
						admin_url( 'admin.php' )
					)
				),
				esc_html__( 'Inquiry record', 'sustainable-catalyst-engagement-intake' )
			),
		);
		if ( in_array( $item['status'], array( 'draft', 'approved', 'failed' ), true ) ) {
			$actions['edit'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( SC_EI_Communication_Admin::thread_url( absint( $item['inquiry_id'] ), array( 'draft' => absint( $item['id'] ) ) ) ),
				esc_html__( 'Open draft', 'sustainable-catalyst-engagement-intake' )
			);
		}

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong><br><span class="description">%3$s%4$s</span>%5$s',
			esc_url( $url ),
			esc_html( $item['reference'] ),
			esc_html( $item['contact_name'] ?: $item['contact_email'] ),
			$item['organization'] ? ' · ' . esc_html( $item['organization'] ) : '',
			$this->row_actions( $actions )
		);
	}

	public function column_direction_channel( $item ): string {
		return sprintf(
			'<span class="sc-ei-comm-direction sc-ei-comm-direction--%1$s">%2$s</span><br><span class="description">%3$s</span>',
			esc_attr( $item['direction'] ),
			esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::directions(), $item['direction'] ) ),
			esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::channels(), $item['channel'] ) )
		);
	}

	public function column_type_status( $item ): string {
		return sprintf(
			'<strong>%1$s</strong><br><span class="sc-ei-comm-status sc-ei-comm-status--%2$s">%3$s</span>%4$s',
			esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::types(), $item['communication_type'] ) ),
			esc_attr( $item['status'] ),
			esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::statuses(), $item['status'] ) ),
			! empty( $item['is_automated'] ) ? '<br><span class="description">' . esc_html__( 'Automated policy event', 'sustainable-catalyst-engagement-intake' ) . '</span>' : ''
		);
	}

	public function column_subject( $item ): string {
		$summary = wp_trim_words( wp_strip_all_tags( (string) $item['body_text'] ), 20, '…' );
		return sprintf(
			'<strong>%1$s</strong><br><span class="description">%2$s</span>',
			esc_html( $item['subject'] ?: __( 'No subject', 'sustainable-catalyst-engagement-intake' ) ),
			esc_html( $summary )
		);
	}

	public function column_parties( $item ): string {
		return sprintf(
			'<strong>%1$s</strong><br><span class="description">%2$s</span><br><span class="description">%3$s: %4$s</span>',
			esc_html( $item['recipient_name'] ?: $item['recipient_email'] ),
			esc_html( $item['recipient_email'] ),
			esc_html__( 'Sender', 'sustainable-catalyst-engagement-intake' ),
			esc_html( $item['sender_name'] ?: $item['sender_email'] ?: __( 'System', 'sustainable-catalyst-engagement-intake' ) )
		);
	}

	public function column_assignment( $item ): string {
		$follow_up = $item['next_follow_up_at']
			? get_date_from_gmt( $item['next_follow_up_at'], 'M j, Y g:i a' )
			: __( 'No follow-up', 'sustainable-catalyst-engagement-intake' );
		$due = $item['next_follow_up_at'] && strtotime( $item['next_follow_up_at'] . ' UTC' ) <= time();

		return sprintf(
			'<strong>%1$s</strong><br><span class="sc-ei-comm-followup %2$s">%3$s</span><br><span class="description">%4$s</span>',
			esc_html( $item['assigned_name'] ?: __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ),
			$due ? 'sc-ei-inline-warning' : '',
			esc_html( $follow_up ),
			esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::communication_states(), $item['communication_status'] ) )
		);
	}

	public function column_attempts( $item ): string {
		if ( ! in_array( $item['channel'], array( 'email' ), true ) ) {
			return '<span class="description">' . esc_html__( 'Manual record', 'sustainable-catalyst-engagement-intake' ) . '</span>';
		}

		$output = sprintf(
			esc_html( _n( '%d attempt', '%d attempts', absint( $item['attempt_count'] ), 'sustainable-catalyst-engagement-intake' ) ),
			absint( $item['attempt_count'] )
		);
		if ( 'accepted' === $item['status'] ) {
			$output .= '<br><span class="description">' . esc_html__( 'Accepted—not delivery-confirmed', 'sustainable-catalyst-engagement-intake' ) . '</span>';
		} elseif ( 'failed' === $item['status'] && $item['error_code'] ) {
			$output .= '<br><span class="sc-ei-inline-warning">' . esc_html( $item['error_code'] ) . '</span>';
		}
		return $output;
	}

	public function column_created_at( $item ): string {
		$effective = $item['occurred_at'] ?: $item['accepted_at'] ?: $item['created_at'];
		return sprintf(
			'%1$s<br><span class="description">%2$s · v%3$d</span>',
			esc_html( get_date_from_gmt( $effective, 'M j, Y g:i a' ) ),
			esc_html( $item['provider'] ?: __( 'No transport', 'sustainable-catalyst-engagement-intake' ) ),
			absint( $item['row_version'] )
		);
	}

	public function column_default( $item, $column_name ) {
		return '';
	}

	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'sc_ei_communications_per_page', 25 );
		$result = SC_EI_Communication_Repository::query(
			array(
				'view'               => $this->view,
				'status'             => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
				'direction'          => isset( $_GET['direction'] ) ? sanitize_key( wp_unslash( $_GET['direction'] ) ) : '',
				'channel'            => isset( $_GET['channel'] ) ? sanitize_key( wp_unslash( $_GET['channel'] ) ) : '',
				'communication_type' => isset( $_GET['communication_type'] ) ? sanitize_key( wp_unslash( $_GET['communication_type'] ) ) : '',
				'assignee'           => isset( $_GET['assignee'] ) ? sanitize_text_field( wp_unslash( $_GET['assignee'] ) ) : '',
				'search'             => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
				'page'               => $this->get_pagenum(),
				'per_page'           => $per_page,
				'orderby'            => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at',
				'order'              => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC',
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
}
