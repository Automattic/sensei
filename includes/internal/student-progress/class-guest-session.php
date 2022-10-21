<?php

/**
 * File containing the Guest_Learner class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Session_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Session_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Session_Based_Quiz_Progress_Repository;

/**
 * Class Guest_Learner.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Guest_Session {


	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

	}

	public function init() {

		if ( ! session_id() ) {
			session_start();
		}

		if ( isset( $_GET['start-guest-session'] ) ) {
			$this->start_guest_session();
		}

		if ( isset( $_GET['end-guest-session'] ) ) {
			$this->end_guest_session();
		}

		if ( $this->is_guest_session() ) {

			// Student progress repositories.
			Sensei()->course_progress_repository = new Session_Based_Course_Progress_Repository();
			Sensei()->lesson_progress_repository = new Session_Based_Lesson_Progress_Repository();
			Sensei()->quiz_progress_repository   = new Session_Based_Quiz_Progress_Repository();

			add_filter('determine_current_user', function() { return 1; });

			// Need to check if 'Guest access' is allowed for the course, or it's a teacher preview session.
			add_filter('sensei_is_enrolled', '__return_true' );
			add_filter('sensei_is_login_required', '__return_false' );
		}
	}

	public function is_guest_session() {
		return isset( $_SESSION['guest-learner'] );
	}

	public function start_guest_session() {
		$_SESSION['guest-learner'] = true;
	}

	public function end_guest_session() {
		unset( $_SESSION['guest-learner'] );
	}

}
