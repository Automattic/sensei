<?php
/**
 * File containing the Sensei_Course_List_Filter_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_List_Filter_Block
 */
class Sensei_Course_List_Filter_Block {

	/**
	 * List of filter class instances.
	 *
	 * @var Sensei_Course_List_Filter_Abstract[]
	 */
	private $filters;

	/**
	 * Sensei_Course_List_Filter_Block constructor.
	 */
	public function __construct() {
		$this->register_block();

		add_filter( 'render_block_data', [ $this, 'filter_course_list' ] );
		add_action( 'sensei_archive_before_course_loop', [ $this, 'maybe_remove_extra_filters_and_sorting_from_archive_page' ], 9 );

		$this->filters = [
			new Sensei_Course_List_Categories_Filter(),
			new Sensei_Course_List_Featured_Filter(),
			new Sensei_Course_List_Student_Course_Filter(),
		];
	}

	/**
	 * Remove extra filters and sorting from archive page if the Course List block is being used.
	 *
	 * @since 4.11.0
	 * @access private
	 * @hooked sensei_archive_before_course_loop
	 *
	 * @return void
	 */
	public function maybe_remove_extra_filters_and_sorting_from_archive_page(): void {
		if ( Sensei()->course->course_archive_page_has_query_block() ) {
			remove_action( 'sensei_archive_before_course_loop', [ 'Sensei_Course', 'course_archive_sorting' ] );
			remove_action( 'sensei_archive_before_course_loop', [ 'Sensei_Course', 'course_archive_filters' ] );
		}
	}

	/**
	 * Register Sensei_Course_List_Filter_Block block.
	 */
	private function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-list-filter',
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
		if (
			! isset( $attributes['types'] ) ||
			! is_array( $attributes['types'] ) ||
			! isset( $block->context['queryId'] ) ||
			'course' !== ( $block->context['query']['postType'] ?? '' )
		) {
			return '';
		}
		$content = '';

		foreach ( $this->filters as $filter ) {
			if ( in_array( $filter::FILTER_NAME, $attributes['types'], true ) ) {
				$content .= $filter->get_content( $block );
			}
		}

		$wrapper_attributes = get_block_wrapper_attributes();

		if ( empty( $content ) ) {
			return '';
		}

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$content
		);
	}

	/**
	 * Filter the course list block.
	 *
	 * @param array $parsed_block The block to be rendered.
	 *
	 * @return array
	 */
	public function filter_course_list( $parsed_block ) {
		if (
			'core/query' !== $parsed_block['blockName'] ||
			'course' !== ( $parsed_block['attrs']['query']['postType'] ?? '' ) ||
			! isset( $parsed_block['attrs']['queryId'] )
		) {
			return $parsed_block;
		}

		if ( ! array_key_exists( 'exclude', $parsed_block['attrs']['query'] ) || ! is_array( $parsed_block['attrs']['query']['exclude'] ) ) {
			$parsed_block['attrs']['query']['exclude'] = [];
		}

		$default_options = $this->recursively_get_default_option_value_from_inner_blocks( $parsed_block['innerBlocks'] ) ?? [];

		// All the filtered course ids that need to be excluded are merged here.
		// We are changing updating the attribute of the parent Query Loop block here
		// Which will be provided to all the children using context. So no need update each child's (pagination, next page etc.) context
		// separately.
		$parsed_block['attrs']['query']['exclude'] = array_merge(
			$parsed_block['attrs']['query']['exclude'],
			...array_map(
				function ( $filter ) use ( $parsed_block, $default_options ) {
					$filter->set_default_option_as_filter_query_param( $parsed_block['attrs']['queryId'], $default_options );
					return $filter->get_course_ids_to_be_excluded( $parsed_block['attrs']['queryId'] );
				},
				$this->filters
			)
		);
		return $parsed_block;
	}

	/**
	 * Get the defaultOptions value from inner blocks.
	 *
	 * @param mixed $inner_blocks The inner blocks of the parsed block.
	 *
	 * @return array|null The default options.
	 */
	private function recursively_get_default_option_value_from_inner_blocks( $inner_blocks ) {
		$default_options = null;

		if ( ! is_array( $inner_blocks ) ) {
			return $default_options;
		}

		foreach ( $inner_blocks as $inner_block ) {
			if ( 'sensei-lms/course-list-filter' === $inner_block['blockName'] ) {
				$default_options = $inner_block['attrs']['defaultOptions'] ?? [];
			} else {
				$default_options = $this->recursively_get_default_option_value_from_inner_blocks( $inner_block['innerBlocks'] );
			}

			if ( null !== $default_options ) {
				break;
			}
		}
		return $default_options;
	}
}
