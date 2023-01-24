<?php
/**
 * File containing the Sensei_Course_List_Student_Course_Filter class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_List_Student_Course_Filter
 */
class Sensei_Course_List_Student_Course_Filter extends Sensei_Course_List_Filter_Abstract {

	/**
	 * Name of the filter.
	 */
	const FILTER_NAME = 'student_course';

	/**
	 * Unique key for the filter param.
	 *
	 * @var string
	 */
	const PARAM_KEY = 'course-list-student-course-filter-';

	/**
	 * Options for filter dropdown.
	 *
	 * @var array
	 */
	private $filter_options = [];

	/**
	 * Constructor for Sensei_Course_List_Student_Course_Filter class.
	 */
	public function __construct() {
		$this->filter_options = [
			'all'       => __( 'All Courses', 'sensei-lms' ),
			'active'    => __( 'Active', 'sensei-lms' ),
			'completed' => __( 'Completed', 'sensei-lms' ),
		];
	}
	/**
	 * Get the content to be rendered inside the filtered block.
	 *
	 * @param WP_Block $block The block instance.
	 */
	public function get_content( WP_Block $block ) : string {
		if ( empty( get_current_user_id() ) ) {
			return '';
		}

		$attributes       = $block->attributes;
		$query_id         = $block->context['queryId'];
		$is_inherited     = $block->context['query']['inherit'] ?? false;
		$filter_param_key = $is_inherited ? 'student_course_filter' : self::PARAM_KEY . $query_id;
		$default_option   = $attributes['defaultOptions']['student_course'] ?? 'all';
		$selected_option  = isset( $_GET[ $filter_param_key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $filter_param_key ] ) ) : $default_option; // phpcs:ignore WordPress.Security.NonceVerification -- Argument is used to filter courses.

		return '<select data-param-key="' . esc_attr( $filter_param_key ) . '">' .
			join(
				'',
				array_map(
					function ( $key ) use ( $selected_option ) {
						return '<option ' . selected( $key, $selected_option, false ) . ' value="' . esc_attr( $key ) . '">' . esc_html( $this->filter_options[ $key ] ) . '</option>';
					},
					array_keys( $this->filter_options )
				)
			) . '</select>';
	}

	/**
	 * Get a list of course Ids to be excluded from the course list block filtered by user's course status.
	 *
	 * @param int $query_id The id of the Query block this filter is rendering inside.
	 */
	public function get_course_ids_to_be_excluded( $query_id ): array {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return [];
		}

		$filter_param_key = self::PARAM_KEY . $query_id;

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_GET[ $filter_param_key ] ) ) {
			return [];
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		$selected_option = sanitize_text_field( wp_unslash( $_GET[ $filter_param_key ] ) );

		if ( 'all' === $selected_option || ! in_array( $selected_option, array_keys( $this->filter_options ), true ) ) {
			return [];
		}

		$args           = array(
			'post_type'      => 'course',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		$all_course_ids = get_posts( $args );

		$args = [
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		$included_course_ids = [];
		$learner_manager     = Sensei_Learner::instance();

		switch ( $selected_option ) {
			case 'active':
				$courses_query       = $learner_manager->get_enrolled_active_courses_query( $user_id, $args );
				$included_course_ids = $courses_query->posts;
				break;
			case 'completed':
				$courses_query       = $learner_manager->get_enrolled_completed_courses_query( $user_id, $args );
				$included_course_ids = $courses_query->posts;
				break;
		}

		return array_diff( $all_course_ids, $included_course_ids );
	}
}
