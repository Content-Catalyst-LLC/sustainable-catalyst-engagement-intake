<?php
/** Execute v1.4.1 approval-integrity and action-mapping helpers without WordPress. */
define( 'ABSPATH', __DIR__ );
function __( $value, $domain = '' ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function sanitize_textarea_field( $value ) { return trim( (string) $value ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function absint( $value ) { return abs( (int) $value ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_Error {
    private string $code; private string $message;
    public function __construct( string $code, string $message = '' ) { $this->code = $code; $this->message = $message; }
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}
require dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-proposal-governance-schema.php';
require dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-proposal-governance-repository.php';

$sender = array(
    'schema' => SC_EI_Proposal_Governance_Schema::APPROVAL_SCHEMA,
    'inquiry_id' => 12,
    'proposal_id' => 34,
    'proposal_version_id' => 56,
    'sow_id' => null,
    'action' => 'proposal_accepted',
    'actor_type' => 'sender',
    'actor_id' => 78,
    'note' => 'Approved for validation.',
    'authority_attested' => 1,
    'boundary_acknowledged' => 1,
    'confirmation_hash' => hash( 'sha256', 'ACCEPT PROP-TEST' ),
    'created_at' => '2026-07-15 18:00:00',
);
$sender['immutable_hash'] = SC_EI_Proposal_Governance_Schema::canonical_hash( $sender );
$staff = array(
    'public_id' => '00000000-0000-4000-8000-000000000001',
    'schema' => SC_EI_Proposal_Governance_Schema::APPROVAL_SCHEMA,
    'inquiry_id' => 12,
    'proposal_id' => 34,
    'proposal_version_id' => 56,
    'sow_id' => 90,
    'action' => 'engagement_converted',
    'actor_type' => 'staff',
    'actor_id' => 1,
    'note' => 'Converted after external contract evidence.',
    'authority_attested' => 1,
    'boundary_acknowledged' => 1,
    'confirmation_hash' => '',
    'created_at' => '2026-07-15 18:05:00',
);
$staff['immutable_hash'] = SC_EI_Proposal_Governance_Schema::canonical_hash( $staff );
$mutated = $sender;
$mutated['note'] = 'Changed after receipt creation.';
$checks = array(
    'sender receipt integrity verifies' => SC_EI_Proposal_Governance_Repository::verify_approval_integrity( $sender ),
    'staff receipt integrity verifies' => SC_EI_Proposal_Governance_Repository::verify_approval_integrity( $staff ),
    'mutated receipt is rejected' => ! SC_EI_Proposal_Governance_Repository::verify_approval_integrity( $mutated ),
    'accept maps to proposal accepted' => 'proposal_accepted' === SC_EI_Proposal_Governance_Repository::action_for_workflow_response( 'accept' ),
    'request changes maps consistently' => 'changes_requested' === SC_EI_Proposal_Governance_Repository::action_for_workflow_response( 'request_changes' ),
    'unknown response has no receipt action' => '' === SC_EI_Proposal_Governance_Repository::action_for_workflow_response( 'unknown' ),
);
$failed = array_keys( array_filter( $checks, static fn( $passed ) => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Proposal reliability runtime checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
echo 'Sustainable Catalyst v1.4.1 proposal reliability runtime checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
