<?php
/**
 * File containing the Comments_Based_Lesson_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Models;

use DateTimeInterface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;
use Sensei_Lesson;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Lesson_Progress.
 *
 * @internal
 *
 * @since 4.18.0
 */
class Comments_Based_Lesson_Progress extends Lesson_Progress_Abstract {
	/**
	 * Changes the lesson progress status and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $completed_at The completion date.
	 */
	public function complete( ?DateTimeInterface $completed_at = null ): void {
		$this->completed_at = $completed_at ?? current_datetime();
		$has_questions      = Sensei_Lesson::lesson_quiz_has_questions( $this->lesson_id );
		$this->status       = $has_questions ? Quiz_Progress_Interface::STATUS_PASSED : self::STATUS_COMPLETE;
	}

	/**
	 * Returns the lesson progress status.
	 *
	 * @internal
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
