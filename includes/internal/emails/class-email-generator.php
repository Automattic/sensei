<?php
/**
 * File containing the Email_Generator class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Email_Generator
 *
 * @package Sensei\Internal\Emails
 */
class Email_Generator {

	/**
	 * List of individual email generator instances.
	 *
	 * @var Email_Generators_Abstract[]
	 */
	private $email_generators;

	/**
	 * Email repository instance.
	 *
	 * @var Email_Repository
	 */
	private $email_repository;

	/**
	 * Email_Generator constructor.
	 *
	 * @param Email_Repository $email_repository Email repository instance.
	 *
	 * @internal
	 */
	public function __construct( $email_repository ) {
		$this->email_repository = $email_repository;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		$this->email_generators = [
			Student_Starts_Course::IDENTIFIER_NAME    => new Student_Starts_Course( $this->email_repository ),
			Student_Completes_Course::IDENTIFIER_NAME => new Student_Completes_Course( $this->email_repository ),
			Student_Submits_Quiz::IDENTIFIER_NAME     => new Student_Submits_Quiz( $this->email_repository ),
			Course_Completed::IDENTIFIER_NAME         => new Course_Completed( $this->email_repository ),
			New_Course_Assigned::IDENTIFIER_NAME      => new New_Course_Assigned( $this->email_repository ),
			Teacher_Message_Reply::IDENTIFIER_NAME    => new Teacher_Message_Reply( $this->email_repository ),
		];

		add_action( 'init', [ $this, 'init_email_generators' ] );
	}

	/**
	 * Initialize the email generators.
	 *
	 * @access private
	 */
	public function init_email_generators(): void {

		/**
		 * Filter the individual email generators.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_generators
		 *
		 * @param {Email_Generators_Abstract[]} $email_generators The email generators.
		 *
		 * @return {Email_Generators_Abstract[]} The email generators.
		 */
		$email_generators = apply_filters( 'sensei_email_generators', $this->email_generators );

		foreach ( $email_generators as $email_generator ) {
			if ( $email_generator->is_email_active() ) {
				$email_generator->init();
			}
		}
	}

}
