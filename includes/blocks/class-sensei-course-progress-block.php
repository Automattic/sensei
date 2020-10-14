<?php
/**
 * File containing the Sensei_Course_Progress_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Progress_Block
 */
class Sensei_Course_Progress_Block {

	/**
	 * Sensei_Course_Progress_Block constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register course progress block.
	 *
	 * @access private
	 */
	public function register_block() {
		register_block_type_from_metadata(
			Sensei()->assets->src_path( 'blocks/course-progress' ),
			[
				'render_callback' => [ $this, 'render_course_progress' ],
			]
		);
	}

	/**
	 * Renders the course progress block in the frontend.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return string The HTML of the block.
	 */
	public function render_course_progress( $attributes ) : string {

		$text_css                   = Sensei_Block_Helpers::build_styles( $attributes );
		$bar_background_css         = Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor'          => null,
				'barBackgroundColor' => 'background-color',
			]
		);
		$bar_css                    = Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor' => null,
				'barColor'  => 'background-color',
			]
		);
		$bar_css['inline_styles'][] = 'width: 60%';

		return '
			<div ' . Sensei_Block_Helpers::render_style_attributes( $attributes['className'] ?? [], $text_css ) . '>
				<section class="wp-block-sensei-lms-progress-heading">
					<div class="wp-block-sensei-lms-progress-heading__lessons">5 Lessons</div>
					<div class="wp-block-sensei-lms-progress-heading__completed">3 completed (60%)</div>
				</section>
				<div ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-progress-bar' ], $bar_background_css ) . '>
					<div ' . Sensei_Block_Helpers::render_style_attributes( [], $bar_css ) . '></div>
				</div>
			</div>
		';
	}
}
