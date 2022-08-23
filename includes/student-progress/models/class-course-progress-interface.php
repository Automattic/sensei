<?php
/**
 * File containing the Sensei_Course_Progress_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Models;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Sensei_Course_Progress_Interface.
 *
 * @since $$next-version$$
 */
interface Course_Progress_Interface {
	/**
	 * Status course in progress.
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Status course complete.
	 */
	public const STATUS_COMPLETE = 'complete';

	/**
	 * Set in-progress status and start date.
	 *
	 * @param DateTime|null $started_at Course start date.
	 */
	public function start( DateTime $started_at = null ): void;

	/**
	 * Set complete status and completion date.
	 *
	 * @param DateTime|null $completed_at Course completion date.
	 */
	public function complete( DateTime $completed_at = null ): void;

	/**
	 * Returns the progress identifier.
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Returns the course identifier.
	 *
	 * @return int
	 */
	public function get_course_id(): int;

	/**
	 * Returns the user identifier.
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Returns the course progress status.
	 *
	 * @return string|null
	 */
	public function get_status(): ?string;

	/**
	 * Returns the course start date.
	 *
	 * @return DateTime|null
	 */
	public function get_started_at(): ?DateTime;

	/**
	 * Returns the course completion date.
	 *
	 * @return DateTime|null
	 */
	public function get_completed_at(): ?DateTime;

	/**
	 * Returns the course progress created date.
	 *
	 * @return DateTime
	 */
	public function get_created_at(): DateTime;

	/**
	 * Returns the course progress updated date.
	 *
	 * @return DateTime
	 */
	public function get_updated_at(): DateTime;

	/**
	 * Returns the course progress metadata.
	 * Method exists for compatibility with the legacy code and will be removed in later versions.
	 *
	 * @return array Course progress metadata.
	 */
	public function get_metadata(): array;

	/**
	 * Set the course progress updated date.
	 *
	 * @param DateTime $updated_at Course progress updated date.
	 */
	public function set_updated_at( DateTime $updated_at ): void;
}
