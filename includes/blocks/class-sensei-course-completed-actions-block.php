<?php
/**
 * File containing the Sensei_Course_Completed_Actions_Block class.
 *
 * @package sensei
 * @since 3.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Completed_Actions_Block
 */
class Sensei_Course_Completed_Actions_Block {

	/**
	 * Sensei_Course_Completed_Actions_Block constructor.
	 */
	public function __construct() {
		add_filter( 'render_block', [ $this, 'render_block' ], 10, 2 );
	}

	/**
	 * Render the block.
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string Block HTML.
	 */
	public function render_block( $block_content, $block ): string {
		if ( ! isset( $block['blockName'] ) || 'core/buttons' !== $block['blockName'] ) {
			return $block_content;
		}

		$dom = new DomDocument();
		$dom->loadHTML( $block_content );

		$xpath       = new DomXPath( $dom );
		$nodes       = $xpath->query( '//div' );
		$parent_node = $nodes->length > 0 ? $nodes[0] : '';

		if ( ! $parent_node ) {
			return $block_content;
		}

		// Locate 'Find More Courses' button using its CSS class.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		foreach ( $parent_node->childNodes as $node ) {
			if ( ! $node->hasAttributes() ) {
				continue;
			}

			foreach ( $node->attributes as $attribute ) {
				if ( ( 'class' === $attribute->name ) && ( false !== strpos( $attribute->value, 'find-courses' ) ) ) {
					$anchor_node = $node->getElementsByTagName( 'a' )->length > 0 ? $node->getElementsByTagName( 'a' )[0] : '';

					// Open the course archive page when the button is clicked.
					if ( $anchor_node ) {
						$course_archive_page_url = Sensei_Course::get_courses_page_url();

						if ( $course_archive_page_url ) {
							$anchor_node->setAttribute( 'href', $course_archive_page_url );
						}
					}

					break 2;
				}
			}
		}

		return $dom->saveHTML( $parent_node );
	}
}
