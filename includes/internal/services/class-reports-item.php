<?php
/**
 * File containing the Reports_Item class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Reports_Item.
 *
 * Value object representing a single report row. Abstracts away the
 * difference between comment-based and table-based storage so that
 * the report UI code can work with a uniform interface.
 *
 * @since 4.26.0
 */
class Reports_Item {

	/**
	 * Statuses counted as completed for per-lesson completion counts
	 * in report listings.
	 *
	 * Excludes 'ungraded' because those lessons are awaiting admin grading.
	 *
	 * @var string[]
	 */
	public const COMPLETED_STATUSES = array(
		Lesson_Progress_Interface::STATUS_COMPLETE,
		Quiz_Progress_Interface::STATUS_GRADED,
		Quiz_Progress_Interface::STATUS_PASSED,
		Quiz_Progress_Interface::STATUS_FAILED,
	);

	/**
	 * The post ID (lesson or course).
	 *
	 * @var int
	 */
	public int $post_id;

	/**
	 * The user ID.
	 *
	 * @var int
	 */
	public int $user_id;

	/**
	 * The progress status.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * The date the progress was started, or null if not available.
	 *
	 * @var string|null
	 */
	public ?string $started_at;

	/**
	 * The date the progress was completed, or null if not completed.
	 *
	 * @var string|null
	 */
	public ?string $completed_at;

	/**
	 * The grade percentage, or null if not graded.
	 *
	 * @var float|null
	 */
	public ?float $grade;

	/**
	 * The completion percentage, or null if not applicable.
	 *
	 * @var float|null
	 */
	public ?float $percent;

	/**
	 * Mapping of legacy WP_Comment property names to Reports_Item properties.
	 *
	 * @var array<string, string>
	 */
	private const LEGACY_PROPERTY_MAP = array(
		'comment_approved' => 'status',
		'comment_post_ID'  => 'post_id',
		'comment_date'     => 'completed_at',
	);

	/**
	 * Constructor.
	 *
	 * @since 4.26.0
	 *
	 * @param int         $post_id      The post ID.
	 * @param int         $user_id      The user ID.
	 * @param string      $status       The progress status.
	 * @param string|null $started_at   The date the progress was started.
	 * @param string|null $completed_at The date the progress was completed.
	 * @param float|null  $grade        The grade percentage.
	 * @param float|null  $percent      The completion percentage.
	 */
	public function __construct(
		int $post_id,
		int $user_id,
		string $status,
		?string $started_at,
		?string $completed_at,
		?float $grade,
		?float $percent
	) {
		$this->post_id      = $post_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->started_at   = $started_at;
		$this->completed_at = $completed_at;
		$this->grade        = $grade;
		$this->percent      = $percent;
	}

	/**
	 * Provide backward-compatible access to legacy WP_Comment property names.
	 *
	 * This allows third-party code that hooks into report column data filters
	 * and reads WP_Comment properties to continue working with deprecation notices.
	 *
	 * Supported legacy properties: comment_approved, comment_post_ID, comment_date.
	 * Other WP_Comment properties (e.g. comment_ID) are not mapped and will
	 * trigger a _doing_it_wrong notice, since report data is now carried
	 * directly on the Reports_Item object.
	 *
	 * @since 4.26.0
	 *
	 * @param string $key The property name.
	 * @return mixed|null The property value, or null if not mapped.
	 */
	public function __get( $key ) {
		if ( isset( self::LEGACY_PROPERTY_MAP[ $key ] ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- _deprecated_argument handles its own output.
			_deprecated_argument(
				'Reports_Item::$' . $key,
				'4.26.0',
				sprintf(
					/* translators: 1: old property name, 2: new property name */
					'Accessing Reports_Item via legacy WP_Comment property "%1$s" is deprecated. Use "%2$s" instead.',
					$key,
					self::LEGACY_PROPERTY_MAP[ $key ]
				)
			);
			// phpcs:enable

			return $this->{self::LEGACY_PROPERTY_MAP[ $key ]};
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- _doing_it_wrong handles its own output.
		_doing_it_wrong(
			'Reports_Item::$' . $key,
			sprintf(
				/* translators: %s: property name */
				'Property "%s" does not exist on Reports_Item. The report list table no longer uses WP_Comment objects.',
				$key
			),
			'4.26.0'
		);
		// phpcs:enable

		return null;
	}
}
