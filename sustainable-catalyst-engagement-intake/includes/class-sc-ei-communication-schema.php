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
			'proposal_handoff'          => __( 'Proposal Handoff', 'sustainable-catalyst-engagement-intake' ),
			'referral'                  => __( 'Referral', 'sustainable-catalyst-engagement-intake' ),
			'decline'                   => __( 'Decline', 'sustainable-catalyst-engagement-intake' ),
			'follow_up'                 => __( 'Follow-up', 'sustainable-catalyst-engagement-intake' ),
			'internal_new_inquiry'      => __( 'Internal New Inquiry Alert', 'sustainable-catalyst-engagement-intake' ),
			'internal_review_due'       => __( 'Internal Review Due Reminder', 'sustainable-catalyst-engagement-intake' ),
			'internal_follow_up_due'    => __( 'Internal Follow-up Due Reminder', 'sustainable-catalyst-engagement-intake' ),
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
			'{site_name}'             => __( 'Website name', 'sustainable-catalyst-engagement-intake' ),
			'{site_url}'              => __( 'Website URL', 'sustainable-catalyst-engagement-intake' ),
			'{sender_name}'           => __( 'Configured sender name', 'sustainable-catalyst-engagement-intake' ),
			'{reply_email}'           => __( 'Configured reply email', 'sustainable-catalyst-engagement-intake' ),
			'{reviewer_name}'         => __( 'Assigned reviewer name', 'sustainable-catalyst-engagement-intake' ),
			'{review_due}'            => __( 'Review due date', 'sustainable-catalyst-engagement-intake' ),
			'{next_follow_up}'        => __( 'Next follow-up date', 'sustainable-catalyst-engagement-intake' ),
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
					"Hello {first_name},\n\nThank you for the information provided. Before I can determine the most appropriate next step, please reply with the following:\n\n• [Question or missing information]\n• [Question or missing information]\n\nPlease do not email confidential documents or credentials. A secure sender portal will be introduced in a later release; for now, use only the approved intake route for private documents.\n\nRegards,\n{sender_name}\n{reply_email}",
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
