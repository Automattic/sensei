<?php
/**
 * Student management row data.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course or lesson progress displayed in student management.
 *
 * @internal
 * @since $$next-version$$
 */
class Student_Management_Item {

	/**
	 * Course or lesson ID.
	 *
	 * @var int
	 */
	public int $post_id;

	/**
	 * Student ID.
	 *
	 * @var int
	 */
	public int $user_id;

	/**
	 * Progress status.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * Completion date in the site timezone, or null when incomplete.
	 *
	 * @var string|null
	 */
	public ?string $completed_at;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param int         $post_id      Course or lesson ID.
	 * @param int         $user_id      Student ID.
	 * @param string      $status       Progress status.
	 * @param string|null $completed_at Completion date in the site timezone.
	 */
	public function __construct( int $post_id, int $user_id, string $status, ?string $completed_at ) {
		$this->post_id      = $post_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->completed_at = $completed_at;
	}
}
