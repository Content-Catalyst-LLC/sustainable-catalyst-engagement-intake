<?php
/**
 * Administrative review queue table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class SC_EI_Review_List_Table extends WP_List_Table {

	private string $view = 'queue';

	public function __construct( string $view = 'queue' ) {
		$this->view = $view;
		parent::__construct(
			array(
				'singular' => 'sc_ei_review_inquiry',
				'plural'   => 'sc_ei_review_inquiries',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		$columns = array(
			'reference'       => __( 'Inquiry', 'sustainable-catalyst-engagement-intake' ),
			'request'         => __( 'Request / Scope', 'sustainable-catalyst-engagement-intake' ),
			'assignment'      => __( 'Assignment', 'sustainable-catalyst-engagement-intake' ),
			'review_stage'    => __( 'Review Stage', 'sustainable-catalyst-engagement-intake' ),
			'priority_due'    => __( 'Priority / Due', 'sustainable-catalyst-engagement-intake' ),
			'fit_next_step'   => __( 'Fit / Next Step', 'sustainable-catalyst-engagement-intake' ),
			'risk_evidence'   => __( 'Risk / Evidence', 'sustainable-catalyst-engagement-intake' ),
			'documents'       => __( 'Documents', 'sustainable-catalyst-engagement-intake' ),
			'status_age'      => __( 'Inquiry / Age', 'sustainable-catalyst-engagement-intake' ),
			'last_reviewed_at'=> __( 'Last Reviewed', 'sustainable-catalyst-engagement-intake' ),
		);

		if ( current_user_can( 'sc_intake_bulk_review_actions' ) ) {
			$columns = array_merge( array( 'cb' => '<input type="checkbox">' ), $columns );
		}
		return $columns;
	}

	protected function get_sortable_columns(): array {
		return array(
			'reference'        => array( 'reference', false ),
			'assignment'       => array( 'contact_name', false ),
			'review_stage'     => array( 'review_stage', false ),
			'priority_due'     => array( 'review_due_at', true ),
			'fit_next_step'    => array( 'fit_decision', false ),
			'risk_evidence'    => array( 'risk_level', false ),
			'status_age'       => array( 'created_at', false ),
			'last_reviewed_at' => array( 'last_reviewed_at', true ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No inquiries match the current administrative review filters.', 'sustainable-catalyst-engagement-intake' );
	}

	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="inquiry_ids[]" value="%d" aria-label="%s">',
			absint( $item['id'] ),
			esc_attr( sprintf( __( 'Select inquiry %s', 'sustainable-catalyst-engagement-intake' ), $item['reference'] ) )
		);
	}

	public function column_reference( $item ): string {
		$url = SC_EI_Review_Admin::detail_url( absint( $item['id'] ) );
		$actions = array(
			'review' => sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Open review', 'sustainable-catalyst-engagement-intake' ) ),
			'record' => sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'    => 'sc-engagement-intake',
							'action'  => 'view',
							'inquiry' => absint( $item['id'] ),
						),
						admin_url( 'admin.php' )
					)
				),
				esc_html__( 'Full inquiry record', 'sustainable-catalyst-engagement-intake' )
			),
		);

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong><br><span class="description">%3$s</span>%4$s',
			esc_url( $url ),
			esc_html( $item['reference'] ),
			esc_html( get_date_from_gmt( $item['created_at'], 'M j, Y g:i a' ) ),
			$this->row_actions( $actions )
		);
	}

	public function column_request( $item ): string {
		$type = SC_EI_Statuses::inquiry_types()[ $item['inquiry_type'] ] ?? $item['inquiry_type'];
		$title = $item['subject'] ?: $item['project_summary'] ?: $item['message'];
		$title = wp_trim_words( wp_strip_all_tags( (string) $title ), 16, '…' );

		return sprintf(
			'<strong>%1$s</strong><br>%2$s<br><span class="description">%3$s%4$s</span>',
			esc_html( $item['contact_name'] ?: $item['contact_email'] ),
			esc_html( $title ?: __( 'No request summary supplied', 'sustainable-catalyst-engagement-intake' ) ),
			esc_html( $type ),
			$item['organization'] ? ' · ' . esc_html( $item['organization'] ) : ''
		);
	}

	public function column_assignment( $item ): string {
		if ( empty( $item['assigned_user_id'] ) ) {
			return '<span class="sc-ei-review-assignment sc-ei-review-assignment--unassigned">' . esc_html__( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) . '</span>';
		}

		$mine = absint( $item['assigned_user_id'] ) === get_current_user_id();
		return sprintf(
			'<strong>%1$s</strong>%2$s<br><span class="description">%3$s</span>',
			esc_html( $item['assigned_name'] ?: 'User #' . absint( $item['assigned_user_id'] ) ),
			$mine ? ' <span class="sc-ei-review-mine">' . esc_html__( 'Mine', 'sustainable-catalyst-engagement-intake' ) . '</span>' : '',
			$item['assignment_at'] ? esc_html( get_date_from_gmt( $item['assignment_at'], 'M j, Y' ) ) : esc_html__( 'Assignment date unavailable', 'sustainable-catalyst-engagement-intake' )
		);
	}

	public function column_review_stage( $item ): string {
		$progress = SC_EI_Review_Schema::checklist_progress( $item['review_checklist'] );
		$escalation = in_array( $item['escalation_status'], array( 'requested', 'under_review' ), true )
			? '<br><span class="sc-ei-review-escalation">' . esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::escalation_statuses(), $item['escalation_status'] ) ) . '</span>'
			: '';

		return sprintf(
			'<span class="sc-ei-review-stage sc-ei-review-stage--%1$s">%2$s</span><br><span class="description">%3$d%% checklist</span>%4$s',
			esc_attr( $item['review_stage'] ),
			esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::stages(), $item['review_stage'] ) ),
			absint( $progress['percent'] ),
			$escalation // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	public function column_priority_due( $item ): string {
		$timing = SC_EI_Review_Schema::timing( $item );
		$due = $item['review_due_at']
			? get_date_from_gmt( $item['review_due_at'], 'M j, Y g:i a' )
			: __( 'No due date', 'sustainable-catalyst-engagement-intake' );

		return sprintf(
			'<span class="sc-ei-review-priority sc-ei-review-priority--%1$s">%2$s</span><br><span class="sc-ei-review-due sc-ei-review-due--%3$s">%4$s</span>%5$s',
			esc_attr( $item['review_priority'] ),
			esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::priorities(), $item['review_priority'] ) ),
			esc_attr( $timing['due_state'] ),
			esc_html( $due ),
			$timing['is_stale'] ? '<br><strong class="sc-ei-inline-warning">' . esc_html__( 'Stale review', 'sustainable-catalyst-engagement-intake' ) . '</strong>' : ''
		);
	}

	public function column_fit_next_step( $item ): string {
		return sprintf(
			'<strong>%1$s</strong><br><span class="description">%2$s · %3$s</span>',
			esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::fit_decisions(), $item['fit_decision'] ) ),
			esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::next_steps(), $item['recommended_next_step'] ) ),
			esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::confidence_levels(), $item['fit_confidence'] ) )
		);
	}

	public function column_risk_evidence( $item ): string {
		return sprintf(
			'<span class="sc-ei-review-risk sc-ei-review-risk--%1$s">%2$s</span><br><span class="description">%3$s · scope %4$s</span>',
			esc_attr( $item['risk_level'] ),
			esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::risk_levels(), $item['risk_level'] ) ),
			esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::evidence_readiness_levels(), $item['evidence_readiness'] ) ),
			esc_html( strtolower( SC_EI_Review_Schema::label( SC_EI_Review_Schema::scope_clarity_levels(), $item['scope_clarity'] ) ) )
		);
	}

	public function column_documents( $item ): string {
		$count = absint( $item['document_count'] );
		$attention = absint( $item['document_attention_count'] );
		if ( ! $count ) {
			return '—';
		}

		return sprintf(
			'<strong>%1$d</strong> %2$s<br><span class="%3$s">%4$s</span>',
			$count,
			esc_html( _n( 'document', 'documents', $count, 'sustainable-catalyst-engagement-intake' ) ),
			$attention ? 'sc-ei-inline-warning' : 'description',
			$attention
				? esc_html( sprintf( _n( '%d needs attention', '%d need attention', $attention, 'sustainable-catalyst-engagement-intake' ), $attention ) )
				: esc_html__( 'No document alerts', 'sustainable-catalyst-engagement-intake' )
		);
	}

	public function column_status_age( $item ): string {
		$timing = SC_EI_Review_Schema::timing( $item );
		return sprintf(
			'<span class="sc-ei-status sc-ei-status--%1$s">%2$s</span><br><span class="description">%3$d days old · %4$d idle</span>',
			esc_attr( $item['status'] ),
			esc_html( SC_EI_Statuses::label( $item['status'] ) ),
			absint( $timing['age_days'] ),
			absint( $timing['idle_days'] )
		);
	}

	public function column_last_reviewed_at( $item ): string {
		if ( empty( $item['last_reviewed_at'] ) ) {
			return '<span class="description">' . esc_html__( 'Not reviewed', 'sustainable-catalyst-engagement-intake' ) . '</span>';
		}
		return sprintf(
			'%1$s<br><span class="description">%2$s · v%3$d</span>',
			esc_html( get_date_from_gmt( $item['last_reviewed_at'], 'M j, Y g:i a' ) ),
			esc_html( $item['last_reviewer_name'] ?: 'User #' . absint( $item['last_reviewed_by'] ) ),
			absint( $item['review_version'] )
		);
	}

	public function column_default( $item, $column_name ) {
		return '';
	}

	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'sc_ei_reviews_per_page', 20 );
		$page     = $this->get_pagenum();

		$result = SC_EI_Review_Repository::query(
			array(
				'view'              => $this->view,
				'review_stage'      => isset( $_GET['review_stage'] ) ? sanitize_key( wp_unslash( $_GET['review_stage'] ) ) : '',
				'review_priority'   => isset( $_GET['review_priority'] ) ? sanitize_key( wp_unslash( $_GET['review_priority'] ) ) : '',
				'fit_decision'      => isset( $_GET['fit_decision'] ) ? sanitize_key( wp_unslash( $_GET['fit_decision'] ) ) : '',
				'risk_level'        => isset( $_GET['risk_level'] ) ? sanitize_key( wp_unslash( $_GET['risk_level'] ) ) : '',
				'escalation_status' => isset( $_GET['escalation_status'] ) ? sanitize_key( wp_unslash( $_GET['escalation_status'] ) ) : '',
				'assignee'          => isset( $_GET['assignee'] ) ? sanitize_text_field( wp_unslash( $_GET['assignee'] ) ) : '',
				'due_state'         => isset( $_GET['due_state'] ) ? sanitize_key( wp_unslash( $_GET['due_state'] ) ) : '',
				'status'            => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
				'inquiry_type'      => isset( $_GET['inquiry_type'] ) ? sanitize_key( wp_unslash( $_GET['inquiry_type'] ) ) : '',
				'source_page'       => isset( $_GET['source_page'] ) ? sanitize_key( wp_unslash( $_GET['source_page'] ) ) : '',
				'search'            => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
				'page'              => $page,
				'per_page'          => $per_page,
				'orderby'           => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'review_due_at',
				'order'             => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'ASC',
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
		if ( 'top' !== $which || ! current_user_can( 'sc_intake_bulk_review_actions' ) ) {
			return;
		}
		?>
		<div class="alignleft actions sc-ei-review-bulk" data-sc-ei-review-bulk>
			<select name="bulk_review_operation" data-sc-ei-review-bulk-operation aria-label="<?php esc_attr_e( 'Bulk review action', 'sustainable-catalyst-engagement-intake' ); ?>">
				<option value=""><?php esc_html_e( 'Bulk review action', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<option value="assign"><?php esc_html_e( 'Assign reviewer', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<option value="unassign"><?php esc_html_e( 'Unassign', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<option value="priority"><?php esc_html_e( 'Set priority', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<option value="stage"><?php esc_html_e( 'Set review stage', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<option value="due"><?php esc_html_e( 'Set due date', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<option value="escalate"><?php esc_html_e( 'Request escalation', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<option value="resolve_escalation"><?php esc_html_e( 'Resolve escalation', 'sustainable-catalyst-engagement-intake' ); ?></option>
			</select>
			<select name="bulk_assigned_user_id" data-sc-ei-review-bulk-assignee hidden>
				<option value=""><?php esc_html_e( 'Select reviewer', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php foreach ( SC_EI_Review_Schema::reviewers() as $reviewer ) : ?>
					<option value="<?php echo esc_attr( $reviewer->ID ); ?>"><?php echo esc_html( $reviewer->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="bulk_review_priority" data-sc-ei-review-bulk-priority hidden><?php foreach ( SC_EI_Review_Schema::priorities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
			<select name="bulk_review_stage" data-sc-ei-review-bulk-stage hidden><?php foreach ( SC_EI_Review_Schema::stages() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
			<input type="datetime-local" name="bulk_review_due_local" data-sc-ei-review-bulk-due hidden>
			<input type="text" name="bulk_review_reason" data-sc-ei-review-bulk-reason hidden placeholder="<?php esc_attr_e( 'Reason or operational note', 'sustainable-catalyst-engagement-intake' ); ?>">
			<button type="submit" class="button" name="run_bulk_review" value="1"><?php esc_html_e( 'Apply', 'sustainable-catalyst-engagement-intake' ); ?></button>
		</div>
		<?php
	}
}
