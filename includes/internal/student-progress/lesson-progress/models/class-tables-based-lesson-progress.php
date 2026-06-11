<?php
/**
 * File containing the Tables_Based_Lesson_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Lesson_Progress.
 *
 * @internal
 *
 * @since 4.18.0
 */
class Tables_Based_Lesson_Progress extends Lesson_Progress_Abstract {
	/**
	 * Returns the lesson progress status.
	 *
	 * When a lesson's quiz has pass_required disabled, a 'failed' quiz status
	 * still means the lesson is complete — the learner is only required to
	 * attempt the quiz, not to pass it. Mirrors the equivalent logic in
	 * Comments_Based_Lesson_Progress::get_status().
	 *
	 * @internal
	 *
	 * @since $next-version$
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		switch ( $this->status ) {
			case 'complete':
			case 'graded':
			case 'passed':
				return self::STATUS_COMPLETE;

			case 'failed':
				// This may be 'completed' depending on...
				// Get Quiz ID, this won't be needed once all Quiz meta fields are stored on the Lesson.
				$lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $this->lesson_id );
				if ( $lesson_quiz_id ) {
					// ...the quiz pass setting.
					$pass_required = get_post_meta( $lesson_quiz_id, '_pass_required', true );
					if ( empty( $pass_required ) ) {
						// We just require the user to have done the quiz, not to have passed.
						return self::STATUS_COMPLETE;
					}
				}
				return self::STATUS_IN_PROGRESS;

			default:
				return self::STATUS_IN_PROGRESS;
		}
	}
}
