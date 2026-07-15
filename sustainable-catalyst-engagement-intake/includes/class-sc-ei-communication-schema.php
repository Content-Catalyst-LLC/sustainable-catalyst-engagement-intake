<?php
/**
 * Communication taxonomies, validation, variables, and timing helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Communication_Schema {

	public static function directions(): array {
		return array(
			'outbound' => __( 'Outbound', 'sustainable-catalyst-engagement-intake' ),
			'inbound'  => __( 'Inbound', 'sustainable-catalyst-engagement-intake' ),
			'internal' => __( 'Internal', 'sustainable-catalyst-engagement-intake' ),
			'system'   => __( 'System Notification', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function channels(): array {
		return array(
			'email'         => __( 'Email', 'sustainable-catalyst-engagement-intake' ),
			'teams_message' => __( 'Microsoft Teams Message', 'sustainable-catalyst-engagement-intake' ),
			'teams_meeting' => __( 'Microsoft Teams Meeting', 'sustainable-catalyst-engagement-intake' ),
			'phone'         => __( 'Phone', 'sustainable-catalyst-engagement-intake' ),
			'video'         => __( 'Video Conversation', 'sustainable-catalyst-engagement-intake' ),
			'in_person'     => __( 'In Person', 'sustainable-catalyst-engagement-intake' ),
			'other'         => __( 'Other', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function types(): array {
		return array(
			'acknowledgment'            => __( 'Submission Acknowledgment', 'sustainable-catalyst-engagement-intake' ),
			'general_response'          => __( 'General Response', 'sustainable-catalyst-engagement-intake' ),
			'request_information'       => __( 'Request More Information', 'sustainable-catalyst-engagement-intake' ),
			'fit_call_invitation'       => __( 'Fit Call Invitation', 'sustainable-catalyst-engagement-intake' ),
			'consultation_invitation'   => __( 'Paid Consultation Invitation', 'sustainable-catalyst-engagement-intake' ),
			'teams_confirmation'        => __( 'Microsoft Teams Confirmation', 'sustainable-catalyst-engagement-intake' ),
			'engagement_update'         => __( 'Engagement Update', 'sustainable-catalyst-engagement-intake' ),
			'support_received'          => __( 'Support Request Received', 'sustainable-catalyst-engagement-intake' ),
			'support_information'       => __( 'Support Information Requested', 'sustainable-catalyst-engagement-intake' ),
			'support_known_issue'       => __( 'Known Issue Identified', 'sustainable-catalyst-engagement-intake' ),
			'support_workaround'        => __( 'Support Workaround', 'sustainable-catalyst-engagement-intake' ),
			'support_fix_planned'       => __( 'Support Fix Planned', 'sustainable-catalyst-engagement-intake' ),
			'support_fix_released'      => __( 'Support Fix Released', 'sustainable-catalyst-engagement-intake' ),
			'support_resolution'        => __( 'Support Resolution Confirmation', 'sustainable-catalyst-engagement-intake' ),
			'support_closed'            => __( 'Support Case Closed', 'sustainable-catalyst-engagement-intake' ),
			'proposal_ready'            => __( 'Proposal Ready for Review', 'sustainable-catalyst-engagement-intake' ),
			'proposal_sent'             => __( 'Proposal Sent', 'sustainable-catalyst-engagement-intake' ),
			'proposal_expiration'       => __( 'Proposal Expiration Reminder', 'sustainable-catalyst-engagement-intake' ),
			'proposal_changes'          => __( 'Proposal Changes Requested', 'sustainable-catalyst-engagement-intake' ),
			'proposal_revised'          => __( 'Revised Proposal Available', 'sustainable-catalyst-engagement-intake' ),
			'proposal_accepted'         => __( 'Proposal Accepted', 'sustainable-catalyst-engagement-intake' ),
			'proposal_declined'         => __( 'Proposal Declined', 'sustainable-catalyst-engagement-intake' ),
			'proposal_expired'          => __( 'Proposal Expired', 'sustainable-catalyst-engagement-intake' ),
			'sow_ready'                 => __( 'Statement of Work Ready', 'sustainable-catalyst-engagement-intake' ),
			'engagement_activated'      => __( 'Engagement Activated', 'sustainable-catalyst-engagement-intake' ),
			'workspace_activated'       => __( 'Client Workspace Activated', 'sustainable-catalyst-engagement-intake' ),
			'workspace_update'          => __( 'Client Workspace Update', 'sustainable-catalyst-engagement-intake' ),
			'workspace_deliverable'     => __( 'Workspace Deliverable Available', 'sustainable-catalyst-engagement-intake' ),
			'workspace_changes'         => __( 'Workspace Deliverable Changes Requested', 'sustainable-catalyst-engagement-intake' ),
			'workspace_accepted'        => __( 'Workspace Deliverable Accepted', 'sustainable-catalyst-engagement-intake' ),
			'invoice_issued'            => __( 'Invoice Issued', 'sustainable-catalyst-engagement-intake' ),
			'payment_reminder'          => __( 'Payment Reminder', 'sustainable-catalyst-engagement-intake' ),
			'payment_received'          => __( 'Payment Received', 'sustainable-catalyst-engagement-intake' ),
			'invoice_voided'            => __( 'Invoice Voided', 'sustainable-catalyst-engagement-intake' ),
			'proposal_handoff'          => __( 'Proposal Handoff', 'sustainable-catalyst-engagement-intake' ),
			'referral'                  => __( 'Referral', 'sustainable-catalyst-engagement-intake' ),
			'decline'                   => __( 'Decline', 'sustainable-catalyst-engagement-intake' ),
			'follow_up'                 => __( 'Follow-up', 'sustainable-catalyst-engagement-intake' ),
			'internal_new_inquiry'      => __( 'Internal New Inquiry Alert', 'sustainable-catalyst-engagement-intake' ),
			'internal_review_due'       => __( 'Internal Review Due Reminder', 'sustainable-catalyst-engagement-intake' ),
			'internal_follow_up_due'    => __( 'Internal Follow-up Due Reminder', 'sustainable-catalyst-engagement-intake' ),
			'internal_lifecycle_task_due'=> __( 'Internal Lifecycle Task Due Reminder', 'sustainable-catalyst-engagement-intake' ),
			'internal_escalation'       => __( 'Internal Escalation Alert', 'sustainable-catalyst-engagement-intake' ),
			'internal_note'             => __( 'Internal Note', 'sustainable-catalyst-engagement-intake' ),
			'manual_interaction'        => __( 'Manually Recorded Interaction', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function statuses(): array {
		return array(
			'draft'      => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'approved'   => __( 'Approved to Send', 'sustainable-catalyst-engagement-intake' ),
			'sending'    => __( 'Sending', 'sustainable-catalyst-engagement-intake' ),
			'accepted'   => __( 'Accepted by Mail Transport', 'sustainable-catalyst-engagement-intake' ),
			'failed'     => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'   => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
			'received'   => __( 'Received / Recorded', 'sustainable-catalyst-engagement-intake' ),
			'recorded'   => __( 'Recorded Outside System', 'sustainable-catalyst-engagement-intake' ),
			'suppressed' => __( 'Suppressed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function communication_states(): array {
		return array(
			'open'                => __( 'Open', 'sustainable-catalyst-engagement-intake' ),
			'waiting_on_sender'   => __( 'Waiting on Sender', 'sustainable-catalyst-engagement-intake' ),
			'waiting_on_internal' => __( 'Waiting on Internal Team', 'sustainable-catalyst-engagement-intake' ),
			'follow_up_due'       => __( 'Follow-up Due', 'sustainable-catalyst-engagement-intake' ),
			'paused'              => __( 'Paused', 'sustainable-catalyst-engagement-intake' ),
			'closed'              => __( 'Communication Closed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function privacy_levels(): array {
		return array(
			'private'      => __( 'Private', 'sustainable-catalyst-engagement-intake' ),
			'confidential' => __( 'Confidential', 'sustainable-catalyst-engagement-intake' ),
			'restricted'   => __( 'Restricted', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function template_variables(): array {
		return array(
			'{contact_name}'          => __( 'Full contact name', 'sustainable-catalyst-engagement-intake' ),
			'{first_name}'            => __( 'First name', 'sustainable-catalyst-engagement-intake' ),
			'{reference}'             => __( 'Inquiry reference', 'sustainable-catalyst-engagement-intake' ),
			'{organization}'          => __( 'Organization', 'sustainable-catalyst-engagement-intake' ),
			'{subject}'               => __( 'Inquiry subject', 'sustainable-catalyst-engagement-intake' ),
			'{inquiry_type}'          => __( 'Inquiry type', 'sustainable-catalyst-engagement-intake' ),
			'{service_interest}'      => __( 'Service interest', 'sustainable-catalyst-engagement-intake' ),
			'{review_stage}'          => __( 'Review stage', 'sustainable-catalyst-engagement-intake' ),
			'{fit_decision}'          => __( 'Human fit decision', 'sustainable-catalyst-engagement-intake' ),
			'{recommended_next_step}' => __( 'Human recommended next step', 'sustainable-catalyst-engagement-intake' ),
			'{teams_duration}'        => __( 'Preferred Teams duration', 'sustainable-catalyst-engagement-intake' ),
			'{teams_meeting_url}'     => __( 'Approved Teams meeting URL', 'sustainable-catalyst-engagement-intake' ),
			'{scheduled_start}'       => __( 'Scheduled start in sender timezone', 'sustainable-catalyst-engagement-intake' ),
			'{scheduled_timezone}'    => __( 'Scheduled timezone', 'sustainable-catalyst-engagement-intake' ),
			'{meeting_type}'          => __( 'Approved meeting type', 'sustainable-catalyst-engagement-intake' ),
			'{meeting_agenda}'        => __( 'Approved meeting agenda', 'sustainable-catalyst-engagement-intake' ),
			'{meeting_preparation}'   => __( 'Approved meeting preparation requests', 'sustainable-catalyst-engagement-intake' ),
			'{meeting_next_step}'     => __( 'Approved meeting next step', 'sustainable-catalyst-engagement-intake' ),
			'{site_name}'             => __( 'Website name', 'sustainable-catalyst-engagement-intake' ),
			'{site_url}'              => __( 'Website URL', 'sustainable-catalyst-engagement-intake' ),
			'{sender_name}'           => __( 'Configured sender name', 'sustainable-catalyst-engagement-intake' ),
			'{reply_email}'           => __( 'Configured reply email', 'sustainable-catalyst-engagement-intake' ),
			'{reviewer_name}'         => __( 'Assigned reviewer name', 'sustainable-catalyst-engagement-intake' ),
			'{review_due}'            => __( 'Review due date', 'sustainable-catalyst-engagement-intake' ),
			'{next_follow_up}'        => __( 'Next follow-up date', 'sustainable-catalyst-engagement-intake' ),
			'{lifecycle_stage}'       => __( 'Advisory lifecycle stage', 'sustainable-catalyst-engagement-intake' ),
			'{lifecycle_next_action}' => __( 'Published or internal lifecycle next action', 'sustainable-catalyst-engagement-intake' ),
			'{lifecycle_task}'        => __( 'Lifecycle task title', 'sustainable-catalyst-engagement-intake' ),
			'{lifecycle_task_due}'    => __( 'Lifecycle task due date', 'sustainable-catalyst-engagement-intake' ),
			'{support_case_number}'   => __( 'Product support case number', 'sustainable-catalyst-engagement-intake' ),
			'{support_product}'       => __( 'Support product', 'sustainable-catalyst-engagement-intake' ),
			'{support_version}'       => __( 'Support product version', 'sustainable-catalyst-engagement-intake' ),
			'{support_component}'     => __( 'Support component', 'sustainable-catalyst-engagement-intake' ),
			'{support_status}'        => __( 'Sender-safe support status', 'sustainable-catalyst-engagement-intake' ),
			'{support_next_step}'     => __( 'Approved support next step', 'sustainable-catalyst-engagement-intake' ),
			'{proposal_number}'       => __( 'Proposal number', 'sustainable-catalyst-engagement-intake' ),
			'{proposal_version}'      => __( 'Current approved proposal version', 'sustainable-catalyst-engagement-intake' ),
			'{proposal_title}'        => __( 'Proposal title', 'sustainable-catalyst-engagement-intake' ),
			'{proposal_expires}'      => __( 'Proposal expiration date', 'sustainable-catalyst-engagement-intake' ),
			'{sow_number}'            => __( 'Statement of Work number', 'sustainable-catalyst-engagement-intake' ),
			'{sow_version}'           => __( 'Current approved Statement of Work version', 'sustainable-catalyst-engagement-intake' ),
			'{change_request_number}'=> __( 'Change request number', 'sustainable-catalyst-engagement-intake' ),
			'{engagement_number}'     => __( 'Engagement number', 'sustainable-catalyst-engagement-intake' ),
			'{workspace_number}'      => __( 'Client workspace number', 'sustainable-catalyst-engagement-intake' ),
			'{workspace_title}'       => __( 'Client workspace title', 'sustainable-catalyst-engagement-intake' ),
			'{workspace_status}'      => __( 'Client workspace status', 'sustainable-catalyst-engagement-intake' ),
			'{workspace_next_step}'   => __( 'Approved workspace next step', 'sustainable-catalyst-engagement-intake' ),
			'{invoice_number}'        => __( 'Invoice number', 'sustainable-catalyst-engagement-intake' ),
			'{invoice_status}'        => __( 'Invoice status', 'sustainable-catalyst-engagement-intake' ),
			'{invoice_total}'         => __( 'Invoice total', 'sustainable-catalyst-engagement-intake' ),
			'{invoice_balance}'       => __( 'Invoice balance due', 'sustainable-catalyst-engagement-intake' ),
			'{invoice_due}'           => __( 'Invoice due date', 'sustainable-catalyst-engagement-intake' ),
			'{payment_provider}'      => __( 'External payment provider', 'sustainable-catalyst-engagement-intake' ),
			'{payment_url}'           => __( 'Approved external payment URL', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_templates(): array {
		return array(
			'acknowledgment' => array(
				'name'               => __( 'Submission acknowledgment', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'acknowledgment',
				'subject'            => __( 'We received your Sustainable Catalyst inquiry — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nThank you for contacting Sustainable Catalyst. Your inquiry has been received under reference {reference}.\n\nThe request will be reviewed before any meeting, consultation, proposal, or engagement is confirmed. Please keep this reference for future communication.\n\nNo documents will be returned as email attachments. Private files remain in the protected intake system.\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 1,
			),
			'general_response' => array(
				'name'               => __( 'General response', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'general_response',
				'subject'            => __( 'Re: {subject} — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nThank you for your inquiry. I have reviewed the information submitted under reference {reference}.\n\n[Write the response here.]\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 0,
			),
			'request_information' => array(
				'name'               => __( 'Request more information', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'request_information',
				'subject'            => __( 'Additional information requested — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nThank you for the information provided. Before I can determine the most appropriate next step, please reply with the following:\n\n• [Question or missing information]\n• [Question or missing information]\n\nPlease do not email confidential documents or credentials. Use the approved secure sender portal for private follow-up documents when portal access has been issued. Do not email confidential documents or credentials.\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 0,
			),
			'fit_call_invitation' => array(
				'name'               => __( 'Microsoft Teams fit-call invitation', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'fit_call_invitation',
				'subject'            => __( 'Microsoft Teams fit call — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nBased on the initial review, a short Microsoft Teams fit call is the recommended next step. The proposed duration is {teams_duration} minutes.\n\n[Provide availability instructions or confirmed meeting details.]\n\nA meeting is not confirmed until a date, time, timezone, and Teams link are explicitly provided.\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 0,
			),
			'teams_confirmation' => array(
				'name'               => __( 'Microsoft Teams confirmation', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'teams_confirmation',
				'subject'            => __( 'Microsoft Teams meeting confirmed — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nYour Microsoft Teams meeting is confirmed.\n\nDate and time: {scheduled_start}\nTimezone: {scheduled_timezone}\nTeams link: {teams_meeting_url}\n\nPlease reply if accessibility support or a scheduling adjustment is needed.\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 0,
			),
			'teams_rescheduled' => array(
				'name'               => __( 'Microsoft Teams rescheduled', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'teams_confirmation',
				'subject'            => __( 'Microsoft Teams meeting rescheduled — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __( "Hello {first_name},\n\nThe Microsoft Teams meeting has been rescheduled.\n\nDate and time: {scheduled_start}\nTimezone: {scheduled_timezone}\nTeams link: {teams_meeting_url}\nAgenda: {meeting_agenda}\nPreparation: {meeting_preparation}\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system'          => 0,
			),
			'teams_canceled' => array(
				'name'               => __( 'Microsoft Teams canceled', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'teams_confirmation',
				'subject'            => __( 'Microsoft Teams meeting canceled — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __( "Hello {first_name},\n\nThe Microsoft Teams meeting associated with {reference} has been canceled. The previous join link is no longer active.\n\nNext step: {meeting_next_step}\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system'          => 0,
			),
			'teams_reminder' => array(
				'name'               => __( 'Microsoft Teams reminder', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'teams_confirmation',
				'subject'            => __( 'Microsoft Teams meeting reminder — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __( "Hello {first_name},\n\nThis is a reviewed reminder for your upcoming {meeting_type}.\n\nDate and time: {scheduled_start}\nTimezone: {scheduled_timezone}\nTeams link: {teams_meeting_url}\nAgenda: {meeting_agenda}\nPreparation: {meeting_preparation}\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system'          => 0,
			),
			'teams_followup' => array(
				'name'               => __( 'Microsoft Teams follow-up', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'follow_up',
				'subject'            => __( 'Follow-up after our Microsoft Teams meeting — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __( "Hello {first_name},\n\nThank you for the Microsoft Teams conversation regarding {reference}.\n\n[Approved meeting summary]\n\nNext step: {meeting_next_step}\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system'          => 0,
			),
			'consultation_invitation' => array(
				'name'               => __( 'Paid consultation invitation', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'consultation_invitation',
				'subject'            => __( 'Consultation next step — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nFollowing review of your inquiry, the recommended next step is a paid consultation focused on [scope].\n\n[Describe the consultation, price, preparation, and scheduling process.]\n\nNo work is authorized until scope, terms, and payment arrangements are separately confirmed.\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 0,
			),
			'referral' => array(
				'name'               => __( 'Referral response', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'referral',
				'subject'            => __( 'Referral guidance — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nThank you for the inquiry. Based on the review, Sustainable Catalyst is not the best direct fit for this request, but the following referral path may be useful:\n\n[Referral guidance]\n\nThis referral is informational and does not represent an endorsement, guarantee, or transfer of confidential information.\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 0,
			),
			'decline' => array(
				'name'               => __( 'Decline response', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'decline',
				'subject'            => __( 'Regarding your inquiry — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Hello {first_name},\n\nThank you for considering Sustainable Catalyst. After review, I am not able to move forward with this request.\n\n[Optional concise reason or boundary.]\n\nNo judgment is implied about the importance of the work; this decision reflects fit, scope, timing, capacity, or responsible-use boundaries.\n\nRegards,\n{sender_name}\n{reply_email}",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 0,
			),
			'lifecycle_information_request' => array(
				'name' => __( 'Lifecycle information request', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'request_information',
				'subject' => __( 'Information needed for the next step — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nYour inquiry is currently at: {lifecycle_stage}. To continue, please provide the information described below.\n\n[Requested information]\n\nUse the Secure Sender Portal for private documents.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'lifecycle_meeting_invitation' => array(
				'name' => __( 'Lifecycle Teams meeting invitation', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'fit_call_invitation',
				'subject' => __( 'Microsoft Teams conversation requested — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nA Microsoft Teams conversation is the recommended next step for {reference}.\n\n{lifecycle_next_action}\n\nNo meeting is confirmed until a human-approved date, time, timezone, and Teams link are provided.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'lifecycle_proposal_sent' => array(
				'name' => __( 'Lifecycle proposal notice', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'proposal_handoff',
				'subject' => __( 'Proposal available — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nA proposal record is available for inquiry {reference}. Review the approved proposal notice and documents through the Secure Sender Portal.\n\nA portal response is not an electronic signature and does not authorize work by itself.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'proposal_ready' => array(
				'name' => __( 'Proposal ready for review', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'proposal_ready',
				'subject' => __( 'Proposal ready for review — {proposal_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nProposal {proposal_number}, version {proposal_version}, is ready for review through the Secure Sender Portal.\n\nTitle: {proposal_title}\nExpiration: {proposal_expires}\n\nPlease review only the current approved version. No work is authorized until the separate approval and contracting requirements are completed.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'proposal_changes' => array(
				'name' => __( 'Proposal changes requested', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'proposal_changes',
				'subject' => __( 'Changes requested — {proposal_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nChanges were requested for proposal {proposal_number}.\n\n[Approved summary of requested changes]\n\nA revised version will be published separately after human review.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'proposal_revised' => array(
				'name' => __( 'Revised proposal available', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'proposal_revised',
				'subject' => __( 'Revised proposal available — {proposal_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nA revised version of proposal {proposal_number} is available. Current version: {proposal_version}.\n\nOnly the current approved version may be accepted. Earlier versions remain part of the audit history but are no longer actionable.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'proposal_accepted' => array(
				'name' => __( 'Proposal accepted', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'proposal_accepted',
				'subject' => __( 'Proposal acceptance recorded — {proposal_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nYour acceptance of proposal {proposal_number}, version {proposal_version}, has been recorded.\n\nThis portal action is an auditable approval record but is not represented as an electronic signature or automatic authorization to begin work. Any required external contract and approved Statement of Work remain separate controls.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'proposal_declined' => array(
				'name' => __( 'Proposal declined', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'proposal_declined',
				'subject' => __( 'Proposal decision recorded — {proposal_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nThe decision to decline proposal {proposal_number} has been recorded.\n\nThank you for reviewing the proposal.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'proposal_expiration' => array(
				'name' => __( 'Proposal expiration reminder', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'proposal_expiration',
				'subject' => __( 'Proposal review reminder — {proposal_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nProposal {proposal_number} is scheduled to expire on {proposal_expires}.\n\nPlease review the current approved version through the Secure Sender Portal. This reminder is sent only after human review.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'sow_ready' => array(
				'name' => __( 'Statement of Work ready', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'sow_ready',
				'subject' => __( 'Statement of Work ready — {sow_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nStatement of Work {sow_number}, version {sow_version}, is ready for review.\n\nReview the scope, deliverables, milestones, responsibilities, acceptance criteria, change-control process, and approved attachments through the Secure Sender Portal.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'engagement_activated' => array(
				'name' => __( 'Engagement activated', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'engagement_activated',
				'subject' => __( 'Engagement activated — {engagement_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nEngagement {engagement_number} has been activated after the required proposal, Statement of Work, and external contracting controls were completed.\n\n[Approved onboarding summary and next step]\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'lifecycle_engagement_accepted' => array(
				'name' => __( 'Lifecycle engagement accepted', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'engagement_update',
				'subject' => __( 'Engagement next steps — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nThe inquiry has moved to the accepted stage.\n\nNext action: {lifecycle_next_action}\n\nScope, payment, contracting, and authorization remain governed by the separately approved engagement documents.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'lifecycle_declined' => array(
				'name' => __( 'Lifecycle declined', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'decline',
				'subject' => __( 'Regarding inquiry {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nAfter human review, Sustainable Catalyst will not advance this inquiry.\n\n[Optional concise explanation or referral.]\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'lifecycle_completed' => array(
				'name' => __( 'Lifecycle engagement completed', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'engagement_update',
				'subject' => __( 'Engagement completed — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nThe engagement associated with {reference} has been marked completed.\n\n[Completion summary and any approved follow-up path.]\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'workspace_activated' => array(
				'name' => __( 'Client workspace activated', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'workspace_activated',
				'subject' => __( 'Secure client workspace available — {workspace_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},

A secure client workspace is now available for engagement {engagement_number}.

Workspace: {workspace_number}
Title: {workspace_title}
Status: {workspace_status}
Next step: {workspace_next_step}

Use the Secure Sender Portal for approved milestones, deliverables, documents, and collaboration updates. Do not email confidential documents or credentials.

Regards,
{sender_name}
{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'workspace_update' => array(
				'name' => __( 'Client workspace update', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'workspace_update',
				'subject' => __( 'Workspace update — {workspace_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},

A reviewed update is available in your secure client workspace.

Workspace: {workspace_number}
Title: {workspace_title}
Next step: {workspace_next_step}

Open the Secure Sender Portal to review the approved update.

Regards,
{sender_name}
{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'workspace_deliverable' => array(
				'name' => __( 'Workspace deliverable available', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'workspace_deliverable',
				'subject' => __( 'Deliverable available — {workspace_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},

A reviewed deliverable is available in your secure client workspace.

Workspace: {workspace_number}
Title: {workspace_title}

Open the Secure Sender Portal to review the current version and record an acceptance or change request when a decision is requested.

Regards,
{sender_name}
{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'workspace_changes' => array(
				'name' => __( 'Workspace deliverable changes requested', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'workspace_changes',
				'subject' => __( 'Deliverable changes requested — {workspace_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "A sender requested changes to a deliverable in workspace {workspace_number}.

Open the private Client Workspace administration screen to review the sender note and determine the next action. This internal notice does not authorize a revision automatically.", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 1,
			),
			'workspace_accepted' => array(
				'name' => __( 'Workspace deliverable accepted', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'workspace_accepted',
				'subject' => __( 'Deliverable accepted — {workspace_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "A sender accepted a deliverable in workspace {workspace_number}.

Open the private Client Workspace administration screen to verify the recorded decision and determine the next milestone or closeout action.", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 1,
			),
			'support_received' => array(
				'name' => __( 'Support request received', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_received',
				'subject' => __( 'Support request received — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nYour product support request has been received.\n\nCase: {support_case_number}\nProduct: {support_product} {support_version}\nComponent: {support_component}\n\nThe case will be reviewed before a workaround, fix, or release commitment is made.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'support_information' => array(
				'name' => __( 'Support information requested', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_information',
				'subject' => __( 'Additional support information requested — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nAdditional information is needed to continue investigating case {support_case_number}.\n\n[Requested diagnostic information]\n\nUse the Secure Sender Portal for private files. Never send passwords, API keys, or access tokens.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'support_known_issue' => array(
				'name' => __( 'Known issue identified', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_known_issue',
				'subject' => __( 'Known issue identified — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nCase {support_case_number} has been associated with a known issue affecting {support_product}.\n\n[Approved known-issue reference and current guidance]\n\nNo release date is committed unless it is explicitly stated in an approved release notice.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'support_workaround' => array(
				'name' => __( 'Support workaround available', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_workaround',
				'subject' => __( 'Workaround available — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nAn approved workaround is available for case {support_case_number}.\n\n[Workaround and verification steps]\n\nPlease confirm through the Secure Sender Portal whether this resolves the issue.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'support_fix_planned' => array(
				'name' => __( 'Support fix planned', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_fix_planned',
				'subject' => __( 'Fix planned — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nA fix has been planned for the issue tracked under case {support_case_number}.\n\n[Approved release or roadmap context]\n\nPlanning status is not a guarantee of timing until an official release is published.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'support_fix_released' => array(
				'name' => __( 'Support fix released', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_fix_released',
				'subject' => __( 'Fix released — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nA release addressing case {support_case_number} is now available.\n\n[Release, upgrade, and verification guidance]\n\nCreate a backup before updating and confirm the result through the Secure Sender Portal.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'support_resolution' => array(
				'name' => __( 'Support resolution confirmation', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_resolution',
				'subject' => __( 'Please confirm the resolution — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nA resolution has been recorded for case {support_case_number}.\n\n[Resolution and verification steps]\n\nPlease confirm whether the issue is resolved.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'support_closed' => array(
				'name' => __( 'Support case closed', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'support_closed',
				'subject' => __( 'Support case closed — {support_case_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nSupport case {support_case_number} has been closed.\n\n[Approved closure summary]\n\nA new request can be submitted if a separate issue appears.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),

			'invoice_issued' => array(
				'name' => __( 'Invoice issued', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'invoice_issued',
				'subject' => __( 'Invoice {invoice_number} is available — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nInvoice {invoice_number} is available in the Secure Sender Portal.\n\nTotal: {invoice_total}\nBalance due: {invoice_balance}\nDue: {invoice_due}\n\nExternal payment provider: {payment_provider}\nApproved payment link: {payment_url}\n\nSustainable Catalyst does not collect or store card or bank-account details in the engagement platform.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'payment_reminder' => array(
				'name' => __( 'Payment reminder', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'payment_reminder',
				'subject' => __( 'Payment reminder — invoice {invoice_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nThis is a reviewed reminder regarding invoice {invoice_number}.\n\nBalance due: {invoice_balance}\nDue: {invoice_due}\nStatus: {invoice_status}\n\nUse only the approved external payment link shown in the Secure Sender Portal. Never send payment credentials by email.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'payment_received' => array(
				'name' => __( 'Payment received', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'payment_received',
				'subject' => __( 'Payment recorded — invoice {invoice_number}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nAn external payment status has been recorded for invoice {invoice_number}.\n\nCurrent status: {invoice_status}\nBalance due: {invoice_balance}\n\nThis operational notice is not a bank receipt or tax document.\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),
			'invoice_voided' => array(
				'name' => __( 'Invoice voided', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'invoice_voided',
				'subject' => __( 'Invoice {invoice_number} voided — {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "Hello {first_name},\n\nInvoice {invoice_number} has been voided and is no longer payable. Any prior payment link associated with it should not be used.\n\n[Approved replacement or next-step guidance]\n\nRegards,\n{sender_name}\n{reply_email}", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 0,
			),

			'internal_lifecycle_task_due' => array(
				'name' => __( 'Internal lifecycle task due', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'internal_lifecycle_task_due',
				'subject' => __( 'Lifecycle task due: {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body' => __( "A private advisory-lifecycle task is due.\n\nReference: {reference}\nStage: {lifecycle_stage}\nTask: {lifecycle_task}\nDue: {lifecycle_task_due}\nNext action: {lifecycle_next_action}\n\nOpen the Advisory Lifecycle workspace. This reminder does not contact the sender.", 'sustainable-catalyst-engagement-intake' ),
				'is_system' => 1,
			),
			'internal_new_inquiry' => array(
				'name'               => __( 'Internal new inquiry alert', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'internal_new_inquiry',
				'subject'            => __( 'New inquiry {reference}: {subject}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"A new private inquiry has been received.\n\nReference: {reference}\nContact: {contact_name}\nOrganization: {organization}\nSubject: {subject}\nService interest: {service_interest}\nReview due: {review_due}\n\nOpen the Engagement Intake Review Workspace in WordPress. Do not forward private intake content outside approved systems.",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 1,
			),
			'internal_review_due' => array(
				'name'               => __( 'Internal review due reminder', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'internal_review_due',
				'subject'            => __( 'Review due: {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Administrative review attention is required.\n\nReference: {reference}\nSubject: {subject}\nAssigned reviewer: {reviewer_name}\nReview stage: {review_stage}\nReview due: {review_due}\n\nOpen the private Review Workspace. This reminder does not contact the sender.",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 1,
			),
			'internal_follow_up_due' => array(
				'name'               => __( 'Internal follow-up reminder', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'internal_follow_up_due',
				'subject'            => __( 'Follow-up due: {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"Communication follow-up is due.\n\nReference: {reference}\nContact: {contact_name}\nSubject: {subject}\nNext follow-up: {next_follow_up}\n\nReview the communication history before sending or recording another interaction.",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 1,
			),
			'internal_escalation' => array(
				'name'               => __( 'Internal escalation alert', 'sustainable-catalyst-engagement-intake' ),
				'communication_type' => 'internal_escalation',
				'subject'            => __( 'Escalated inquiry: {reference}', 'sustainable-catalyst-engagement-intake' ),
				'body'               => __(
					"An administrative review has been escalated.\n\nReference: {reference}\nSubject: {subject}\nReviewer: {reviewer_name}\nReview stage: {review_stage}\nHuman fit decision: {fit_decision}\nRecommended next step: {recommended_next_step}\n\nOpen the private Review Workspace for the recorded rationale and escalation details.",
					'sustainable-catalyst-engagement-intake'
				),
				'is_system'          => 1,
			),
		);
	}

	public static function sanitize_choice( string $value, array $options, string $fallback ): string {
		$value = sanitize_key( $value );
		return array_key_exists( $value, $options ) ? $value : $fallback;
	}

	public static function sanitize_emails( $value, int $maximum = 10 ): array {
		$items = is_array( $value ) ? $value : preg_split( '/[\s,;]+/', (string) $value );
		$emails = array();
		foreach ( (array) $items as $item ) {
			$email = sanitize_email( trim( (string) $item ) );
			if ( $email && is_email( $email ) ) {
				$emails[ strtolower( $email ) ] = $email;
			}
			if ( count( $emails ) >= max( 1, min( 25, $maximum ) ) ) {
				break;
			}
		}
		return array_values( $emails );
	}

	public static function sanitize_subject( string $subject ): string {
		$subject = preg_replace( '/[\r\n]+/', ' ', $subject );
		$subject = sanitize_text_field( $subject );
		return function_exists( 'mb_substr' ) ? mb_substr( $subject, 0, 255 ) : substr( $subject, 0, 255 );
	}

	public static function sanitize_body( string $body ): string {
		$body = sanitize_textarea_field( $body );
		return function_exists( 'mb_substr' ) ? mb_substr( $body, 0, 50000 ) : substr( $body, 0, 50000 );
	}

	public static function thread_key( array $inquiry ): string {
		return 'sc-ei-' . strtolower( sanitize_key( (string) ( $inquiry['reference'] ?? '' ) ) );
	}

	public static function label( array $options, string $value ): string {
		return $options[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}
}
