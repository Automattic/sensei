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
	 * All statuses that indicate a lesson is no longer in progress.
	 *
	 * Used for "Completed" counts and completion rate in reports.
	 * Includes 'failed' and 'ungraded' because the student has finished
	 * the lesson activity even if they did not pass.
	 *
	 * @since $$next-version$$
	 *
	 * @var string[]
	 */
	public const COMPLETED_STATUSES = [ 'complete', 'graded', 'passed', 'failed', 'ungraded' ];

	/**
	 * Statuses that have a valid completion date in the data model.
	 *
	 * Excludes 'failed' and 'ungraded' because those statuses do not have a
	 * completion date set. Used as the divisor for days-to-complete calculations.
	 *
	 * @since $$next-version$$
	 *
	 * @var string[]
	 */
	public const STATUSES_WITH_COMPLETION_DATE = [ 'complete', 'graded', 'passed' ];

	/**
	 * Get the site's UTC offset in '+HH:MM' / '-HH:MM' format for CONVERT_TZ.
	 *
	 * Uses a numeric offset so that MySQL timezone tables are not required.
	 *
	 * @since $$next-version$$
	 *
	 * @return string UTC offset string, e.g. '+05:00' or '-05:00'.
	 */
	public static function get_utc_offset_string(): string {
		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = abs( (int) ( ( $offset - $hours ) * 60 ) );
		$sign    = $offset < 0 ? '-' : '+';

		return sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes );
	}

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
	 * @var float|null
	 */
	public ?float $grade;

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
	 * @param string     $status     The progress status.
	 * @param int        $user_id    The user ID.
	 * @param int        $lesson_id  The lesson post ID.
	 * @param string     $updated_at The date string of the last update.
	 * @param float|null $grade      The grade percentage, or null if not graded.
	 */
	public function __construct( string $status, int $user_id, int $lesson_id, string $updated_at, ?float $grade ) {
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
