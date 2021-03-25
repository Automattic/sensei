<?php
/**
 * File containing Sensei_Updates class.
 *
 * @package sensei-lms
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Updates Class
 *
 * Class that contains the updates for Sensei data and structures.
 *
 * @author Automattic
 * @since 1.1.0
 * @since 3.7.0 New constructor signature and single purpose for performing update tasks.
 */
class Sensei_Updates {
	/**
	 * Version that is currently being updated from.
	 *
	 * @var string|null
	 */
	private $current_version;

	/**
	 * Flag if this is a new install.
	 *
	 * @var bool
	 */
	private $is_new_install;

	/**
	 * Flag if this is an upgrade.
	 *
	 * @var bool
	 */
	private $is_upgrade;

	/**
	 * Sensei_Updates constructor.
	 *
	 * Default values for backwards compatibility pre-v3.7.
	 *
	 * @param string $current_version Version that is currently being updated from.
	 * @param bool   $is_new_install  Flag if this is a new install.
	 * @param bool   $is_upgrade      Flag if this is an upgrade.
	 */
	public function __construct( $current_version = null, $is_new_install = false, $is_upgrade = false ) {
		if ( is_object( $current_version ) ) {
			$current_version = null;
		}

		$this->current_version = $current_version;
		$this->is_new_install  = $is_new_install;
		$this->is_upgrade      = $is_upgrade;
	}

	/**
	 * Run the updates (if necessary).
	 *
	 * @since 3.7.0
	 */
	public function run_updates() {
		// Only proceed if we knew the previous version and this was a new install or an upgrade.
		if ( $this->current_version && ! $this->is_new_install && ! $this->is_upgrade ) {
			return;
		}

		if ( $this->is_upgrade ) {
			$this->log_update();
		}

		$this->v3_0_check_legacy_enrolment();
		$this->v3_7_check_rewrite_front();
		$this->v3_7_add_comment_indexes();
		$this->v3_9_fix_question_author();
		$this->v3_9_remove_abandoned_multiple_question();

		// Flush rewrite cache.
		Sensei()->initiate_rewrite_rules_flush();
	}

	/**
	 * Enqueue job to remove the abandoned `multiple_question`.
	 */
	private function v3_9_remove_abandoned_multiple_question() {
		// Only run this if we're upgrading and the current version (before upgrade) is less than 3.9.0.
		if ( ! $this->is_upgrade || version_compare( $this->current_version, '3.9.0', '>=' ) ) {
			return;
		}

		Sensei_Scheduler::instance()->schedule_job( new Sensei_Update_Remove_Abandoned_Multiple_Question() );
	}

	/**
	 * Enqueue job to fix question post authors from previous course teacher changes.
	 */
	private function v3_9_fix_question_author() {
		// Only run this if we're upgrading and the current version (before upgrade) is less than 3.9.0.
		if ( ! $this->is_upgrade || version_compare( $this->current_version, '3.9.0', '>=' ) ) {
			return;
		}

		Sensei_Scheduler::instance()->schedule_job( new Sensei_Update_Fix_Question_Author() );
	}

