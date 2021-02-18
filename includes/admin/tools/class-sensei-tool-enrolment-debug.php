<?php
/**
 * File containing Sensei_Tool_Enrolment_Debug class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Enrolment_Debug class.
 *
 * @since 3.7.0
 */
class Sensei_Tool_Enrolment_Debug implements Sensei_Tool_Interface, Sensei_Tool_Interactive_Interface {
	const NONCE_ACTION = 'enrolment-debug';

	/**
	 * Debug results.
	 *
	 * @var array
	 */
	private $results;

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'enrolment-debug';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Debug Course Enrollment', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Check what the enrollment status is between a course and learner.', 'sensei-lms' );
	}

	/**
	 * Is the tool a single action?
	 *
	 * @return bool
	 */
	public function is_single_action() {
		return false;
	}

	/**
	 * Output tool view for interactive action methods.
	 */
	public function output() {
		// If the results were processed by `process()`, show them here.
		if ( $this->results ) {
			$results = $this->results;
			include __DIR__ . '/views/html-enrolment-debug.php';
		}

		$user_query_args = [
			'number'  => 100,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		];
		$user_search     = new WP_User_Query( $user_query_args );

		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Variable used in view.
		$users = false;
		if ( $user_search->get_total() < 100 ) {
			$users = $user_search->get_results();
		}

		$course_query_args = [
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_type'      => 'course',
			'post_status'    => 'any',
		];
		$course_search     = new WP_Query( $course_query_args );

		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Variable used in view.
		$courses = false;
		if ( $course_search->found_posts < 100 ) {
			$courses = $course_search->get_posts();
		}

		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Variable used in view.
		$tool_id = $this->get_id();

		include __DIR__ . '/views/html-enrolment-debug-form.php';
	}

	/**
	 * Return to tool with a message.
	 *
	 * @param string $message  Message to show.
	 * @param bool   $is_error True if this is an error message.
	 */
	private function return_with_message( $message, $is_error ) {
		Sensei_Tools::instance()->add_user_message( $message, $is_error );

		wp_safe_redirect( Sensei_Tools::instance()->get_tool_url( $this ) );
		exit;
	}

	/**
	 * Process the tool action.
	 */
	public function process() {
		Sensei()->assets->enqueue( 'sensei-enrolment-debug', 'css/enrolment-debug.css' );

		if ( empty( $_GET['course_id'] ) && empty( $_GET['user_id'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't modify the nonce.
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), self::NONCE_ACTION ) ) {
			Sensei_Tools::instance()->trigger_invalid_request( $this );

			return;
		}

		if ( empty( $_GET['user_id'] ) ) {
			$this->return_with_message( __( 'Please select a user.', 'sensei-lms' ), true );

			return;
		}

		if ( empty( $_GET['course_id'] ) ) {
			$this->return_with_message( __( 'Please select a course ID.', 'sensei-lms' ), true );

			return;
		}

		$user_id   = intval( $_GET['user_id'] );
		$course_id = intval( $_GET['course_id'] );

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			$this->return_with_message( __( 'Invalid user ID selected.', 'sensei-lms' ), true );

			return;
		}

		$course = get_post( $course_id );
		if ( ! $course || 'course' !== get_post_type( $course ) ) {
			$this->return_with_message( __( 'Invalid course ID selected.', 'sensei-lms' ), true );

			return;
		}

		$this->results = $this->get_debug_results( $user, $course );
	}

	/**
	 * Get the debug results for a user/course.
	 *
	 * @param WP_User $user   User object.
	 * @param WP_Post $course Course post object.
	 *
	 * @return array
	 */
	private function get_debug_results( WP_User $user, WP_Post $course ) {
		$enrolment_manager    = Sensei_Course_Enrolment_Manager::instance();
		$course_enrolment     = Sensei_Course_Enrolment::get_course_instance( $course->ID );
		$provider_results     = $course_enrolment->get_enrolment_check_results( $user->ID );
		$is_enrolled          = $course_enrolment->is_enrolled( $user->ID );
		$provider_results_arr = [];

		$results_stale = false;
		if ( ! $provider_results || $provider_results->get_version_hash() !== $course_enrolment->get_current_enrolment_result_version() ) {
			$results_stale    = true;
			$provider_results = $course_enrolment->get_enrolment_check_results( $user->ID );
		}

		if ( $provider_results ) {
			$provider_results_arr = $provider_results->get_provider_results();
		}

		$debug_results = [
			'course'           => $course->post_title,
			'course_id'        => $course->ID,
			'course_published' => 'publish' === $course->post_status,
			'user'             => $user->display_name . ' (' . $user->ID . ')',
			'user_id'          => $user->ID,
			'is_enrolled'      => $is_enrolled,
			'is_removed'       => $course_enrolment->is_learner_removed( $user->ID ),
			'results_stale'    => $results_stale,
			'results_match'    => true,
			'results_time'     => $provider_results ? self::format_date( $provider_results->get_time() ) : null,
			'providers'        => [],
			'progress'         => false,
		];

		$course_progress_id = Sensei_Utils::has_started_course( $course->ID, $user->ID );
		if ( $course_progress_id ) {
			$course_progress = get_comment( $course_progress_id );
			$user_start_date = get_comment_meta( $course_progress_id, 'start', true );
			$status          = esc_html__( 'In Progress', 'sensei-lms' );

			if ( 'complete' === $course_progress->comment_approved ) {
				$status           = esc_html__( 'Completed', 'sensei-lms' );
				$percent_complete = 100;
			} else {
				$percent_complete = $this->get_percent_complete( $user->ID, $course->ID );
			}

			$last_activity_time = $this->get_last_progress_activity_date( $user->ID, $course->ID, $course_progress_id );

			$debug_results['progress'] = [
				'start_date'       => self::format_date( strtotime( $user_start_date ) ),
				'last_activity'    => $last_activity_time ? self::format_date( $last_activity_time ) : false,
				'status'           => $status,
				'percent_complete' => $percent_complete,
			];
		}

		$providers = $enrolment_manager->get_all_enrolment_providers();
		foreach ( $providers as $provider ) {
			$provider_info = [
				'id'             => $provider->get_id(),
				'name'           => $provider->get_name(),
				'handles_course' => $provider->handles_enrolment( $course->ID ),
				'is_enrolled'    => null,
				'debug'          => false,
				'logs'           => [],
				'history'        => false,
			];

			if ( $provider_info['handles_course'] ) {
				$provider_info['is_enrolled'] = false;
				try {
					$provider_info['is_enrolled'] = $provider->is_enrolled( $user->ID, $course->ID );
				} catch ( Exception $e ) {
					$provider_info['logs'][] = [
						'timestamp' => time(),
						'message'   => $e->getMessage(),
						'data'      => [
							'source' => $e->getFile() . ':' . $e->getLine(),
							'trace'  => $e->getTraceAsString(),
						],
					];
				}
				if (
					! isset( $provider_results_arr[ $provider->get_id() ] )
					|| $provider_results_arr[ $provider->get_id() ] !== $provider_info['is_enrolled']
				) {
					$debug_results['results_match'] = false;
				}
			} else {
				if ( isset( $provider_results_arr[ $provider->get_id() ] ) ) {
					$debug_results['results_match'] = false;
				}
			}

			if (
				interface_exists( 'Sensei_Course_Enrolment_Provider_Debug_Interface' )
				&& $provider instanceof Sensei_Course_Enrolment_Provider_Debug_Interface
			) {
				$provider_info['debug'] = $provider->debug( $user->ID, $course->ID );
			}

			if ( class_exists( 'Sensei_Enrolment_Provider_Journal_Store' ) ) {
				$provider_info['logs']    = array_merge( $provider_info['logs'], Sensei_Enrolment_Provider_Journal_Store::get_provider_logs( $provider, $user->ID, $course->ID ) );
				$provider_info['history'] = Sensei_Enrolment_Provider_Journal_Store::get_provider_history( $provider, $user->ID, $course->ID );
			}

			$debug_results['providers'][ $provider_info['id'] ] = $provider_info;
		}

		return $debug_results;
	}


	/**
	 * Get the unix timestamp of the last progress activity.
	 *
	 * @param int $user_id            User ID.
	 * @param int $course_id          Course post ID.
	 * @param int $course_progress_id Course progress comment ID.
	 *
	 * @return int|false
	 */
	private function get_last_progress_activity_date( $user_id, $course_id, $course_progress_id ) {
		$dates = [];

		$course_progress = get_comment( $course_progress_id );
		if ( ! $course_progress ) {
			return false;
		}

		$dates[] = strtotime( $course_progress->comment_date_gmt );

		$course_lessons = Sensei()->course->course_lessons( $course_id );

		foreach ( $course_lessons as $lesson ) {
			$lesson_status = Sensei_Utils::user_lesson_status( $lesson->ID, $user_id );
			if ( $lesson_status ) {
				$dates[] = strtotime( $lesson_status->comment_date_gmt );
			}
		}

		return max( $dates );
	}


	/**
	 * Get the percent complete for a user's progress in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return false|float
	 */
	private function get_percent_complete( $user_id, $course_id ) {
		$completed_lesson_ids = [];

		$course_lessons = Sensei()->course->course_lessons( $course_id );

		if ( empty( $course_lessons ) ) {
			return 0;
		}

		foreach ( $course_lessons as $lesson ) {
			$is_lesson_completed = Sensei_Utils::user_completed_lesson( $lesson->ID, $user_id );
			if ( $is_lesson_completed ) {
				$completed_lesson_ids[] = $lesson->ID;
			}
		}

		return round( ( count( $completed_lesson_ids ) / count( $course_lessons ) ) * 100 );
	}

	/**
	 * Get the URL for the enrolment debug tool for a user/course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return string
	 */
	public static function get_enrolment_debug_url( $user_id, $course_id ) {
		return wp_nonce_url(
			add_query_arg(
				[
					'course_id' => $course_id,
					'user_id'   => $user_id,
				],
				Sensei_Tools::instance()->get_tool_url( new self() )
			),
			self::NONCE_ACTION
		);
	}

	/**
	 * Format the date time to be human readable.
	 *
	 * @param int|float $time Format the time.
	 *
	 * @return string
	 */
	public static function format_date( $time ) {
		$time             = round( $time );
		$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		if ( function_exists( 'wp_date' ) ) {
			$formatted_time = wp_date( $date_time_format, $time );
		} else {
			$formatted_time = date_i18n( $date_time_format, $time );
		}

		return $formatted_time;
	}

	/**
	 * Add debug button to row.
	 *
	 * @param array $row_data  Row data for learner management.
	 * @param array $item      Activity information for row.
	 * @param int   $course_id Course post ID.
	 *
	 * @return array
	 */
	public static function add_debug_action( $row_data, $item, $course_id = null ) {
		/**
		 * Show the enrolment debug button on learner management.
		 *
		 * @since 3.7.0
		 * @hook sensei_show_enrolment_debug_button
		 *
		 * @param {bool} $show_button Whether to show the button.
		 * @param {int}  $user_id     User ID.
		 * @param {int}  $course_id   Course ID.
		 *
		 * @return {bool}
		 */
		$show_button = apply_filters( 'sensei_show_enrolment_debug_button', false, $item->user_id, $course_id );
		if (
			! $course_id
			|| ! current_user_can( 'manage_sensei' )
			|| ! $show_button
			|| 'course' !== get_post_type( $course_id )
		) {
			return $row_data;
		}

		$button_url           = self::get_enrolment_debug_url( $item->user_id, $course_id );
		$row_data['actions'] .= ' <a class="button" href="' . esc_url( $button_url ) . '">' . esc_html__( 'Debug Enrollment', 'sensei-lms' ) . '</a>';

		return $row_data;
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}
