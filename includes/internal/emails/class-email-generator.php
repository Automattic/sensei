<?php
/**
 * File containing the Email_Generator class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei\Internal\Emails\Generators\Course_Completed;
use Sensei\Internal\Emails\Generators\Course_Created;
use Sensei\Internal\Emails\Generators\Course_Welcome;
use Sensei\Internal\Emails\Generators\New_Course_Assigned;
use Sensei\Internal\Emails\Generators\Quiz_Graded;
use Sensei\Internal\Emails\Generators\Student_Completes_Course;
use Sensei\Internal\Emails\Generators\Student_Completes_Lesson;
use Sensei\Internal\Emails\Generators\Student_Starts_Course;
use Sensei\Internal\Emails\Generators\Student_Submits_Quiz;
use Sensei\Internal\Emails\Generators\Teacher_Message_Reply;
use Sensei\Internal\Emails\Generators\Student_Message_Reply;
use Sensei\Internal\Emails\Generators\Student_Sends_Message;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;

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
	 * Lesson progress repository.
	 *
	 * @var Lesson_Progress_Repository_Interface
	 */
	private $lesson_progress_repository;

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
	 * @internal
	 *
	 * @param Email_Repository                     $email_repository Email repository instance.
	 * @param Lesson_Progress_Repository_Interface $lesson_progress_repository Lesson progress repository.
	 */
	public function __construct( Email_Repository $email_repository, Lesson_Progress_Repository_Interface $lesson_progress_repository ) {
		$this->email_repository           = $email_repository;
		$this->lesson_progress_repository = $lesson_progress_repository;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		$this->email_generators = [
			Course_Created::IDENTIFIER_NAME           => new Course_Created( $this->email_repository ),
			Course_Welcome::IDENTIFIER_NAME           => new Course_Welcome( $this->email_repository ),
			Student_Starts_Course::IDENTIFIER_NAME    => new Student_Starts_Course( $this->email_repository ),
			Student_Completes_Course::IDENTIFIER_NAME => new Student_Completes_Course( $this->email_repository ),
			Student_Completes_Lesson::IDENTIFIER_NAME => new Student_Completes_Lesson( $this->email_repository, $this->lesson_progress_repository ),
			Student_Submits_Quiz::IDENTIFIER_NAME     => new Student_Submits_Quiz( $this->email_repository ),
			Course_Completed::IDENTIFIER_NAME         => new Course_Completed( $this->email_repository ),
			New_Course_Assigned::IDENTIFIER_NAME      => new New_Course_Assigned( $this->email_repository ),
			Quiz_Graded::IDENTIFIER_NAME              => new Quiz_Graded( $this->email_repository ),
			Teacher_Message_Reply::IDENTIFIER_NAME    => new Teacher_Message_Reply( $this->email_repository ),
			Student_Message_Reply::IDENTIFIER_NAME    => new Student_Message_Reply( $this->email_repository ),
			Student_Sends_Message::IDENTIFIER_NAME    => new Student_Sends_Message( $this->email_repository ),
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
		 * @since 4.12.0
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
