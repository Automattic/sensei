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
		$this->register_block();
	}

	/**
	 * Register course progress block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-progress',
			[
				'render_callback' => [ $this, 'render_course_progress' ],
			],
			Sensei()->assets->src_path( 'blocks/course-progress-block' )
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

		$course_id = $attributes['postId'] ?? get_the_ID();

		if ( ! Sensei()->course::is_user_enrolled( $course_id ) ) {
			return '';
		}

		$stats         = Sensei()->course->get_progress_stats( $course_id );
		$total_lessons = $stats['lessons_count'];
		$completed     = $stats['completed_lessons_count'];
		$percentage    = $stats['completed_lessons_percentage'];

		$text_css           = Sensei_Block_Helpers::build_styles( $attributes );
		$bar_background_css = Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor'          => null,
				'barBackgroundColor' => 'background-color',
			],
			[
				'height'       => 'height',
				'borderRadius' => 'border-radius',
			]
		);

		$bar_css = Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor' => null,
				'barColor'  => 'background-color',
			]
		);

		$bar_css['inline_styles'][] = 'width: ' . $percentage . '%';

		// translators: %1$d number of lessons completed, %2$d number of total lessons, %3$s percentage.
		$progress_bar_text = sprintf( __( '%1$d of %2$d lessons completed (%3$s)', 'sensei-lms' ), $completed, $total_lessons, $percentage . '%' );

		$class_names = [ 'sensei-block-wrapper' ];

		if ( ! empty( $attributes['className'] ) ) {
			$class_names[] = $attributes['className'];
		}

		return '
			<div ' . Sensei_Block_Helpers::render_style_attributes( $class_names, $text_css ) . '>
				<section class="wp-block-sensei-lms-progress-heading sensei-progress-bar__heading">
					<div class="wp-block-sensei-lms-progress-heading__completed sensei-progress-bar__completed">' . $progress_bar_text . '</div>
				</section>
				<div role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-course-progress', 'sensei-progress-bar__bar' ], $bar_background_css ) . '>
					<div ' . Sensei_Block_Helpers::render_style_attributes( [], $bar_css ) . '></div>
				</div>
			</div>
		';
	}
}
