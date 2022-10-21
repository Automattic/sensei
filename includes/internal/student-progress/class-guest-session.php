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
		if ( isset( $_REQUEST['guest-learner'] ) ) {

			// Student progress repositories.
			Sensei()->course_progress_repository = new Session_Based_Course_Progress_Repository();
			Sensei()->lesson_progress_repository = new Session_Based_Lesson_Progress_Repository();
			Sensei()->quiz_progress_repository   = new Session_Based_Quiz_Progress_Repository();

			add_filter('determine_current_user', function() { return 1; });
			add_filter('sensei_is_enrolled', '__return_true');

		}
	}
}