	/**
	 * Add comment table indexes.
	 *
	 * @since 3.7.0
	 */
	private function v3_7_add_comment_indexes() {
		global $wpdb;

		/**
		 * Filter to disable attempts at adding the comment indexes.
		 *
		 * @hook sensei_add_comment_indexes
		 * @since 3.7.0
		 *
		 * @param {bool} $do_add_indexes True if indexes should be added to comment table.
		 *
		 * @return {bool}
		 */
		if ( ! apply_filters( 'sensei_add_comment_indexes', true ) ) {
			return;
		}

		$indexes = [
			'woo_idx_comment_type'        => [ 'comment_type' ],
			'sensei_comment_type_user_id' => [ 'comment_type', 'user_id' ],
		];

		$current_indexes = array_map(
			function( $arr ) {
				return implode( ',', $arr['columns'] );
			},
			$this->get_table_indexes( $wpdb->comments )
		);

		foreach ( $indexes as $name => $columns ) {
			if ( isset( $current_indexes[ $name ] ) ) {
				continue;
			}

			sort( $columns );
			if ( in_array( implode( ',', $columns ), $current_indexes, true ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Safe schema change.
			$wpdb->query( "ALTER TABLE {$wpdb->comments} ADD INDEX {$name} (`" . implode( '`,`', $columns ) . '`)' );
		}
	}

	/**
	 * Get indexes for a table.
	 *
	 * @param string $table Table to get indexes for.
	 *
	 * @return array
	 */
	private function get_table_indexes( $table ) {
		global $wpdb;

		$indexes = [];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe direct sql.
		$results = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", ARRAY_A );
		if ( ! $results ) {
			return [];
		}

		foreach ( $results as $row ) {
			if ( ! isset( $indexes[ $row['Key_name'] ] ) ) {
				$indexes[ $row['Key_name'] ] = [
					'unique'  => 0 === (int) $row['Non_unique'],
					'columns' => [],
				];
			}
			$indexes[ $row['Key_name'] ]['columns'][] = $row['Column_name'];
		}

		foreach ( $indexes as $index => $config ) {
			sort( $indexes[ $index ]['columns'] );
		}

		return $indexes;
	}

	/**
	 * Check for rewrite front and set legacy flag if needed.
	 *
	 * @since 3.7.0
	 */
	private function v3_7_check_rewrite_front() {
		global $wp_rewrite;

		// Set up legacy `with_front` on CPT rewrite options.
		if (
			$this->is_upgrade
			&& version_compare( '3.7.0', $this->current_version, '>' )
			&& '' !== trim( $wp_rewrite->front, '/' )
		) {
			Sensei()->set_legacy_flag( Sensei_Main::LEGACY_FLAG_WITH_FRONT, true );
		}
	}

	/**
	 * Check for legacy enrolment data and set flag if needed.
	 *
	 * @since 3.0.0
	 */
	private function v3_0_check_legacy_enrolment() {
		// Mark site as having enrolment data from legacy instances.
		if (
			// If the version is known and the previous version was pre-3.0.0.
			(
				$this->is_upgrade
				&& version_compare( '3.0.0', $this->current_version, '>' )
			)

			// If there wasn't a current version set and this isn't a new install, double check to make sure there wasn't any enrolment.
			|| (
				! $this->current_version
				&& ! $this->is_new_install
				&& $this->course_progress_exists()
			)
		) {
			update_option( 'sensei_enrolment_legacy', time() );
		}
	}

	/**
	 * Helper function to check to see if any course progress exists in the database.
	 *
	 * @return bool
	 */
	private function course_progress_exists() {
		$activity_args = [
			'type'   => 'sensei_course_status',
			'number' => 1,
			'status' => 'any',
		];

		$activity_sample = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		return ! empty( $activity_sample );
	}

	/**
	 * Get an array of quiz post IDs.
	 *
	 * @return int[]
	 */
	private function get_quiz_ids() {
		$query = new WP_Query(
			[
				'post_type'        => 'quiz',
				'fields'           => 'ids',
				'post_status'      => [ 'draft', 'publish' ],
				'posts_per_page'   => -1,
				'no_found_rows'    => true,
				'suppress_filters' => 1,
			]
		);

		return array_map( 'intval', $query->posts );
	}

	/**
	 * Logs the system update.
	 */
	private function log_update() {
		wp_schedule_single_event(
			time(),
			'sensei_log_update',
			[
				[
					'from_version'       => $this->current_version,
					'to_version'         => Sensei()->version,
					'days_since_release' => $this->get_days_since_release(),
				],
			]
		);
	}

	/**
	 * Get the days since release.
	 *
	 * @return int|null
	 */
	private function get_days_since_release() {
		$releases = $this->get_changelog_release_dates( Sensei()->version );

		if ( ! isset( $releases[ Sensei()->version ] ) ) {
			return null;
		}

		$today = ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->setTime( 0, 0, 0 );
		$diff  = $releases[ Sensei()->version ]->diff( $today );
		$days  = false !== $diff->d ? (int) $diff->d : null;

		return $days;
	}

	/**
	 * Get the release dates from the changelog.
	 *
	 * @param string $version Filter to just include a single version (Optional).
	 *
	 * @return DateTimeImmutable[]
	 */
	private function get_changelog_release_dates( $version = null ) {
		$releases  = [];
		$changelog = $this->get_changelog();
		if ( ! $changelog ) {
			return $releases;
		}

		$version_match = '[\d\.\-a-z]+';
		if ( $version ) {
			$version_match = preg_quote( $version, '/' );
		}

		preg_match_all( "/((?'year'\d{4})[\-\.](?'month'\d{1,2})[\-\.](?'day'\d{1,2}).*version\s+(?'version'{$version_match}))[^\S]/", $changelog, $releases_raw, PREG_SET_ORDER );

		foreach ( $releases_raw as $release ) {
			if ( empty( $release['version'] ) || empty( $release['year'] ) || empty( $release['month'] ) || empty( $release['day'] ) ) {
				continue;
			}

			$releases[ $release['version'] ] = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', sprintf( '%04d-%02d-%02d 00:00:00', $release['year'], $release['month'], $release['day'] ), new DateTimeZone( 'UTC' ) );
		}

		return $releases;
	}

	/**
	 * Get the changelog contents.
	 *
	 * @return false|string
	 */
	protected function get_changelog() {
		$changelog_path = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'changelog.txt';
		if ( ! is_readable( $changelog_path ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file usage.
		return file_get_contents( $changelog_path );
	}

	/**
	 * Handles deprecation notices for old methods.
	 *
	 * @param string $name Method name.
	 * @param array  $args Called method arguments.
	 *
	 * @return mixed
	 * @throws BadMethodCallException When method is not known.
	 */
	public function __call( $name, $args ) {
		$methods = [
			'sensei_updates_page'                         => [
				'version' => '3.7.0',
				'default' => null,
			],
			'function_in_whitelist'                       => [
				'version' => '3.7.0',
				'default' => false,
			],
			'update'                                      => [
				'version' => '3.7.0',
				'default' => false,
			],
			'set_default_quiz_grade_type'                 => [
				'version' => '3.7.0',
				'default' => null,
			],
			'set_default_question_type'                   => [
				'version' => '3.7.0',
				'default' => null,
			],
			'update_question_answer_data'                 => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_question_grade_points'                => [
				'version' => '3.7.0',
				'default' => true,
			],
			'convert_essay_paste_questions'               => [
				'version' => '3.7.0',
				'default' => true,
			],
			'set_random_question_order'                   => [
				'version' => '3.7.0',
				'default' => true,
			],
			'set_default_show_question_count'             => [
				'version' => '3.7.0',
				'default' => true,
			],
			'remove_deleted_user_activity'                => [
				'version' => '3.7.0',
				'default' => true,
			],
			'add_teacher_role'                            => [
				'version' => '3.7.0',
				'default' => true,
			],
			'restructure_question_meta'                   => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_quiz_settings'                        => [
				'version' => '3.7.0',
				'default' => true,
			],
			'reset_lesson_order_meta'                     => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_question_gap_fill_separators'         => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_quiz_lesson_relationship'             => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_fix_lessons'                  => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_convert_lessons'              => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_convert_courses'              => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_repair_course_statuses'       => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_convert_questions'            => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_legacy_sensei_comments_status'        => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_comment_course_lesson_comment_counts' => [
				'version' => '3.7.0',
				'default' => true,
			],
			'remove_legacy_comments'                      => [
				'version' => '3.7.0',
				'default' => true,
			],
			'index_comment_status_field'                  => [
				'version' => '3.7.0',
				'default' => true,
			],
			'enhance_teacher_role'                        => [
				'version' => '3.7.0',
				'default' => true,
			],
			'recalculate_enrolment'                       => [
				'version' => '3.7.0',
				'default' => true,
			],
		];

		if ( isset( $methods[ $name ] ) ) {
			_deprecated_function( esc_html( 'Sensei_Updates::' . $name ), esc_html( $methods[ $name ]['version'] ) );

			return isset( $methods[ $name ]['default'] ) ? $methods[ $name ]['default'] : null;
		}

		throw new BadMethodCallException( sprintf( 'Sensei_Updates::%s method does not exist' ) );
	}

	/**
	 * Sets the role capabilities for WordPress users.
	 *
	 * @since 1.1.0
	 * @deprecated 3.7.0
	 */
	public function assign_role_caps() {
		_deprecated_function( __METHOD__, '3.7.0', 'Sensei_Main::assign_role_caps' );

		Sensei()->assign_role_caps();
	}

	/**
	 * Add Sensei Admin Capabilities.
	 *
	 * @deprecated 3.7.0
	 *
	 * @return bool
	 */
	public function add_sensei_caps() {
		_deprecated_function( __METHOD__, '3.7.0', 'Sensei_Main::add_sensei_admin_caps' );

		return Sensei()->add_sensei_admin_caps();
	}

	/**
	 * Add editor role capabilities.
	 *
	 * @deprecated 3.7.0
	 *
	 * @return bool
	 */
	public function add_editor_caps() {
		_deprecated_function( __METHOD__, '3.7.0', 'Sensei_Main::add_editor_caps' );

		return Sensei()->add_editor_caps();
	}
}

/**
 * Class WooThemes_Sensei_Updates
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Updates extends Sensei_Updates {} // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
