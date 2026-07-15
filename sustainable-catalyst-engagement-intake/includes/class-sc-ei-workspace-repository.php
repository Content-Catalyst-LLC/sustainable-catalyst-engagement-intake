<?php
/**
 * Secure client workspace and collaboration repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workspace_Repository {

	public const MIGRATION_KEY = 'v1_5_0_secure_client_workspace_collaboration';
	private const OPTION_SCHEMA = 'sc_ei_workspace_schema_version';

	public static function register(): void {}

	public static function maybe_upgrade(): void {
		$stored = (string) get_option( self::OPTION_SCHEMA, '' );
		if ( version_compare( $stored, SC_EI_WORKSPACE_SCHEMA_VERSION, '<' ) ) {
			SC_EI_Database::install();
		}
		self::record_migration( $stored );
		if ( ! in_array( false, SC_EI_Database::workspace_columns_exist(), true ) ) {
			update_option( self::OPTION_SCHEMA, SC_EI_WORKSPACE_SCHEMA_VERSION, false );
		}
	}

	public static function record_migration( string $from_schema = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$columns = SC_EI_Database::workspace_columns_exist();
		$ok = ! in_array( false, $columns, true );
		$now = current_time( 'mysql', true );
		$context = array(
			'release'                     => 'Secure Client Workspace and Collaboration',
			'from_schema'                 => $from_schema,
			'to_schema'                   => SC_EI_WORKSPACE_SCHEMA_VERSION,
			'no_destructive_migration'    => true,
			'workspace_isolation'         => true,
			'sender_projection_allowlist' => SC_EI_Workspace_Schema::sender_projection_keys(),
			'private_notes_excluded'       => true,
			'missing_contract_items'      => array_keys( array_filter( $columns, static fn( bool $value ): bool => ! $value ) ),
		);
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::MIGRATION_KEY,
			'from_version'  => $from_schema,
			'to_version'    => SC_EI_WORKSPACE_SCHEMA_VERSION,
			'status'        => $ok ? 'completed' : 'failed',
			'context_json'  => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $ok ? $now : null,
			'error_code'    => $ok ? '' : 'workspace_schema_incomplete',
			'error_message' => $ok ? '' : 'The client-workspace database contract is incomplete.',
			'created_at'    => $existing['created_at'] ?? $now,
			'updated_at'    => $now,
		);
		if ( $existing ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $table, $data, self::formats( $data ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $result ) {
			return new WP_Error( 'workspace_migration_journal_failed', __( 'The client-workspace migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function create_for_engagement( int $engagement_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$engagement = SC_EI_Engagement_Repository::find( $engagement_id );
		if ( ! $engagement ) {
			return new WP_Error( 'workspace_engagement_missing', __( 'Choose a valid engagement.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$existing = self::for_engagement( $engagement_id );
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$public_id = wp_generate_uuid4();
		$data = array(
			'public_id'       => $public_id,
			'workspace_number'=> 'SC-WS-TMP-' . strtoupper( substr( str_replace( '-', '', $public_id ), 0, 12 ) ),
			'inquiry_id'      => absint( $engagement['inquiry_id'] ),
			'engagement_id'   => $engagement_id,
			'title'           => sanitize_text_field( (string) ( $input['title'] ?? $engagement['title'] ) ),
			'status'          => 'draft',
			'owner_user_id'   => absint( $input['owner_user_id'] ?? $engagement['owner_user_id'] ) ?: null,
			'sender_summary'  => sanitize_textarea_field( (string) ( $input['sender_summary'] ?? '' ) ),
			'sender_next_step'=> sanitize_textarea_field( (string) ( $input['sender_next_step'] ?? '' ) ),
			'sender_visible'  => empty( $input['sender_visible'] ) ? 0 : 1,
			'row_version'     => 0,
			'activated_at'    => null,
			'paused_at'       => null,
			'completed_at'    => null,
			'created_by'      => $actor_user_id,
			'created_at'      => $now,
			'updated_at'      => $now,
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'client_workspaces' ), $data, self::formats( $data, self::workspace_integer_fields() ) ) ) {
			self::record_failure( 'workspace_insert_failed', $engagement_id, array( 'db_error' => (string) $wpdb->last_error ) );
			return new WP_Error( 'workspace_save_failed', __( 'The client workspace could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		$number = 'SC-WS-' . gmdate( 'Y' ) . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
		if ( 1 !== $wpdb->update( SC_EI_Database::table( 'client_workspaces' ), array( 'workspace_number' => $number ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ) ) {
			$wpdb->delete( SC_EI_Database::table( 'client_workspaces' ), array( 'id' => $id ), array( '%d' ) );
			return new WP_Error( 'workspace_number_failed', __( 'The workspace number could not be finalized.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$inquiry = SC_EI_Inquiry_Repository::find( absint( $engagement['inquiry_id'] ) );
		$sender_member = self::add_member(
			$id,
			array(
				'member_type' => 'sender',
				'email'       => (string) ( $inquiry['contact_email'] ?? '' ),
				'display_name'=> (string) ( $inquiry['contact_name'] ?? '' ),
				'role_label'  => __( 'Authorized sender', 'sustainable-catalyst-engagement-intake' ),
				'permissions' => SC_EI_Workspace_Schema::member_permissions(),
			),
			$actor_user_id
		);
		if ( is_wp_error( $sender_member ) ) {
			$wpdb->delete( SC_EI_Database::table( 'workspace_members' ), array( 'workspace_id' => $id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'client_workspaces' ), array( 'id' => $id ), array( '%d' ) );
			return $sender_member;
		}

		if ( $actor_user_id ) {
			$actor = get_userdata( $actor_user_id );
			self::add_member(
				$id,
				array(
					'member_type' => 'staff',
					'user_id'     => $actor_user_id,
					'display_name'=> $actor ? (string) $actor->display_name : '',
					'role_label'  => __( 'Workspace owner', 'sustainable-catalyst-engagement-intake' ),
					'permissions' => SC_EI_Workspace_Schema::member_permissions(),
				),
				$actor_user_id
			);
		}

		self::event( $id, absint( $engagement['inquiry_id'] ), 'workspace_created', 'workspace', $id, '', 'draft', 'staff', $actor_user_id, array( 'schema' => SC_EI_Workspace_Schema::HANDOFF_SCHEMA ) );
		return self::find( $id );
	}

	public static function transition( int $workspace_id, string $status, string $confirmation, string $note, int $actor_user_id ) {
		global $wpdb;
		$current = self::find( $workspace_id );
		if ( ! $current ) return new WP_Error( 'workspace_missing', __( 'The client workspace could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		$status = SC_EI_Workspace_Schema::sanitize_status( $status, SC_EI_Workspace_Schema::workspace_statuses(), (string) $current['status'] );
		$expected = strtoupper( $status . ' ' . $current['workspace_number'] );
		if ( strtoupper( trim( $confirmation ) ) !== $expected ) return new WP_Error( 'workspace_confirmation_failed', sprintf( __( 'Type %s to confirm.', 'sustainable-catalyst-engagement-intake' ), $expected ) );
		$now = current_time( 'mysql', true );
		$data = array(
			'status'          => $status,
			'row_version'     => absint( $current['row_version'] ) + 1,
			'updated_at'      => $now,
			'activated_at'    => 'active' === $status ? ( $current['activated_at'] ?: $now ) : $current['activated_at'],
			'paused_at'       => 'paused' === $status ? $now : $current['paused_at'],
			'completed_at'    => 'completed' === $status ? $now : $current['completed_at'],
		);
		$updated = $wpdb->update( SC_EI_Database::table( 'client_workspaces' ), $data, array( 'id' => $workspace_id, 'row_version' => absint( $current['row_version'] ) ), self::formats( $data, self::workspace_integer_fields() ), array( '%d', '%d' ) );
		if ( 1 !== $updated ) return new WP_Error( 'workspace_transition_conflict', __( 'The workspace changed before the transition was saved.', 'sustainable-catalyst-engagement-intake' ) );
		self::event( $workspace_id, absint( $current['inquiry_id'] ), 'workspace_transitioned', 'workspace', $workspace_id, (string) $current['status'], $status, 'staff', $actor_user_id, array( 'note' => sanitize_textarea_field( $note ) ) );
		return self::find( $workspace_id );
	}

	public static function update_sender_projection( int $workspace_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$current = self::find( $workspace_id );
		if ( ! $current ) return new WP_Error( 'workspace_missing', __( 'The client workspace could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		$data = array(
			'sender_summary'   => sanitize_textarea_field( (string) ( $input['sender_summary'] ?? $current['sender_summary'] ) ),
			'sender_next_step' => sanitize_textarea_field( (string) ( $input['sender_next_step'] ?? $current['sender_next_step'] ) ),
			'sender_visible'   => empty( $input['sender_visible'] ) ? 0 : 1,
			'row_version'      => absint( $current['row_version'] ) + 1,
			'updated_at'       => current_time( 'mysql', true ),
		);
		$updated = $wpdb->update( SC_EI_Database::table( 'client_workspaces' ), $data, array( 'id' => $workspace_id, 'row_version' => absint( $current['row_version'] ) ), self::formats( $data, self::workspace_integer_fields() ), array( '%d', '%d' ) );
		if ( 1 !== $updated ) return new WP_Error( 'workspace_update_conflict', __( 'The workspace changed before the update was saved.', 'sustainable-catalyst-engagement-intake' ) );
		self::event( $workspace_id, absint( $current['inquiry_id'] ), 'workspace_projection_updated', 'workspace', $workspace_id, '', '', 'staff', $actor_user_id, array( 'sender_visible' => (bool) $data['sender_visible'] ) );
		return self::find( $workspace_id );
	}

	public static function add_member( int $workspace_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$workspace = self::find( $workspace_id );
		if ( ! $workspace ) {
			return new WP_Error( 'workspace_missing', __( 'The client workspace could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$member_type = in_array( sanitize_key( (string) ( $input['member_type'] ?? 'sender' ) ), array( 'sender', 'staff' ), true ) ? sanitize_key( (string) $input['member_type'] ) : 'sender';
		$user_id = absint( $input['user_id'] ?? 0 );
		$email = sanitize_email( (string) ( $input['email'] ?? '' ) );
		if ( 'staff' === $member_type && ! $user_id ) {
			return new WP_Error( 'workspace_staff_user_required', __( 'Choose a valid staff user.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'sender' === $member_type && ! is_email( $email ) ) {
			return new WP_Error( 'workspace_sender_email_required', __( 'A valid sender email is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$permissions = array_values( array_intersect( SC_EI_Workspace_Schema::member_permissions(), array_map( 'sanitize_key', (array) ( $input['permissions'] ?? array() ) ) ) );
		$data = array(
			'public_id'        => wp_generate_uuid4(),
			'workspace_id'     => $workspace_id,
			'inquiry_id'       => absint( $workspace['inquiry_id'] ),
			'member_type'      => $member_type,
			'user_id'          => $user_id ?: null,
			'email_hash'       => $email ? hash( 'sha256', strtolower( $email ) ) : '',
			'display_name'     => sanitize_text_field( (string) ( $input['display_name'] ?? '' ) ),
			'role_label'       => sanitize_text_field( (string) ( $input['role_label'] ?? '' ) ),
			'permissions_json' => wp_json_encode( $permissions, JSON_UNESCAPED_SLASHES ),
			'status'           => 'active',
			'invited_at'       => current_time( 'mysql', true ),
			'activated_at'     => 'staff' === $member_type ? current_time( 'mysql', true ) : null,
			'revoked_at'       => null,
			'created_by'       => $actor_user_id ?: null,
			'created_at'       => current_time( 'mysql', true ),
			'updated_at'       => current_time( 'mysql', true ),
		);
		$duplicate_sql = 'staff' === $member_type
			? $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'workspace_members' ) . ' WHERE workspace_id = %d AND member_type = %s AND user_id = %d AND status = %s LIMIT 1', $workspace_id, $member_type, $user_id, 'active' )
			: $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'workspace_members' ) . ' WHERE workspace_id = %d AND member_type = %s AND email_hash = %s AND status = %s LIMIT 1', $workspace_id, $member_type, $data['email_hash'], 'active' );
		$existing = $wpdb->get_row( $duplicate_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing ) {
			return $existing;
		}
		if ( false === $wpdb->insert( SC_EI_Database::table( 'workspace_members' ), $data, self::formats( $data, array( 'workspace_id', 'inquiry_id', 'user_id', 'created_by' ) ) ) ) {
			return new WP_Error( 'workspace_member_save_failed', __( 'The workspace member could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		self::event( $workspace_id, absint( $workspace['inquiry_id'] ), 'workspace_member_added', 'member', $id, '', 'active', 'staff', $actor_user_id, array( 'member_type' => $member_type ) );
		return self::find_row( 'workspace_members', $id );
	}

	public static function members( int $workspace_id ): array {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'workspace_members' ) . ' WHERE workspace_id = %d ORDER BY member_type ASC, id ASC', $workspace_id ),
			ARRAY_A
		);
	}

	public static function add_sender_message( int $workspace_id, int $inquiry_id, string $body ): mixed {
		global $wpdb;
		$workspace = self::find( $workspace_id );
		if ( ! $workspace || absint( $workspace['inquiry_id'] ) !== $inquiry_id || empty( $workspace['sender_visible'] ) || ! in_array( (string) $workspace['status'], array( 'active', 'paused' ), true ) ) {
			return new WP_Error( 'workspace_message_unavailable', __( 'This workspace is not available for sender collaboration.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$body = sanitize_textarea_field( $body );
		if ( '' === trim( $body ) ) {
			return new WP_Error( 'workspace_message_required', __( 'Enter a collaboration message.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$data = array(
			'public_id'             => wp_generate_uuid4(),
			'workspace_id'          => $workspace_id,
			'inquiry_id'            => $inquiry_id,
			'direction'             => 'inbound',
			'sender_type'           => 'sender',
			'body_text'             => $body,
			'sender_visible'        => 1,
			'related_deliverable_id'=> null,
			'created_by'            => null,
			'created_at'            => current_time( 'mysql', true ),
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'workspace_messages' ), $data, self::formats( $data, array( 'workspace_id', 'inquiry_id', 'sender_visible', 'related_deliverable_id', 'created_by' ) ) ) ) {
			return new WP_Error( 'workspace_message_save_failed', __( 'The workspace message could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		self::event( $workspace_id, $inquiry_id, 'workspace_sender_message_created', 'message', $id, '', 'recorded', 'sender', 0, array() );
		return self::find_row( 'workspace_messages', $id );
	}

	public static function add_milestone( int $workspace_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$workspace = self::find( $workspace_id );
		if ( ! $workspace ) return new WP_Error( 'workspace_missing', __( 'The client workspace could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		if ( '' === $title ) return new WP_Error( 'workspace_milestone_title_required', __( 'A milestone title is required.', 'sustainable-catalyst-engagement-intake' ) );
		$data = array(
			'public_id'      => wp_generate_uuid4(), 'workspace_id' => $workspace_id, 'inquiry_id' => absint( $workspace['inquiry_id'] ),
			'title'          => $title, 'description' => sanitize_textarea_field( (string) ( $input['description'] ?? '' ) ),
			'status'         => SC_EI_Workspace_Schema::sanitize_status( (string) ( $input['status'] ?? 'planned' ), SC_EI_Workspace_Schema::milestone_statuses(), 'planned' ),
			'due_date'       => self::date_or_null( $input['due_date'] ?? '' ), 'sender_visible' => empty( $input['sender_visible'] ) ? 0 : 1,
			'sort_order'     => absint( $input['sort_order'] ?? 0 ), 'completed_by' => null, 'completed_at' => null,
			'created_by'     => $actor_user_id, 'created_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ),
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'workspace_milestones' ), $data, self::formats( $data, array( 'workspace_id','inquiry_id','sender_visible','sort_order','completed_by','created_by' ) ) ) ) return new WP_Error( 'workspace_milestone_save_failed', __( 'The milestone could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		$id=(int)$wpdb->insert_id; self::event( $workspace_id, absint($workspace['inquiry_id']), 'milestone_created', 'milestone', $id, '', (string)$data['status'], 'staff', $actor_user_id, array( 'title'=>$title ) );
		return self::find_row( 'workspace_milestones', $id );
	}

	public static function add_deliverable( int $workspace_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$workspace = self::find( $workspace_id );
		if ( ! $workspace ) return new WP_Error( 'workspace_missing', __( 'The client workspace could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		if ( '' === $title ) return new WP_Error( 'workspace_deliverable_title_required', __( 'A deliverable title is required.', 'sustainable-catalyst-engagement-intake' ) );
		$data = array(
			'public_id'=>wp_generate_uuid4(),'workspace_id'=>$workspace_id,'inquiry_id'=>absint($workspace['inquiry_id']),'title'=>$title,
			'description'=>sanitize_textarea_field((string)($input['description']??'')),'status'=>'draft','due_date'=>self::date_or_null($input['due_date']??''),
			'sender_visible'=>empty($input['sender_visible'])?0:1,'approval_required'=>empty($input['approval_required'])?0:1,'sender_decision'=>'pending',
			'sender_decision_note'=>'','decided_at'=>null,'current_version'=>max(1,absint($input['current_version']??1)),'attachment_id'=>absint($input['attachment_id']??0)?:null,
			'row_version'=>0,'created_by'=>$actor_user_id,'created_at'=>current_time('mysql',true),'updated_at'=>current_time('mysql',true),
		);
		if(false===$wpdb->insert(SC_EI_Database::table('workspace_deliverables'),$data,self::formats($data,array('workspace_id','inquiry_id','sender_visible','approval_required','current_version','attachment_id','row_version','created_by')))) return new WP_Error('workspace_deliverable_save_failed',__('The deliverable could not be saved.','sustainable-catalyst-engagement-intake'));
		$id=(int)$wpdb->insert_id; self::event($workspace_id,absint($workspace['inquiry_id']),'deliverable_created','deliverable',$id,'','draft','staff',$actor_user_id,array('title'=>$title));
		return self::find_row('workspace_deliverables',$id);
	}

	public static function publish_deliverable( int $deliverable_id, bool $sender_visible, int $actor_user_id ) {
		global $wpdb; $current=self::find_row('workspace_deliverables',$deliverable_id); if(!$current)return new WP_Error('workspace_deliverable_missing',__('The deliverable could not be found.','sustainable-catalyst-engagement-intake'));
		$data=array('status'=>'published','sender_visible'=>$sender_visible?1:0,'sender_decision'=>'pending','sender_decision_note'=>'','decided_at'=>null,'row_version'=>absint($current['row_version'])+1,'updated_at'=>current_time('mysql',true));
		$updated=$wpdb->update(SC_EI_Database::table('workspace_deliverables'),$data,array('id'=>$deliverable_id,'row_version'=>absint($current['row_version'])),self::formats($data,array('sender_visible','row_version')),array('%d','%d'));
		if(1!==$updated)return new WP_Error('workspace_deliverable_publish_conflict',__('The deliverable changed before publication.','sustainable-catalyst-engagement-intake'));
		self::event(absint($current['workspace_id']),absint($current['inquiry_id']),'deliverable_published','deliverable',$deliverable_id,(string)$current['status'],'published','staff',$actor_user_id,array('sender_visible'=>$sender_visible));
		return self::find_row('workspace_deliverables',$deliverable_id);
	}

	public static function record_sender_deliverable_decision( int $deliverable_id, string $decision, string $note, int $inquiry_id ) {
		global $wpdb; $current=self::find_row('workspace_deliverables',$deliverable_id);
		if(!$current||absint($current['inquiry_id'])!==$inquiry_id||empty($current['sender_visible'])||'published'!==(string)$current['status']) return new WP_Error('workspace_deliverable_unavailable',__('The deliverable is not available for this portal.','sustainable-catalyst-engagement-intake'));
		$decision=SC_EI_Workspace_Schema::sanitize_status($decision,SC_EI_Workspace_Schema::sender_decisions(),'pending');
		if(!in_array($decision,array('accepted','changes_requested'),true))return new WP_Error('workspace_deliverable_decision_invalid',__('Choose a valid deliverable response.','sustainable-catalyst-engagement-intake'));
		$status='accepted'===$decision?'accepted':'changes_requested'; $now=current_time('mysql',true);
		$data=array('status'=>$status,'sender_decision'=>$decision,'sender_decision_note'=>sanitize_textarea_field($note),'decided_at'=>$now,'row_version'=>absint($current['row_version'])+1,'updated_at'=>$now);
		$updated=$wpdb->update(SC_EI_Database::table('workspace_deliverables'),$data,array('id'=>$deliverable_id,'row_version'=>absint($current['row_version']),'status'=>'published'),self::formats($data,array('row_version')),array('%d','%d','%s'));
		if(1!==$updated)return new WP_Error('workspace_deliverable_decision_conflict',__('The deliverable changed before the response was recorded.','sustainable-catalyst-engagement-intake'));
		self::event(absint($current['workspace_id']),$inquiry_id,'deliverable_sender_response','deliverable',$deliverable_id,'published',$status,'sender',0,array('decision'=>$decision,'note'=>sanitize_textarea_field($note)));
		return self::find_row('workspace_deliverables',$deliverable_id);
	}

	public static function add_message( int $workspace_id, string $body, bool $sender_visible, int $actor_user_id, int $deliverable_id = 0 ) {
		global $wpdb; $workspace=self::find($workspace_id); if(!$workspace)return new WP_Error('workspace_missing',__('The client workspace could not be found.','sustainable-catalyst-engagement-intake'));
		$body=sanitize_textarea_field($body); if(''===$body)return new WP_Error('workspace_message_required',__('A collaboration update is required.','sustainable-catalyst-engagement-intake'));
		$data=array('public_id'=>wp_generate_uuid4(),'workspace_id'=>$workspace_id,'inquiry_id'=>absint($workspace['inquiry_id']),'direction'=>'outbound','sender_type'=>'staff','body_text'=>$body,'sender_visible'=>$sender_visible?1:0,'related_deliverable_id'=>$deliverable_id?:null,'created_by'=>$actor_user_id,'created_at'=>current_time('mysql',true));
		if(false===$wpdb->insert(SC_EI_Database::table('workspace_messages'),$data,self::formats($data,array('workspace_id','inquiry_id','sender_visible','related_deliverable_id','created_by'))))return new WP_Error('workspace_message_save_failed',__('The workspace update could not be saved.','sustainable-catalyst-engagement-intake'));
		$id=(int)$wpdb->insert_id; self::event($workspace_id,absint($workspace['inquiry_id']),'workspace_message_created','message',$id,'','recorded','staff',$actor_user_id,array('sender_visible'=>$sender_visible)); return self::find_row('workspace_messages',$id);
	}

	public static function link_document( int $workspace_id, int $attachment_id, array $input, int $actor_user_id ) {
		global $wpdb; $workspace=self::find($workspace_id); $attachment=SC_EI_Attachment_Repository::find($attachment_id);
		if(!$workspace||!$attachment||absint($attachment['inquiry_id'])!==absint($workspace['inquiry_id']))return new WP_Error('workspace_document_invalid',__('Choose a private document from the same inquiry.','sustainable-catalyst-engagement-intake'));
		$data=array('public_id'=>wp_generate_uuid4(),'workspace_id'=>$workspace_id,'inquiry_id'=>absint($workspace['inquiry_id']),'attachment_id'=>$attachment_id,'document_role'=>sanitize_key((string)($input['document_role']??'shared_document')),'title'=>sanitize_text_field((string)($input['title']??$attachment['original_name'])),'version_label'=>sanitize_text_field((string)($input['version_label']??'')),'sender_visible'=>empty($input['sender_visible'])?0:1,'related_deliverable_id'=>absint($input['related_deliverable_id']??0)?:null,'created_by'=>$actor_user_id,'created_at'=>current_time('mysql',true));
		if(false===$wpdb->insert(SC_EI_Database::table('workspace_documents'),$data,self::formats($data,array('workspace_id','inquiry_id','attachment_id','sender_visible','related_deliverable_id','created_by'))))return new WP_Error('workspace_document_link_failed',__('The document could not be linked to the workspace.','sustainable-catalyst-engagement-intake'));
		$id=(int)$wpdb->insert_id; self::event($workspace_id,absint($workspace['inquiry_id']),'workspace_document_linked','document',$id,'','linked','staff',$actor_user_id,array('attachment_id'=>$attachment_id,'sender_visible'=>(bool)$data['sender_visible'])); return self::find_row('workspace_documents',$id);
	}

	public static function milestones( int $workspace_id ): array { global $wpdb; $table=SC_EI_Database::table('workspace_milestones'); return (array)$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE workspace_id=%d ORDER BY sort_order ASC,id ASC",$workspace_id),ARRAY_A); }
	public static function deliverables( int $workspace_id ): array { global $wpdb; $table=SC_EI_Database::table('workspace_deliverables'); return (array)$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE workspace_id=%d ORDER BY id DESC",$workspace_id),ARRAY_A); }
	public static function messages( int $workspace_id ): array { global $wpdb; $table=SC_EI_Database::table('workspace_messages'); return (array)$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE workspace_id=%d ORDER BY created_at DESC,id DESC LIMIT 100",$workspace_id),ARRAY_A); }
	public static function documents( int $workspace_id ): array { global $wpdb; $table=SC_EI_Database::table('workspace_documents'); return (array)$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE workspace_id=%d ORDER BY id DESC",$workspace_id),ARRAY_A); }
	public static function events( int $workspace_id ): array { global $wpdb; $table=SC_EI_Database::table('workspace_events'); return (array)$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE workspace_id=%d ORDER BY created_at DESC,id DESC LIMIT 100",$workspace_id),ARRAY_A); }

	public static function find( int $id ): ?array { return self::find_row('client_workspaces',$id); }
	public static function find_by_public_id( string $public_id ): ?array { global $wpdb; $public_id=sanitize_text_field($public_id); if(''===$public_id)return null; $t=SC_EI_Database::table('client_workspaces'); $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE public_id=%s LIMIT 1",$public_id),ARRAY_A); return $r?:null; }
	public static function for_engagement( int $engagement_id ): ?array { global $wpdb; $t=SC_EI_Database::table('client_workspaces'); $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE engagement_id=%d LIMIT 1",$engagement_id),ARRAY_A); return $r?:null; }
	public static function for_inquiry( int $inquiry_id ): array { global $wpdb; $t=SC_EI_Database::table('client_workspaces'); return (array)$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} WHERE inquiry_id=%d ORDER BY created_at DESC",$inquiry_id),ARRAY_A); }
	public static function all( int $limit=100 ): array { global $wpdb; $t=SC_EI_Database::table('client_workspaces'); return (array)$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} ORDER BY CASE WHEN status IN ('active','paused') THEN 0 ELSE 1 END, updated_at DESC LIMIT %d",max(1,min(500,$limit))),ARRAY_A); }

	public static function sender_snapshot( int $inquiry_id ): array {
		global $wpdb; $workspaces=self::for_inquiry($inquiry_id); $result=array();
		foreach($workspaces as $workspace){ if(empty($workspace['sender_visible'])||!in_array((string)$workspace['status'],array('active','paused','completed'),true))continue;
			$wid=absint($workspace['id']);
			$milestones=(array)$wpdb->get_results($wpdb->prepare("SELECT title,description,status,due_date,completed_at FROM ".SC_EI_Database::table('workspace_milestones')." WHERE workspace_id=%d AND sender_visible=1 ORDER BY sort_order ASC,id ASC",$wid),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$deliverables=(array)$wpdb->get_results($wpdb->prepare("SELECT id,title,description,status,due_date,approval_required,sender_decision,sender_decision_note,decided_at,current_version FROM ".SC_EI_Database::table('workspace_deliverables')." WHERE workspace_id=%d AND sender_visible=1 AND status NOT IN ('draft','in_review','withdrawn') ORDER BY id DESC",$wid),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$documents=(array)$wpdb->get_results($wpdb->prepare("SELECT d.title,d.version_label,d.document_role,a.original_name,a.size_bytes,a.uploaded_at FROM ".SC_EI_Database::table('workspace_documents')." d JOIN ".SC_EI_Database::table('attachments')." a ON a.id=d.attachment_id WHERE d.workspace_id=%d AND d.sender_visible=1 AND a.deleted_at IS NULL ORDER BY d.id DESC",$wid),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$messages=(array)$wpdb->get_results($wpdb->prepare("SELECT body_text,created_at FROM ".SC_EI_Database::table('workspace_messages')." WHERE workspace_id=%d AND sender_visible=1 ORDER BY created_at DESC LIMIT 50",$wid),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result[]=array('public_id'=>$workspace['public_id'],'workspace_number'=>$workspace['workspace_number'],'title'=>$workspace['title'],'status'=>$workspace['status'],'summary'=>$workspace['sender_summary'],'next_step'=>$workspace['sender_next_step'],'milestones'=>$milestones,'deliverables'=>$deliverables,'documents'=>$documents,'messages'=>$messages,'updated_at'=>$workspace['updated_at']);
		}
		return $result;
	}

	public static function metrics(): array {
		global $wpdb; $w=SC_EI_Database::table('client_workspaces'); $m=SC_EI_Database::table('workspace_milestones'); $d=SC_EI_Database::table('workspace_deliverables');
		$today=current_time('Y-m-d',true);
		return array(
			'total'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$w}"), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'active'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$w} WHERE status='active'"), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'overdue_milestones'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$m} WHERE status NOT IN ('completed','canceled') AND due_date IS NOT NULL AND due_date < %s",$today)), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pending_deliverable_decisions'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$d} WHERE sender_visible=1 AND approval_required=1 AND status='published' AND sender_decision='pending'"), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'orphaned'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$w} x LEFT JOIN ".SC_EI_Database::table('engagements')." e ON e.id=x.engagement_id WHERE e.id IS NULL"), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function operational_blockers(): array { $m=self::metrics(); return array_filter(array('orphaned_workspaces'=>absint($m['orphaned']??0),'overdue_milestones'=>absint($m['overdue_milestones']??0)),static fn(int $v):bool=>$v>0); }

	public static function export_for_inquiry( int $inquiry_id ): array {
		global $wpdb;
		$result = array(
			'workspaces'   => self::for_inquiry( $inquiry_id ),
			'members'      => array(),
			'milestones'   => array(),
			'deliverables' => array(),
			'messages'     => array(),
			'documents'    => array(),
			'events'       => array(),
		);
		foreach ( array( 'workspace_members' => 'members', 'workspace_milestones' => 'milestones', 'workspace_deliverables' => 'deliverables', 'workspace_messages' => 'messages', 'workspace_documents' => 'documents', 'workspace_events' => 'events' ) as $table_name => $key ) {
			$result[ $key ] = (array) $wpdb->get_results(
				$wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( $table_name ) . ' WHERE inquiry_id = %d ORDER BY id ASC', $inquiry_id ),
				ARRAY_A
			);
		}
		return $result;
	}

	public static function redact_for_inquiry( int $inquiry_id ): bool {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$ok = true;
		$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'client_workspaces' ) . " SET sender_summary = %s, sender_next_step = %s, sender_visible = 0, updated_at = %s WHERE inquiry_id = %d", '[Workspace summary erased through Privacy and Retention Center.]', '', $now, $inquiry_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'workspace_members' ) . " SET email_hash = '', display_name = %s, role_label = '', permissions_json = '[]', status = 'revoked', revoked_at = %s, updated_at = %s WHERE inquiry_id = %d", '[Workspace member erased]', $now, $now, $inquiry_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'workspace_messages' ) . " SET body_text = %s, sender_visible = 0 WHERE inquiry_id = %d", '[Workspace message erased through Privacy and Retention Center.]', $inquiry_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'workspace_deliverables' ) . " SET sender_decision_note = '', sender_visible = 0, updated_at = %s WHERE inquiry_id = %d", $now, $inquiry_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'workspace_milestones' ) . " SET description = %s, sender_visible = 0, updated_at = %s WHERE inquiry_id = %d", '[Milestone description erased]', $now, $inquiry_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'workspace_documents' ) . " SET title = %s, version_label = '', sender_visible = 0 WHERE inquiry_id = %d", '[Workspace document metadata erased]', $inquiry_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $ok;
	}

	public static function cleanup_for_inquiry( int $inquiry_id ): void {
		global $wpdb; foreach(self::for_inquiry($inquiry_id) as $workspace){$id=absint($workspace['id']); foreach(array('workspace_events','workspace_documents','workspace_messages','workspace_deliverables','workspace_milestones','workspace_members') as $name){$wpdb->delete(SC_EI_Database::table($name),array('workspace_id'=>$id),array('%d'));} $wpdb->delete(SC_EI_Database::table('client_workspaces'),array('id'=>$id),array('%d'));}
	}

	private static function find_row( string $table_name, int $id ): ?array { global $wpdb; $t=SC_EI_Database::table($table_name); $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$id),ARRAY_A); return $r?:null; }
	private static function event( int $workspace_id,int $inquiry_id,string $event_type,string $object_type,int $object_id,string $from_status,string $to_status,string $actor_type,int $actor_id,array $context=array() ): void { global $wpdb; $wpdb->insert(SC_EI_Database::table('workspace_events'),array('public_id'=>wp_generate_uuid4(),'workspace_id'=>$workspace_id,'inquiry_id'=>$inquiry_id,'event_type'=>$event_type,'object_type'=>$object_type,'object_id'=>$object_id,'from_status'=>$from_status,'to_status'=>$to_status,'actor_type'=>$actor_type,'actor_id'=>$actor_id?:null,'context_json'=>wp_json_encode($context,JSON_UNESCAPED_SLASHES),'created_at'=>current_time('mysql',true)),array('%s','%d','%d','%s','%s','%d','%s','%s','%s','%d','%s','%s')); }
	private static function record_failure( string $event_type,int $object_id,array $context=array() ): void { if(class_exists('SC_EI_Hardening_Repository'))SC_EI_Hardening_Repository::record_event('client_workspace',$event_type,'critical','Client workspace persistence or isolation requires review.',array_merge(array('object_id'=>$object_id,'request_id'=>SC_EI_Hardening_Repository::request_id()),$context)); }
	private static function date_or_null( $value ): ?string { $value=sanitize_text_field((string)$value); if(''===$value)return null; $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value,new DateTimeZone('UTC')); return $d&&$d->format('Y-m-d')===$value?$value:null; }
	private static function formats( array $data,array $integer_fields=array() ): array { return array_map(static fn(string $key):string=>in_array($key,$integer_fields,true)?'%d':'%s',array_keys($data)); }
	private static function workspace_integer_fields(): array { return array('inquiry_id','engagement_id','owner_user_id','sender_visible','row_version','created_by'); }
}
