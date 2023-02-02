<?php
/**
 * File containing the Sensei_Course_List_Featured_Filter class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_List_Featured_Filter
 */
class Sensei_Course_List_Featured_Filter extends Sensei_Course_List_Filter_Abstract {

	/**
	 * Name of the filter.
	 */
	const FILTER_NAME = 'featured';

	/**
	 * Unique key for the filter param.
	 *
	 * @var string
	 */
	const PARAM_KEY = 'course-list-featured-filter-';

	/**
	 * Options for featured filter.
	 *
	 * @var array
	 */
	private $featured_options = [];

	/**
	 * Constructor for Sensei_Course_List_Featured_Filter class.
	 */
	public function __construct() {
		$this->featured_options = [
			'all'      => __( 'All Courses', 'sensei-lms' ),
			'featured' => __( 'Featured', 'sensei-lms' ),
		];
	}

	/**
	 * Get the content to be be rendered inside the filtered block.
	 *
	 * @param WP_Block $block The block instance.
	 */
	public function get_content( WP_Block $block ) : string {
		$attributes       = $block->attributes;
		$query_id         = $block->context['queryId'];
		$is_inherited     = $block->context['query']['inherit'] ?? false;
		$filter_param_key = $is_inherited ? 'course_filter' : self::PARAM_KEY . $query_id;
		$default_option   = $attributes['defaultOptions']['featured'] ?? 'all';
		$selected_option  = isset( $_GET[ $filter_param_key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $filter_param_key ] ) ) : $default_option; // phpcs:ignore WordPress.Security.NonceVerification -- Argument is used to filter courses.

		return '<select data-param-key="' . esc_attr( $filter_param_key ) . '">' .
			join(
				'',
				array_map(
					function ( $key ) use ( $selected_option ) {
						return '<option ' . selected( $key, $selected_option, false ) . ' value="' . esc_attr( $key ) . '">' . esc_html( $this->featured_options[ $key ] ) . '</option>';
					},
					array_keys( $this->featured_options )
				)
			) . '</select>';
	}

	/**
	 * Get a list of course Ids to be excluded from the course list block filtered by Featured status.
	 *
	 * @param int $query_id The id of the Query block this filter is rendering inside.
	 */
	public function get_course_ids_to_be_excluded( $query_id ): array {
		$filter_param_key = self::PARAM_KEY . $query_id;

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_GET[ $filter_param_key ] ) ) {
			return [];
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		$selected_option = sanitize_text_field( wp_unslash( $_GET[ $filter_param_key ] ) );

		if ( 'all' === $selected_option || ! in_array( $selected_option, array_keys( $this->featured_options ), true ) ) {
			return [];
		}

		$args = array(
			'post_type'      => 'course',
			'posts_per_page' => -1,
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				[
					'key'     => '_course_featured',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_course_featured',
					'value'   => 'featured',
					'compare' => '!=',
				],
			],
			'fields'         => 'ids',
		);

		return get_posts( $args );
	}
}
