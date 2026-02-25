<?php
/**
 * File with trait Sensei_HPPS_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers related to the High-Performance Progress Storage feature.
 *
 * @since 4.20.0
 */
trait Sensei_HPPS_Helpers {
	/**
	 * Course progress repository.
	 *
	 * @var \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface
	 */
	private $_course_progress_repository;

	/**
	 * Lesson progress repository.
	 *
	 * @var \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface
	 */
	private $_lesson_progress_repository;

	/**
	 * Quiz repository.
	 *
	 * @var \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface
	 */
	private $_quiz_progress_repository;

	/**
	 * Submission repository.
	 *
	 * @var \Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface
	 */
	private $_quiz_submission_repository;

	/**
	 * Answer repository.
	 *
	 * @var \Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Interface
	 */
	private $_quiz_answer_repository;

	/**
	 * Grade repository.
	 *
	 * @var \Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Interface
	 */
	private $_quiz_grade_repository;

	private function enable_hpps_tables_repository() {
		Sensei()->settings->settings['experimental_progress_storage_repository'] = Progress_Storage_Settings::TABLES_STORAGE;

		$this->_course_progress_repository = Sensei()->course_progress_repository;
		$this->_lesson_progress_repository = Sensei()->lesson_progress_repository;
		$this->_quiz_progress_repository   = Sensei()->quiz_progress_repository;
		$this->_quiz_submission_repository = Sensei()->quiz_submission_repository;
		$this->_quiz_answer_repository     = Sensei()->quiz_answer_repository;
		$this->_quiz_grade_repository      = Sensei()->quiz_grade_repository;

		Sensei()->course_progress_repository = ( new Course_Progress_Repository_Factory( true, true ) )->create();
		Sensei()->lesson_progress_repository = ( new Lesson_Progress_Repository_Factory( true, true ) )->create();
		Sensei()->quiz_progress_repository   = ( new Quiz_Progress_Repository_Factory( true, true ) )->create();
		Sensei()->quiz_submission_repository = ( new Submission_Repository_Factory( true, true ) )->create();
		Sensei()->quiz_answer_repository     = ( new Answer_Repository_Factory( true, true ) )->create();
		Sensei()->quiz_grade_repository      = ( new Grade_Repository_Factory( true, true ) )->create();
	}

	private function reset_hpps_repository() {
		Sensei()->settings->settings['experimental_progress_storage_repository'] = Progress_Storage_Settings::COMMENTS_STORAGE;

		Sensei()->course_progress_repository = $this->_course_progress_repository;
		Sensei()->lesson_progress_repository = $this->_lesson_progress_repository;
		Sensei()->quiz_progress_repository   = $this->_quiz_progress_repository;
		Sensei()->quiz_submission_repository = $this->_quiz_submission_repository;
		Sensei()->quiz_answer_repository     = $this->_quiz_answer_repository;
		Sensei()->quiz_grade_repository      = $this->_quiz_grade_repository;
	}

	/**
	 * Check if HPPS tables mode is enabled via the environment variable.
	 *
	 * @return bool
	 */
	public static function is_hpps_tables_mode(): bool {
		return (bool) getenv( 'ENABLE_HPPS' );
	}

	/**
	 * Skip the current test when HPPS tables mode is active.
	 *
	 * @param string $reason Reason for skipping.
	 */
	private function skip_in_hpps_mode( string $reason = 'Skipped in HPPS tables mode.' ): void {
		if ( self::is_hpps_tables_mode() ) {
			$this->markTestSkipped( $reason );
		}
	}
}
