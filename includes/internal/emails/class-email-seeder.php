<?php
/**
 * File containing the Email_Seeder class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei\Internal\Emails\Email_Seeder_Data;
use Sensei\Internal\Emails\Email_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email_Seeder class.
 *
 * @internal
 *
 * @since 4.12.0
 */
class Email_Seeder {
	/**
	 * Email_Seeder_Data instance.
	 *
	 * @var Email_Seeder_Data
	 */
	private $email_data;

	/**
	 * Email repository.
	 *
	 * @var Email_Repository
	 */
	private $email_repository;

	/**
	 * Email data.
	 *
	 * @var array
	 */
	private $emails;

	/**
	 * Template_Wizard constructor.
	 *
	 * @internal
	 *
	 * @param Email_Seeder_Data $email_data Email_Seeder_Data instance. Keeps information about all default emails.
	 * @param Email_Repository  $email_repository Email repository.
	 */
	public function __construct( Email_Seeder_Data $email_data, Email_Repository $email_repository ) {
		$this->email_data       = $email_data;
		$this->email_repository = $email_repository;
		$this->emails           = [];
	}

	/**
	 * Initialize the wizard.
	 *
	 * @internal
	 */
	public function init() {
		/**
		 * Filter the email data.
		 *
		 * @since 4.12.0
		 * @hook sensei_email_seeder_data
		 *
		 * @param {array} $emails Email data.
		 *
		 * @return {array} Filtered array of email data.
		 */
		$this->emails = apply_filters( 'sensei_email_seeder_data', $this->email_data->get_email_data() );
	}

	/**
	 * Create email.
	 *
	 * @internal
	 *
	 * @param string $identifier Email identifier.
	 * @param bool   $force      Force creation.
	 *
	 * @return bool
	 */
	public function create_email( string $identifier, bool $force = false ): bool {
		$email_exists = $this->email_repository->has( $identifier );
		if ( $email_exists ) {
			if ( ! $force ) {
				return false;
			}

			$this->email_repository->delete( $identifier );
		}

		$email_data = $this->emails[ $identifier ] ?? [];
		if ( empty( $email_data ) ) {
			return false;
		}

		$types = $email_data['types'] ?? [];
		if ( empty( $types ) ) {
			return false;
		}

		$subject = $email_data['subject'] ?? '';
		if ( empty( $subject ) ) {
			return false;
		}

		$content = $email_data['content'] ?? '';
		if ( empty( $content ) ) {
			return false;
		}

		$description = $email_data['description'] ?? '';

		$is_pro   = $email_data['is_pro'] ?? false;
		$disabled = $email_data['disabled'] ?? false;

		$email_id = $this->email_repository->create( $identifier, $types, $subject, $description, $content, $is_pro, $disabled );

		return is_int( $email_id ) && $email_id > 0;
	}

	/**
	 * Create all emails from templates.
	 *
	 * @internal
	 *
	 * @param bool $force Delete an old email if exists and re-create it with default data.
	 * @return bool
	 */
	public function create_all( $force = false ): bool {
		$result = true;
		foreach ( $this->get_email_identifiers() as $type ) {
			$last_result = $this->create_email( $type, $force );
			if ( ! $last_result ) {
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Get all available email identifiers.
	 *
	 * @return array
	 */
	private function get_email_identifiers(): array {
		return array_keys( $this->emails );
	}
}
