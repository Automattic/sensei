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
	 * Activity date in the site timezone.
	 *
	 * @var string
	 */
	public string $activity_at;

	/**
	 * Legacy comment properties exposed by student column filters.
	 *
	 * @var array<string, string>
	 */
	private const LEGACY_PROPERTY_MAP = array(
		'comment_approved' => 'status',
		'comment_post_ID'  => 'post_id',
		'comment_date'     => 'activity_at',
	);

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param int         $post_id      Course or lesson ID.
	 * @param int         $user_id      Student ID.
	 * @param string      $status       Progress status.
	 * @param string|null $completed_at Completion date in the site timezone.
	 * @param string      $activity_at  Activity date in the site timezone.
	 */
	public function __construct( int $post_id, int $user_id, string $status, ?string $completed_at, string $activity_at ) {
		$this->post_id      = $post_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->completed_at = $completed_at;
		$this->activity_at  = $activity_at;
	}

	/**
	 * Preserve legacy property reads in student column filters.
	 *
	 * Unmapped properties trigger a _doing_it_wrong notice.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $key The property name.
	 * @return mixed|null The property value, or null if not mapped.
	 */
	public function __get( $key ) {
		if ( isset( self::LEGACY_PROPERTY_MAP[ $key ] ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- _deprecated_argument handles its own output.
			_deprecated_argument(
				'Student_Management_Item::$' . $key,
				'$$next-version$$',
				sprintf(
					/* translators: 1: old property name, 2: new property name */
					'Accessing Student_Management_Item via legacy WP_Comment property "%1$s" is deprecated. Use "%2$s" instead.',
					$key,
					self::LEGACY_PROPERTY_MAP[ $key ]
				)
			);
			// phpcs:enable

			return $this->{self::LEGACY_PROPERTY_MAP[ $key ]};
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- _doing_it_wrong handles its own output.
		_doing_it_wrong(
			'Student_Management_Item::$' . $key,
			sprintf(
				/* translators: %s: property name */
				'Property "%s" does not exist on Student_Management_Item. The student list table no longer uses WP_Comment objects.',
				$key
			),
			'$$next-version$$'
		);
		// phpcs:enable

		return null;
	}
}
