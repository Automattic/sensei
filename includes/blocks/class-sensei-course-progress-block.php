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
		if ( ! Sensei()->course::is_user_enrolled( get_the_ID() ) ) {
			return '';
		}

		$completed     = count( Sensei()->course->get_completed_lesson_ids( get_the_ID() ) );
		$total_lessons = count( Sensei()->course->course_lessons( get_the_ID() ) );
		$percentage    = Sensei_Utils::quotient_as_absolute_rounded_percentage( $completed, $total_lessons, 2 );

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

		$bar_css                    = Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor' => null,
				'barColor'  => 'background-color',
			],
			[ 'borderRadius' => 'border-radius' ]
		);
		$bar_css['inline_styles'][] = 'width: ' . ( 3 > $percentage ? 3 : $percentage ) . '%';

		// translators: Placeholder %d is the lesson count.
		$lessons_text = sprintf( _n( '%d Lesson', '%d Lessons', $total_lessons, 'sensei-lms' ), $total_lessons );

		// translators: Placeholders are the number and percentage of completed lessons.
		$completed_text = sprintf( __( '%1$d completed (%2$s)', 'sensei-lms' ), $completed, $percentage . '%' );

		$class_names = [ 'sensei-block-wrapper' ];

		if ( ! empty( $attributes['className'] ) ) {
			$class_names[] = $attributes['className'];
		}

		return '
			<div ' . Sensei_Block_Helpers::render_style_attributes( $class_names, $text_css ) . '>
				<section class="wp-block-sensei-lms-progress-heading">
					<div class="wp-block-sensei-lms-progress-heading__lessons">' . $lessons_text . '</div>
					<div class="wp-block-sensei-lms-progress-heading__completed">' . $completed_text . '</div>
				</section>
				<div role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-progress-bar' ], $bar_background_css ) . '>
					<div ' . Sensei_Block_Helpers::render_style_attributes( [], $bar_css ) . '></div>
				</div>
			</div>
		';
	}
}
