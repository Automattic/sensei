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
	 * Instance of Sensei_Course_List_Categories_Filter class.
	 *
	 * @var Sensei_Course_List_Categories_Filter
	 */
	private $category_filter;

	/**
	 * Sensei_Course_List_Filter_Block constructor.
	 */
	public function __construct() {
		$this->register_block();

		add_filter( 'render_block_data', [ $this, 'filter_course_list' ] );

		$this->category_filter = new Sensei_Course_List_Categories_Filter();
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
		if ( ! isset( $attributes['type'] ) ) {
			return '';
		}
		$content = '';

		switch ( $attributes['type'] ) {
			case 'categories':
				$content = $this->category_filter->get_content( $block->context['queryId'] );
				break;
			case 'featured':
			case 'activity':
			default:
				break;
		}
		return '<div class="wp-sensei-course-list-block-filter">' . $content . '</div>';
	}

	/**
	 * Filter the course list block.
	 *
	 * @param array $parsed_block The block to be rendered.
	 *
	 * @return array
	 */
	public function filter_course_list( $parsed_block ) {
		if ( 'core/query' !== $parsed_block['blockName'] || 'course' !== $parsed_block['attrs']['query']['postType'] ) {
			return $parsed_block;
		}
		$category_filtered_ids = $this->category_filter->get_course_ids_to_be_excluded( $parsed_block['attrs']['queryId'] );

		if ( ! array_key_exists( 'exclude', $parsed_block['attrs']['query'] ) || ! is_array( $parsed_block['attrs']['query']['exclude'] ) ) {
			$parsed_block['attrs']['query']['exclude'] = [];
		}
		// All the filtered course ids that need to be excluded are merged here.
		// We are changing updating the attribute of the parent Query Loop block here
		// Which will be provided to all the children using context. So no need update each child's (pagination, next page etc.) context
		// separately.
		$parsed_block['attrs']['query']['exclude'] = array_merge( $parsed_block['attrs']['query']['exclude'], $category_filtered_ids );
		return $parsed_block;
	}
}
