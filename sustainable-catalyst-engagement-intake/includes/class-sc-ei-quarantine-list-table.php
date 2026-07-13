<?php
/**
 * Cross-inquiry quarantine operations table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class SC_EI_Quarantine_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'sc_ei_attachment',
				'plural'   => 'sc_ei_attachments',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		$columns = array(
			'file'              => __( 'Private Document', 'sustainable-catalyst-engagement-intake' ),
			'inquiry'           => __( 'Inquiry', 'sustainable-catalyst-engagement-intake' ),
			'classification'    => __( 'Classification', 'sustainable-catalyst-engagement-intake' ),
			'quarantine_status' => __( 'Quarantine', 'sustainable-catalyst-engagement-intake' ),
			'scan_status'       => __( 'Scanner', 'sustainable-catalyst-engagement-intake' ),
			'storage_status'    => __( 'Storage / Integrity', 'sustainable-catalyst-engagement-intake' ),
			'retention_until'   => __( 'Retention', 'sustainable-catalyst-engagement-intake' ),
			'access'            => __( 'Access', 'sustainable-catalyst-engagement-intake' ),
			'uploaded_at'       => __( 'Uploaded', 'sustainable-catalyst-engagement-intake' ),
		);

		if ( current_user_can( 'sc_intake_bulk_file_actions' ) ) {
			$columns = array_merge( array( 'cb' => '<input type="checkbox">' ), $columns );
		}

		return $columns;
	}

	protected function get_sortable_columns(): array {
		return array(
			'file'              => array( 'original_name', false ),
			'inquiry'           => array( 'reference', false ),
			'quarantine_status' => array( 'quarantine_status', false ),
			'scan_status'       => array( 'scan_status', false ),
			'storage_status'    => array( 'storage_status', false ),
			'retention_until'   => array( 'retention_until', false ),
			'uploaded_at'       => array( 'uploaded_at', true ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No active private documents match the current quarantine filters.', 'sustainable-catalyst-engagement-intake' );
	}

	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="attachment_ids[]" value="%d" aria-label="%s">',
			absint( $item['id'] ),
			esc_attr( sprintf( __( 'Select %s', 'sustainable-catalyst-engagement-intake' ), $item['original_name'] ) )
		);
	}

	public function column_file( $item ): string {
		$inquiry_url = add_query_arg(
			array(
				'page'    => 'sc-engagement-intake',
				'action'  => 'view',
				'inquiry' => absint( $item['inquiry_id'] ),
			),
			admin_url( 'admin.php' )
		);
		$actions = array(
			'view_inquiry' => sprintf( '<a href="%s">%s</a>', esc_url( $inquiry_url ), esc_html__( 'Open inquiry', 'sustainable-catalyst-engagement-intake' ) ),
		);

		return sprintf(
			'<strong>%1$s</strong><br><span class="description">%2$s · %3$s · <code>%4$s…</code></span>%5$s',
			esc_html( $item['original_name'] ?: __( 'Unnamed private document', 'sustainable-catalyst-engagement-intake' ) ),
			esc_html( strtoupper( (string) $item['extension'] ) ),
			esc_html( size_format( absint( $item['size_bytes'] ), 2 ) ),
			esc_html( substr( (string) $item['sha256'], 0, 12 ) ),
			$this->row_actions( $actions )
		);
	}

	public function column_inquiry( $item ): string {
		$url = add_query_arg(
			array(
				'page'    => 'sc-engagement-intake',
				'action'  => 'view',
				'inquiry' => absint( $item['inquiry_id'] ),
			),
			admin_url( 'admin.php' )
		);

		$contact = $item['contact_name'] ?: $item['contact_email'];
		$organization = $item['organization'] ? '<br><span class="description">' . esc_html( $item['organization'] ) . '</span>' : '';

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong><br>%3$s%4$s',
			esc_url( $url ),
			esc_html( $item['reference'] ),
			esc_html( $contact ?: __( 'Unnamed contact', 'sustainable-catalyst-engagement-intake' ) ),
			$organization // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	public function column_classification( $item ): string {
		$categories      = SC_EI_Form_Schema::document_categories();
		$confidentiality = SC_EI_Form_Schema::document_confidentiality_options();

		return sprintf(
			'<strong>%1$s</strong><br><span class="description">%2$s</span>',
			esc_html( $categories[ $item['document_category'] ] ?? ucwords( str_replace( '_', ' ', $item['document_category'] ) ) ),
			esc_html( $confidentiality[ $item['confidentiality'] ] ?? ucwords( str_replace( '_', ' ', $item['confidentiality'] ) ) )
		);
	}

	public function column_quarantine_status( $item ): string {
		$label = ucwords( str_replace( '_', ' ', (string) $item['quarantine_status'] ) );
		return sprintf(
			'<span class="sc-ei-status sc-ei-status--file-%1$s">%2$s</span>',
			esc_attr( $item['quarantine_status'] ),
			esc_html( $label )
		);
	}

	public function column_scan_status( $item ): string {
		$status = sanitize_key( (string) $item['scan_status'] );
		$details = array();

		if ( $item['scanner_provider'] ) {
			$details[] = $item['scanner_provider'];
		}
		$details[] = sprintf(
			_n( '%d attempt', '%d attempts', absint( $item['scan_attempts'] ), 'sustainable-catalyst-engagement-intake' ),
			absint( $item['scan_attempts'] )
		);
		if ( $item['last_scanned_at'] ) {
			$details[] = get_date_from_gmt( $item['last_scanned_at'], 'M j, Y g:i a' );
		}

		$message = $item['scan_message']
			? '<br><span class="description" title="' . esc_attr( $item['scan_message'] ) . '">' . esc_html( wp_trim_words( $item['scan_message'], 12 ) ) . '</span>'
			: '';

		return sprintf(
			'<span class="sc-ei-scan-state sc-ei-scan-state--%1$s">%2$s</span><br><span class="description">%3$s</span>%4$s',
			esc_attr( $status ),
			esc_html( ucwords( str_replace( '_', ' ', $status ) ) ),
			esc_html( implode( ' · ', array_filter( $details ) ) ),
			$message // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	public function column_storage_status( $item ): string {
		$status = sanitize_key( (string) ( $item['storage_status'] ?: 'unverified' ) );
		$warning = in_array( $status, array( 'missing', 'hash_mismatch', 'size_mismatch', 'misplaced', 'unresolvable' ), true );

		return sprintf(
			'<span class="sc-ei-storage-state sc-ei-storage-state--%1$s">%2$s</span><br><span class="description">%3$s</span>%4$s',
			esc_attr( $status ),
			esc_html( ucwords( str_replace( '_', ' ', $status ) ) ),
			esc_html( ucwords( str_replace( '_', ' ', (string) $item['integrity_status'] ) ) ),
			$warning ? '<br><strong class="sc-ei-inline-warning">' . esc_html__( 'Action required', 'sustainable-catalyst-engagement-intake' ) . '</strong>' : ''
		);
	}

	public function column_retention_until( $item ): string {
		if ( empty( $item['retention_until'] ) ) {
			return '<span class="description">' . esc_html__( 'No date', 'sustainable-catalyst-engagement-intake' ) . '</span>';
		}

		$expired = strtotime( $item['retention_until'] . ' UTC' ) <= time();
		return sprintf(
			'<span class="%1$s">%2$s</span>',
			$expired ? 'sc-ei-retention-expired' : '',
			esc_html( get_date_from_gmt( $item['retention_until'], 'M j, Y' ) )
		);
	}

	public function column_access( $item ): string {
		$last = $item['last_downloaded_at']
			? get_date_from_gmt( $item['last_downloaded_at'], 'M j, Y g:i a' )
			: __( 'Never', 'sustainable-catalyst-engagement-intake' );

		return sprintf(
			'<strong>%1$d</strong><br><span class="description">%2$s</span>',
			absint( $item['downloaded_count'] ),
			esc_html( $last )
		);
	}

	public function column_uploaded_at( $item ): string {
		return esc_html( get_date_from_gmt( $item['uploaded_at'], 'M j, Y g:i a' ) );
	}

	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'sc_ei_quarantine_per_page', 20 );
		$page     = $this->get_pagenum();

		$result = SC_EI_Attachment_Repository::query_operations(
			array(
				'quarantine_status' => isset( $_GET['quarantine_status'] ) ? sanitize_key( wp_unslash( $_GET['quarantine_status'] ) ) : '',
				'scan_status'       => isset( $_GET['scan_status'] ) ? sanitize_key( wp_unslash( $_GET['scan_status'] ) ) : '',
				'validation_status' => isset( $_GET['validation_status'] ) ? sanitize_key( wp_unslash( $_GET['validation_status'] ) ) : '',
				'storage_status'    => isset( $_GET['storage_status'] ) ? sanitize_key( wp_unslash( $_GET['storage_status'] ) ) : '',
				'document_category' => isset( $_GET['document_category'] ) ? sanitize_key( wp_unslash( $_GET['document_category'] ) ) : '',
				'confidentiality'   => isset( $_GET['confidentiality'] ) ? sanitize_key( wp_unslash( $_GET['confidentiality'] ) ) : '',
				'retention'         => isset( $_GET['retention'] ) ? sanitize_key( wp_unslash( $_GET['retention'] ) ) : '',
				'search'            => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
				'page'              => $page,
				'per_page'          => $per_page,
				'orderby'           => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'uploaded_at',
				'order'             => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC',
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
		if ( 'top' !== $which || ! current_user_can( 'sc_intake_bulk_file_actions' ) ) {
			return;
		}
		?>
		<div class="alignleft actions sc-ei-quarantine-bulk" data-sc-ei-bulk-controls>
			<select name="bulk_operation" data-sc-ei-bulk-operation aria-label="<?php esc_attr_e( 'Bulk document action', 'sustainable-catalyst-engagement-intake' ); ?>">
				<option value=""><?php esc_html_e( 'Bulk document action', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php if ( current_user_can( 'sc_intake_manage_scanner' ) ) : ?><option value="retry_scan"><?php esc_html_e( 'Retry external scan', 'sustainable-catalyst-engagement-intake' ); ?></option><?php endif; ?>
				<?php if ( current_user_can( 'sc_intake_download_files' ) ) : ?><option value="verify_integrity"><?php esc_html_e( 'Verify storage and integrity', 'sustainable-catalyst-engagement-intake' ); ?></option><?php endif; ?>
				<?php if ( current_user_can( 'sc_intake_release_files' ) ) : ?>
					<option value="approve"><?php esc_html_e( 'Approve for controlled use', 'sustainable-catalyst-engagement-intake' ); ?></option>
					<option value="quarantine"><?php esc_html_e( 'Return to quarantine', 'sustainable-catalyst-engagement-intake' ); ?></option>
					<option value="replacement_requested"><?php esc_html_e( 'Request replacement', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php endif; ?>
				<?php if ( current_user_can( 'sc_intake_manage_file_retention' ) ) : ?><option value="set_retention"><?php esc_html_e( 'Set retention date', 'sustainable-catalyst-engagement-intake' ); ?></option><?php endif; ?>
				<?php if ( current_user_can( 'sc_intake_delete' ) ) : ?><option value="reject_delete"><?php esc_html_e( 'Reject and delete physical files', 'sustainable-catalyst-engagement-intake' ); ?></option><?php endif; ?>
			</select>
			<input type="date" name="bulk_retention_date" data-sc-ei-bulk-retention hidden aria-label="<?php esc_attr_e( 'Bulk retention date', 'sustainable-catalyst-engagement-intake' ); ?>">
			<input type="text" name="bulk_confirmation" data-sc-ei-bulk-confirmation hidden autocomplete="off" placeholder="<?php esc_attr_e( 'Type REJECT SELECTED', 'sustainable-catalyst-engagement-intake' ); ?>">
			<button type="submit" class="button" name="run_bulk_operation" value="1"><?php esc_html_e( 'Apply', 'sustainable-catalyst-engagement-intake' ); ?></button>
			<span class="description"><?php esc_html_e( 'Maximum 50 selected records; scanner retry uses the configured bulk limit.', 'sustainable-catalyst-engagement-intake' ); ?></span>
		</div>
		<?php
	}
}
