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
 * @since $$next-version$$
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
	 * Email Template repository.
	 *
	 * @var Email_Template_Repository
	 */
	private $template_repository;

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
	public function __construct( Email_Seeder_Data $email_data, Email_Repository $email_repository, Email_Template_Repository $template_repository  ) {
		$this->email_data       = $email_data;
		$this->email_repository = $email_repository;
		$this->template_repository = $template_repository;
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
		 * @since $$next-version$$
		 *
		 * @param array $emails Email data.
		 * @return array Filtered array of email data.
		 */
		$this->emails = apply_filters( 'sensei_emails_seeder_data', $this->email_data->get_email_data() );
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

		$email_id = $this->email_repository->create(
			$identifier,
			$types,
			$subject,
			$description,
			$content,
			$this->template_repository->get_default_template_name(),
		 );

		return is_int( $email_id ) && $email_id > 0;
	}


	/**
	 * Create the email page template.
	 *
	 * @internal
	 *
	 * @param string $identifier Email identifier.
	 * @param bool   $force      Force creation.
	 *
	 * @return bool
	 */
	public function create_template(bool $force = false ): bool {
		if( $force ) {
			 $this->template_repository->delete_all();
		};

		$template_id = $this->template_repository->create();
		return is_int( $template_id ) && $template_id > 0;
	}

	/**
	 * Create all all emails.
	 *
	 * @internal
	 *
	 * @param bool $force Delete old data before create the new one.
	 * @return
	*/
	private function create_all_emails(bool $force): bool {
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
	 * Create all required data to customize emails.
	 *
	 * @internal
	 *
	 * @param bool $force Delete old data before create the new one.
	 * @return bool
	 */
	public function create_all( $force = false ): bool {
		if( ! $this->create_template($force) ) {
			return false ;
		};

		if( ! $this->create_all_emails($force)) {
			return false;
		}

		return true;
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
