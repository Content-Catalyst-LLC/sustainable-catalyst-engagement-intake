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
			'contact_method'    => __( 'Contact Method', 'sustainable-catalyst-engagement-intake' ),
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
			case 'contact_method':
				return esc_html( SC_EI_Teams::label( SC_EI_Teams::contact_methods(), $item['preferred_contact_method'] ?? 'email' ) );
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
			'view' => sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'View', 'sustainable-catalyst-engagement-intake' ) ),
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
		$search            = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$orderby           = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order             = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC';

		$result = SC_EI_Inquiry_Repository::query(
			array(
				'status'            => $status,
				'inquiry_type'      => $type,
				'scheduling_status' => $scheduling_status,
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
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="sc-ei-status-filter"><?php esc_html_e( 'Filter by status', 'sustainable-catalyst-engagement-intake' ); ?></label>
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
