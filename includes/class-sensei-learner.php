<?php

/**
 * Responsible for all student specific functionality and helper functions
 *
 * @package Users
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Learner {
	const LEARNER_TERM_PREFIX = 'user-';

	/**
	 * Instance of singleton.
	 *
	 * @since 3.0.0
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Cache of the learner terms.
	 *
	 * @var WP_Term[]
	 */
	private static $learner_terms = [];

	/**
	 * Fetches an instance of the class.
	 *
	 * @since 3.0.0
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sensei_Course_Enrolment_Manager constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {}

	/**
	 * Sets the actions.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		// Delete user activity and enrolment terms when user is deleted.
		add_action( 'deleted_user', array( $this, 'delete_all_user_activity' ) );
	}

	/**
	 * Delete all activity for specified user.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $user_id User ID.
	 * @return boolean
	 */
	public function delete_all_user_activity( $user_id = 0 ) {
		$dataset_changes = false;

		if ( ! $user_id ) {
			return $dataset_changes;
		}

		// Remove enrolment terms.
		$learner_term = self::get_learner_term( $user_id );
		wp_delete_term( $learner_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME );

		$activities = Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_id ), true );

		if ( ! $activities ) {
			return $dataset_changes;
		}

		// Need to always return an array, even with only 1 item.
		if ( ! is_array( $activities ) ) {
			$activities = array( $activities );
		}

		foreach ( $activities as $activity ) {
			if ( empty( $activity->comment_type ) ) {
				continue;
			}
			if ( strpos( $activity->comment_type, 'sensei_' ) !== 0 ) {
				continue;
			}
			$dataset_changes = wp_delete_comment( intval( $activity->comment_ID ), true );
		}

		return $dataset_changes;
	}

	/**
	 * Query the courses a user is enrolled in.
	 *
	 * @param int   $user_id         User ID.
	 * @param array $base_query_args Base query arguments.
	 *
	 * @return WP_Query
	 */
	public function get_enrolled_courses_query( $user_id, $base_query_args = [] ) {
		$this->before_enrolled_courses_query( $user_id );

		$query_args = $this->get_enrolled_courses_query_args( $user_id, $base_query_args );

		return new WP_Query( $query_args );
	}

	/**
	 * Query the courses a user is enrolled in and hasn't completed.
	 *
	 * @param int   $user_id         User ID.
	 * @param array $base_query_args Base query arguments.
	 *
	 * @return WP_Query
	 */
	public function get_enrolled_active_courses_query( $user_id, $base_query_args = [] ) {
		return $this->get_enrolled_courses_query_by_progress_status( $user_id, $base_query_args, 'active' );
	}

	/**
	 * Query the courses a user is enrolled in and has completed.
	 *
	 * @param int   $user_id         User ID.
	 * @param array $base_query_args Base query arguments.
	 *
	 * @return WP_Query
	 */
	public function get_enrolled_completed_courses_query( $user_id, $base_query_args = [] ) {
		return $this->get_enrolled_courses_query_by_progress_status( $user_id, $base_query_args, 'completed' );
	}

	/**
	 * Query the courses a user is enrolled in by progress status.
	 *
	 * @param int    $user_id         User ID.
	 * @param array  $base_query_args Base query arguments.
	 * @param string $type            Type of query to run (`active` or `completed`).
	 *
	 * @return WP_Query
	 */
	private function get_enrolled_courses_query_by_progress_status( $user_id, $base_query_args, $type ) {
		$this->before_enrolled_courses_query( $user_id );

		$query_args = $this->get_enrolled_courses_query_args( $user_id, $base_query_args );

		if ( 'active' === $type ) {
			$course_ids = $this->get_course_ids_by_progress_status( $user_id, 'in-progress' );
		} else {
			$course_ids = $this->get_course_ids_by_progress_status( $user_id, 'complete' );
		}

		if ( ! empty( $query_args['post__in'] ) ) {
			$existing_post_ids = (array) $query_args['post__in'];
			$existing_post_ids = array_map( 'intval', $existing_post_ids );

			$course_ids = array_intersect( $course_ids, $existing_post_ids );
		}

		if ( empty( $course_ids ) ) {
			$course_ids = [ -1 ];
		}

		$query_args['post__in'] = $course_ids;

		return new WP_Query( $query_args );
	}

	/**
	 * Get the arguments to pass to WP_Query to fetch a learner's enrolled courses.
	 *
	 * @param int   $user_id         User ID.
	 * @param array $base_query_args Base query arguments.
	 *
	 * @return array
	 */
	public function get_enrolled_courses_query_args( $user_id, $base_query_args = [] ) {
		$order                = 'DESC';
		$orderby              = 'date';
		$has_set_course_order = '' !== get_option( 'sensei_course_order', '' );

		// If a fixed course order has been set, trust menu_order.
		if ( $has_set_course_order ) {
			$order   = 'ASC';
			$orderby = 'menu_order';
		}

		$default_args = [
			'post_status' => 'publish',
			'order'       => $order,
			'orderby'     => $orderby,
			'tax_query'   => [], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Just empty to set array.
		];

		$query_args   = array_merge( $default_args, $base_query_args );
		$learner_term = self::get_learner_term( $user_id );

		$query_args['post_type']   = 'course';
		$query_args['tax_query'][] = [
			'taxonomy'         => Sensei_PostTypes::LEARNER_TAXONOMY_NAME,
			'terms'            => $learner_term->term_id,
			'include_children' => false,
		];

		return $query_args;
	}

	/**
	 * Notify that a user's enrolled courses are about to be queried.
	 */
	private function before_enrolled_courses_query( $user_id ) {
		/**
		 * Fire before we query a user's enrolled courses. This needs to be called before
		 * building the query arguments because `active` courses might be incomplete if we
		 * haven't verified a user's enrolment is up-to-date.
		 *
		 * @since 3.0.0
		 *
		 * @param int $user_id User ID.
		 */
		do_action( 'sensei_before_learners_enrolled_courses_query', $user_id );
	}

	/**
	 * Get the course IDs by progress status.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Course progress status. Either `completed` or `in-progress`.
	 *
	 * @return int[]
	 */
	private function get_course_ids_by_progress_status( $user_id, $status ) {
		$course_ids      = [];
		$course_statuses = Sensei_Utils::sensei_check_for_activity(
			[
				'user_id' => $user_id,
				'type'    => 'sensei_course_status',
				'status'  => $status,
			],
			true
		);

		// Check for activity returns single if only one. We always want an array.
		if ( ! is_array( $course_statuses ) ) {
			$course_statuses = [ $course_statuses ];
		}

		foreach ( $course_statuses as $status ) {
			$course_ids[] = intval( $status->comment_post_ID );
		}

		return $course_ids;
	}

	/**
	 * Get the learner term for the user.
	 *
	 * @param int $user_id User ID.
	 * @return WP_Term
	 * @throws Exception When learner term could not be created.
	 */
	public static function get_learner_term( $user_id ) {
		$user_term_slug = self::get_learner_term_slug( $user_id );
		if ( ! isset( self::$learner_terms[ $user_id ] ) ) {
			self::$learner_terms[ $user_id ] = get_term_by( 'slug', $user_term_slug, Sensei_PostTypes::LEARNER_TAXONOMY_NAME );

			if ( empty( self::$learner_terms[ $user_id ] ) ) {
				$term = wp_insert_term( $user_term_slug, Sensei_PostTypes::LEARNER_TAXONOMY_NAME );
				if ( is_array( $term ) && ! empty( $term['term_id'] ) ) {
					self::$learner_terms[ $user_id ] = get_term( $term['term_id'] );
				}
			}

			if ( empty( self::$learner_terms[ $user_id ] ) || self::$learner_terms[ $user_id ] instanceof WP_Error ) {
				unset( self::$learner_terms[ $user_id ] );
				throw new Exception( esc_html__( 'Learner term could not be created for user.', 'sensei-lms' ) );
			}
		}

		return self::$learner_terms[ $user_id ];
	}

	/**
	 * Get the learner user ID from a term slug.
	 *
	 * @param string $term_name Term slug to parse the user ID from.
	 * @return int
	 */
	public static function get_learner_id( $term_name ) {
		// Cut off the `user-` prefix.
		return intval( substr( $term_name, strlen( self::LEARNER_TERM_PREFIX ) ) );
	}

	/**
	 * Gets the user term slug.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function get_learner_term_slug( $user_id ) {
		return self::LEARNER_TERM_PREFIX . $user_id;
	}

	/**
	 * Get the students full name
	 *
	 * This function replaces Sensei_Learner_Managment->get_learner_full_name
	 *
	 * @since 1.9.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool|mixed|void
	 */
	public static function get_full_name( $user_id ) {

		$full_name = '';

		if ( empty( $user_id ) || ! ( 0 < intval( $user_id ) )
			|| ! ( get_userdata( $user_id ) ) ) {
			return false;
		}

		// get the user details
		$user = get_user_by( 'id', $user_id );

		if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {

			$full_name = trim( $user->first_name ) . ' ' . trim( $user->last_name );

		} else {

			$full_name = $user->display_name;

		}

		/**
		 * Filter the user full name from the get_learner_full_name function.
		 *
		 * @since 1.8.0
		 * @param $full_name
		 * @param $user_id
		 */
		return apply_filters( 'sensei_learner_full_name', $full_name, $user_id );

	}//end get_full_name()

	/**
	 * Get all active learner ids for a course.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @deprecated 3.0.0
	 */
	public static function get_all_active_learner_ids_for_course( $course_id ) {
		_deprecated_function( __METHOD__, '3.0.0' );

		$post_id = absint( $course_id );

		if ( ! $post_id ) {
			return array();
		}

		$activity_args = array(
			'post_id' => $post_id,
			'type'    => 'sensei_course_status',
			'status'  => 'any',
		);

		$learners = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		if ( ! is_array( $learners ) ) {
			$learners = array( $learners );
		}

		$learner_ids = wp_list_pluck( $learners, 'user_id' );

		return $learner_ids;
	}

	/**
	 * Get all users.
	 *
	 * @param array $args
	 *
	 * @deprecated 3.0.0
	 */
	public static function get_all( $args ) {
		_deprecated_function( __METHOD__, '3.0.0' );

		$post_id  = 0;
		$activity = '';

		if ( isset( $args['lesson_id'] ) ) {
			$post_id  = intval( $args['lesson_id'] );
			$activity = 'sensei_lesson_status';
		} elseif ( isset( $args['course_id'] ) ) {
			$post_id  = intval( $args['course_id'] );
			$activity = 'sensei_course_status';
		}

		if ( ! $post_id || ! $activity ) {
			return array();
		}

		$activity_args = array(
			'post_id' => $post_id,
			'type'    => $activity,
			'status'  => 'any',
			'number'  => $args['per_page'],
			'offset'  => $args['offset'],
			'orderby' => $args['orderby'],
			'order'   => $args['order'],
		);

		// Searching users on statuses requires sub-selecting the statuses by user_ids
		if ( $args['search'] ) {
			$user_args = array(
				'search' => '*' . $args['search'] . '*',
				'fields' => 'ID',
			);
			// Filter for extending
			$user_args = apply_filters( 'sensei_learners_search_users', $user_args );
			if ( ! empty( $user_args ) ) {
				$learners_search          = new WP_User_Query( $user_args );
				$activity_args['user_id'] = $learners_search->get_results();
			}
		}

		$activity_args = apply_filters( 'sensei_learners_filter_users', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
		$total_learners = Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$activity_args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);
		// Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
		if ( $total_learners < $activity_args['offset'] ) {
			$new_paged               = floor( $total_learners / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];
		}
		$learners = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		// Need to always return an array, even with only 1 item
		if ( ! is_array( $learners ) ) {
			$learners = array( $learners );
		}

		return $learners;
	} // End get_learners()

	/**
	 * Get learner from Query Var
	 *
	 * @param string $query_var The Query var.
	 * @return WP_User|false
	 */
	public static function find_by_query_var( $query_var ) {
		if ( empty( $query_var ) ) {
			return false;
		}
		if ( false !== filter_var( $query_var, FILTER_VALIDATE_INT ) ) {
			return get_user_by( 'id', $query_var ); // get requested learner object by id
		}

		if ( false !== filter_var( $query_var, FILTER_VALIDATE_EMAIL ) ) {
			return get_user_by( 'email', $query_var ); // get requested learner object by email
		}

		$by_slug = get_user_by( 'slug', $query_var );
		if ( false !== $by_slug ) {
			return $by_slug;
		}

		return get_user_by( 'login', $query_var );
	}
}
