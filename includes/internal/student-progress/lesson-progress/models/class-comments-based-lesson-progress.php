<?php
/**
 * File containing the Comments_Based_Lesson_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Models;

use DateTimeInterface;
use Sensei\Internal\Student_Progress\Comment_Progress;
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
		$pass_required = true;
		if ( 'failed' === $this->status ) {
			$lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $this->lesson_id );
			$pass_required  = ! $lesson_quiz_id || (bool) get_post_meta( $lesson_quiz_id, '_pass_required', true );
		}

		return Comment_Progress::is_complete( 'lesson', $this->status, $pass_required )
			? self::STATUS_COMPLETE
			: self::STATUS_IN_PROGRESS;
	}

	/**
	 * Returns the unnormalized status stored by the comments-based repository.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @return string|null
	 */
	public function get_raw_status(): ?string {
		return $this->status;
	}
}
