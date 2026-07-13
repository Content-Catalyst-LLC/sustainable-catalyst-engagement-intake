<?php
/**
 * Inquiry list table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class SC_EI_Admin_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'sc_ei_inquiry',
				'plural'   => 'sc_ei_inquiries',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'reference'         => __( 'Reference', 'sustainable-catalyst-engagement-intake' ),
			'contact'           => __( 'Contact', 'sustainable-catalyst-engagement-intake' ),
			'organization'      => __( 'Organization', 'sustainable-catalyst-engagement-intake' ),
			'inquiry_type'      => __( 'Type', 'sustainable-catalyst-engagement-intake' ),
			'origin'            => __( 'Experience / Source', 'sustainable-catalyst-engagement-intake' ),
			'conversion_route'  => __( 'Conversion Route', 'sustainable-catalyst-engagement-intake' ),
			'review'            => __( 'Administrative Review', 'sustainable-catalyst-engagement-intake' ),
			'review_due'        => __( 'Review Due', 'sustainable-catalyst-engagement-intake' ),
			'communication'     => __( 'Communication', 'sustainable-catalyst-engagement-intake' ),
			'documents'         => __( 'Documents', 'sustainable-catalyst-engagement-intake' ),
			'status'            => __( 'Inquiry Status', 'sustainable-catalyst-engagement-intake' ),
			'scheduling_status' => __( 'Teams Status', 'sustainable-catalyst-engagement-intake' ),
			'created_at'        => __( 'Received', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'reference'         => array( 'reference', false ),
			'contact'           => array( 'contact_name', false ),
			'organization'      => array( 'organization', false ),
			'conversion_route'  => array( 'conversion_route', false ),
			'review'            => array( 'review_stage', false ),
			'review_due'        => array( 'review_due_at', false ),
			'communication'     => array( 'last_communication_at', false ),
			'status'            => array( 'status', false ),
			'scheduling_status' => array( 'scheduling_status', false ),
			'created_at'        => array( 'created_at', true ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No private inquiries have been recorded yet.', 'sustainable-catalyst-engagement-intake' );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'organization':
				return esc_html( $item['organization'] ?: '—' );

			case 'inquiry_type':
				$types = SC_EI_Statuses::inquiry_types();
				return esc_html( $types[ $item['inquiry_type'] ] ?? $item['inquiry_type'] );

			case 'origin':
				$variants = SC_EI_Conversion::variants();
				$sources  = SC_EI_Conversion::sources();
				$variant  = SC_EI_Conversion::label( $variants, $item['form_variant'] ?? 'advanced' );
				$source   = SC_EI_Conversion::label( $sources, $item['source_page'] ?? 'other' );
				return sprintf(
					'<strong>%1$s</strong><br><span class="description">%2$s</span>',
					esc_html( $variant ),
					esc_html( $source )
				);

			case 'conversion_route':
				return esc_html( $item['conversion_route'] ? ucwords( str_replace( '_', ' ', $item['conversion_route'] ) ) : '—' );

			case 'review':
				$assigned = ! empty( $item['assigned_user_id'] ) ? get_userdata( absint( $item['assigned_user_id'] ) ) : false;
				return sprintf(
					'<span class="sc-ei-review-stage sc-ei-review-stage--%1$s">%2$s</span><br><span class="description">%3$s · %4$s</span>',
					esc_attr( $item['review_stage'] ?: 'intake' ),
					esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::stages(), $item['review_stage'] ?: 'intake' ) ),
					esc_html( $assigned ? $assigned->display_name : __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ),
					esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::fit_decisions(), $item['fit_decision'] ?: 'undecided' ) )
				);

			case 'review_due':
				$timing = SC_EI_Review_Schema::timing( $item );
				return sprintf(
					'<span class="sc-ei-review-priority sc-ei-review-priority--%1$s">%2$s</span><br><span class="sc-ei-review-due sc-ei-review-due--%3$s">%4$s</span>',
					esc_attr( $item['review_priority'] ?: 'normal' ),
					esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::priorities(), $item['review_priority'] ?: 'normal' ) ),
					esc_attr( $timing['due_state'] ),
					$item['review_due_at'] ? esc_html( get_date_from_gmt( $item['review_due_at'], 'M j, Y g:i a' ) ) : esc_html__( 'No due date', 'sustainable-catalyst-engagement-intake' )
				);

			case 'communication':
				$follow_up_due = ! empty( $item['next_follow_up_at'] ) && strtotime( $item['next_follow_up_at'] . ' UTC' ) <= time();
				return sprintf(
					'<span class="sc-ei-comm-status sc-ei-comm-status--%1$s">%2$s</span><br><span class="%3$s">%4$s</span><br><span class="description">%5$d records · %6$d unread</span>',
					esc_attr( $item['communication_status'] ?: 'open' ),
					esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::communication_states(), $item['communication_status'] ?: 'open' ) ),
					$follow_up_due ? 'sc-ei-inline-warning' : 'description',
					$item['next_follow_up_at'] ? esc_html( get_date_from_gmt( $item['next_follow_up_at'], 'M j, Y g:i a' ) ) : esc_html__( 'No follow-up', 'sustainable-catalyst-engagement-intake' ),
					absint( $item['communication_count'] ),
					absint( $item['unread_inbound_count'] )
				);

			case 'documents':
				$count = SC_EI_Attachment_Repository::count_for_inquiry( absint( $item['id'] ) );
				return $count
					? sprintf( '<strong>%1$d</strong><br><span class="description">%2$s</span>', $count, esc_html__( 'private', 'sustainable-catalyst-engagement-intake' ) )
					: '—';

			case 'status':
				return sprintf(
					'<span class="sc-ei-status sc-ei-status--%1$s">%2$s</span>',
					esc_attr( $item['status'] ),
					esc_html( SC_EI_Statuses::label( $item['status'] ) )
				);

			case 'scheduling_status':
				$value = $item['scheduling_status'] ?? 'not_requested';
				return sprintf(
					'<span class="sc-ei-status sc-ei-status--teams-%1$s">%2$s</span>',
					esc_attr( $value ),
					esc_html( SC_EI_Teams::label( SC_EI_Teams::scheduling_statuses(), $value ) )
				);

			case 'created_at':
				return esc_html( get_date_from_gmt( $item['created_at'], 'M j, Y g:i a' ) );

			default:
				return '';
		}
	}

	public function column_reference( $item ): string {
		$url = add_query_arg(
			array(
				'page'    => 'sc-engagement-intake',
				'action'  => 'view',
				'inquiry' => absint( $item['id'] ),
			),
			admin_url( 'admin.php' )
		);

		$actions = array(
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'View', 'sustainable-catalyst-engagement-intake' ) ),
			'review' => sprintf( '<a href="%s">%s</a>', esc_url( SC_EI_Review_Admin::detail_url( absint( $item['id'] ) ) ), esc_html__( 'Review', 'sustainable-catalyst-engagement-intake' ) ),
			'communications' => sprintf( '<a href="%s">%s</a>', esc_url( SC_EI_Communication_Admin::thread_url( absint( $item['id'] ) ) ), esc_html__( 'Communications', 'sustainable-catalyst-engagement-intake' ) ),
		);

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong>%3$s',
			esc_url( $url ),
			esc_html( $item['reference'] ),
			$this->row_actions( $actions )
		);
	}

	public function column_contact( $item ): string {
		$name  = $item['contact_name'] ?: __( 'Unnamed contact', 'sustainable-catalyst-engagement-intake' );
		$email = $item['contact_email'] ?: '';

		return sprintf(
			'<strong>%1$s</strong><br><a href="mailto:%2$s">%3$s</a>',
			esc_html( $name ),
			esc_attr( $email ),
			esc_html( $email )
		);
	}

	public function prepare_items(): void {
		$per_page          = $this->get_items_per_page( 'sc_ei_inquiries_per_page', 20 );
		$page              = $this->get_pagenum();
		$status            = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$type              = isset( $_GET['inquiry_type'] ) ? sanitize_key( wp_unslash( $_GET['inquiry_type'] ) ) : '';
		$scheduling_status = isset( $_GET['scheduling_status'] ) ? sanitize_key( wp_unslash( $_GET['scheduling_status'] ) ) : '';
		$form_variant      = isset( $_GET['form_variant'] ) ? sanitize_key( wp_unslash( $_GET['form_variant'] ) ) : '';
		$source_page       = isset( $_GET['source_page'] ) ? sanitize_key( wp_unslash( $_GET['source_page'] ) ) : '';
		$conversion_route  = isset( $_GET['conversion_route'] ) ? sanitize_key( wp_unslash( $_GET['conversion_route'] ) ) : '';
		$search            = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$orderby           = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order             = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC';

		$result = SC_EI_Inquiry_Repository::query(
			array(
				'status'            => $status,
				'inquiry_type'      => $type,
				'scheduling_status' => $scheduling_status,
				'form_variant'      => $form_variant,
				'source_page'       => $source_page,
				'conversion_route'  => $conversion_route,
				'search'            => $search,
				'page'              => $page,
				'per_page'          => $per_page,
				'orderby'           => $orderby,
				'order'             => $order,
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
		if ( 'top' !== $which ) {
			return;
		}

		$current_status     = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$current_type       = isset( $_GET['inquiry_type'] ) ? sanitize_key( wp_unslash( $_GET['inquiry_type'] ) ) : '';
		$current_scheduling = isset( $_GET['scheduling_status'] ) ? sanitize_key( wp_unslash( $_GET['scheduling_status'] ) ) : '';
		$current_variant    = isset( $_GET['form_variant'] ) ? sanitize_key( wp_unslash( $_GET['form_variant'] ) ) : '';
		$current_source     = isset( $_GET['source_page'] ) ? sanitize_key( wp_unslash( $_GET['source_page'] ) ) : '';
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="sc-ei-status-filter"><?php esc_html_e( 'Filter by inquiry status', 'sustainable-catalyst-engagement-intake' ); ?></label>
			<select name="status" id="sc-ei-status-filter">
				<option value=""><?php esc_html_e( 'All inquiry statuses', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php foreach ( SC_EI_Statuses::all() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="screen-reader-text" for="sc-ei-type-filter"><?php esc_html_e( 'Filter by inquiry type', 'sustainable-catalyst-engagement-intake' ); ?></label>
			<select name="inquiry_type" id="sc-ei-type-filter">
				<option value=""><?php esc_html_e( 'All inquiry types', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php foreach ( SC_EI_Statuses::inquiry_types() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="screen-reader-text" for="sc-ei-variant-filter"><?php esc_html_e( 'Filter by intake experience', 'sustainable-catalyst-engagement-intake' ); ?></label>
			<select name="form_variant" id="sc-ei-variant-filter">
				<option value=""><?php esc_html_e( 'All intake experiences', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php foreach ( SC_EI_Conversion::variants() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_variant, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="screen-reader-text" for="sc-ei-source-filter"><?php esc_html_e( 'Filter by source page', 'sustainable-catalyst-engagement-intake' ); ?></label>
			<select name="source_page" id="sc-ei-source-filter">
				<option value=""><?php esc_html_e( 'All source pages', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php foreach ( SC_EI_Conversion::sources() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_source, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="screen-reader-text" for="sc-ei-scheduling-filter"><?php esc_html_e( 'Filter by Microsoft Teams scheduling status', 'sustainable-catalyst-engagement-intake' ); ?></label>
			<select name="scheduling_status" id="sc-ei-scheduling-filter">
				<option value=""><?php esc_html_e( 'All Teams statuses', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php foreach ( SC_EI_Teams::scheduling_statuses() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_scheduling, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php submit_button( __( 'Filter', 'sustainable-catalyst-engagement-intake' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}
