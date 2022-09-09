<?php
/**
 * File containing the Sensei_Course_List_Categories_Filter class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_List_Categories_Filter
 */
class Sensei_Course_List_Categories_Filter extends Sensei_Course_List_Filter_Abstract {

	/**
	 * Name of the filter.
	 */
	const FILTER_NAME = 'categories';

	/**
	 * Unique key for the filter param.
	 *
	 * @var string
	 */
	private $param_key = 'course-list-category-filter-';

	/**
	 * Get the content to be be rendered inside the filtered block.
	 *
	 * @param int $query_id The id of the Query block this filter is rendering inside.
	 */
	public function get_content( $query_id ) : string {
		$filter_param_key  = $this->param_key . $query_id;
		$category_id       = isset( $_GET[ $filter_param_key ] ) ? intval( $_GET[ $filter_param_key ] ) : -1; // phpcs:ignore WordPress.Security.NonceVerification -- Argument is used to filter courses.
		$course_categories = get_terms(
			[
				'taxonomy'   => 'course-category',
				'hide_empty' => true,
			]
		);

		return '<select data-param-key="' . esc_attr( $filter_param_key ) . '">
			<option value="-1">' . esc_html__( 'All Categories', 'sensei-lms' ) . '</option>' .
			join(
				'',
				array_map(
					function ( $category ) use ( $category_id ) {
						return '<option ' . selected( $category_id, $category->term_id, false ) . ' value="' . esc_attr( $category->term_id ) . '">' . esc_html( $category->name ) . '</option>';
					},
					$course_categories
				)
			) . '</select>';
	}

	/**
	 * Get a list of course Ids to be excluded from the course list block filtered by Course Category.
	 *
	 * @param int $query_id The id of the Query block this filter is rendering inside.
	 */
	public function get_course_ids_to_be_excluded( $query_id ): array {
		$filter_param_key = $this->param_key . $query_id;

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_GET[ $filter_param_key ] ) ) {
			return [];
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		$category_id = intval( $_GET[ $filter_param_key ] );

		$course_categories = get_terms( 'course-category', [ 'fields' => 'ids' ] );

		if ( ! is_array( $course_categories ) || ! in_array( $category_id, $course_categories, true ) ) {
			return [];
		}
		$tax_query = array(
			array(
				'taxonomy' => 'course-category',
				'field'    => 'term_id',
				'terms'    => [ $category_id ],
				'operator' => 'NOT IN',
			),
		);
		$args      = array(
			'post_type'      => 'course',
			'posts_per_page' => -1,
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
		);

		return get_posts( $args );
	}
}
