<?php
/**
 * File containing the Sensei_Course_Categories_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_List_Featured_Filter_Block
 */
class Sensei_Course_List_Featured_Filter_Block {



	/**
	 * Rendered HTML output for the block.
	 *
	 * @var string
	 */
	private $block_content;

	/**
	 * Sensei_Course_List_Featured_Filter_Block constructor.
	 */
	public function __construct() {
		$this->register_block();
		add_filter( 'render_block_context', [ $this, 'filter_course_list_by_category' ], 10, 3 );
	}

	/**
	 * Register course Sensei_Course_List_Featured_Filter_Block block.
	 */
	private function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-list-featured-filter',
			[
				'render_callback' => [ $this, 'render_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-list-filter-block' )
		);
	}


	/**
	 * Render the Course Categories block.
	 *
	 * @param Array    $attributes The block's attributes.
	 * @param string   $content    The block's content.
	 * @param WP_Block $block      The block instance.
	 * @return string
	 */
	public function render_block( $attributes, $content, WP_Block $block ): string {
		$args             = array(
			'hide_empty' => true,
		);
		$category_id      = 0;
		$filter_param_key = 'course-filter-query-' . $block->context['queryId'] . '-category';
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET[ $filter_param_key ] ) ) {
			$category_id = intval( $_GET[ $filter_param_key ] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		$course_categories         = get_terms( 'course-category', $args );
		$category_selector_content = '<select name="course_list_category_filter" class="course_list_category_filter" data-query-id="' . $block->context['queryId'] . '" >
			<option value="">' . esc_html__( 'Select Category', 'sensei-lms' ) . '</option>' .
			join(
				'',
				array_map(
					function ( $category ) use ( $block, $category_id ) {
						return '<option ' . selected( $category_id, $category->term_id, false ) . ' value="' . esc_attr( $category->term_id ) . '">' . esc_html( $category->name ) . '</option>';
					},
					$course_categories
				)
			) . '</select>';
		return '<div>' . $category_selector_content . '</div>';
	}

	/**
	 * Filter the course list by category.
	 *
	 * @param array    $context The block's context.
	 * @param array    $parsed_block The block to be rendered.
	 * @param WP_Block $parent_block The parent block instance.
	 * @return array
	 */
	public function filter_course_list_by_category( $context, $parsed_block, $parent_block ) {
		if ( 'core/post-template' !== $parsed_block['blockName'] ) {
			return $context;
		}

		$filter_param_key = 'course-filter-query-' . $context['queryId'] . '-category';

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_GET[ $filter_param_key ] ) ) {
			return $context;
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		$category_id = intval( $_GET[ $filter_param_key ] );

		$course_categories = get_terms( 'course-category', [ 'fields' => 'ids' ] );

		if ( ! is_array( $course_categories ) || ! in_array( $category_id, $course_categories, true ) ) {
			return $context;
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

		$course_ids_not_in_category = get_posts( $args );
		if ( ! array_key_exists( 'exclude', $context['query'] ) || ! is_array( $context['query']['exclude'] ) ) {
			$context['query']['exclude'] = [];
		}
		$context['query']['exclude'] = array_merge( $context['query']['exclude'], $course_ids_not_in_category );
		return $context;
	}
}
