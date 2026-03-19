<?php
/**
 * File containing the Grading_Item class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Grading_Item.
 *
 * Value object representing a single grading row. Abstracts away the
 * difference between comment-based and table-based storage so that
 * the grading UI code can work with a uniform interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Grading_Item {

	/**
	 * Statuses that indicate a lesson has been completed with a valid completion date.
	 *
	 * Excludes 'failed' and 'ungraded' because those statuses do not have a
	 * completion date set in the data model. Used for lesson completion counts
	 * and days-to-complete calculations in reports.
	 *
	 * @since $$next-version$$
	 *
	 * @var string[]
	 */
	public const STATUSES_WITH_COMPLETION_DATE = [ 'complete', 'graded', 'passed' ];

	/**
	 * The progress status. For HPPS tables-based storage, this is the
	 * effective status coalesced from quiz progress when available.
	 * For comments-based storage, this is the raw comment_approved value.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * The user ID.
	 *
	 * @var int
	 */
	public int $user_id;

	/**
	 * The lesson post ID.
	 *
	 * @var int
	 */
	public int $lesson_id;

	/**
	 * The date string of the last update.
	 *
	 * @var string
	 */
	public string $updated_at;

	/**
	 * The grade percentage, or null if not graded.
	 *
	 * @var int|null
	 */
	public ?int $grade;

	/**
	 * Mapping of legacy WP_Comment property names to Grading_Item properties.
	 *
	 * @var array<string, string>
	 */
	private const LEGACY_PROPERTY_MAP = [
		'comment_approved' => 'status',
		'comment_post_ID'  => 'lesson_id',
		'comment_date'     => 'updated_at',
	];

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param string   $status     The progress status.
	 * @param int      $user_id    The user ID.
	 * @param int      $lesson_id  The lesson post ID.
	 * @param string   $updated_at The date string of the last update.
	 * @param int|null $grade      The grade percentage, or null if not graded.
	 */
	public function __construct( string $status, int $user_id, int $lesson_id, string $updated_at, ?int $grade ) {
		$this->status     = $status;
		$this->user_id    = $user_id;
		$this->lesson_id  = $lesson_id;
		$this->updated_at = $updated_at;
		$this->grade      = $grade;
	}

	/**
	 * Provide backward-compatible access to legacy WP_Comment property names.
	 *
	 * This allows third-party code that hooks into `sensei_grading_main_column_data`
	 * and reads WP_Comment properties (e.g. `$item->comment_approved`) to continue
	 * working transparently.
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
				'Grading_Item::$' . $key,
				'$$next-version$$',
				sprintf(
					/* translators: 1: old property name, 2: new property name */
					'Accessing Grading_Item via legacy WP_Comment property "%1$s" is deprecated. Use "%2$s" instead.',
					$key,
					self::LEGACY_PROPERTY_MAP[ $key ]
				)
			);
			// phpcs:enable

			return $this->{self::LEGACY_PROPERTY_MAP[ $key ]};
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- _doing_it_wrong handles its own output.
		_doing_it_wrong(
			'Grading_Item::$' . $key,
			sprintf(
				/* translators: %s: property name */
				'Property "%s" does not exist on Grading_Item. The grading list table no longer uses WP_Comment objects.',
				$key
			),
			'$$next-version$$'
		);
		// phpcs:enable

		return null;
	}
}
