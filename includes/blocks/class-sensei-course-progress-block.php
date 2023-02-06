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
	public function render_course_progress( $attributes ): string {

		$course_id = $attributes['postId'] ?? get_the_ID();

		if ( 'course' !== get_post_type( $course_id ) ) {
			return '';
		}

		if ( ! Sensei()->course::is_user_enrolled( $course_id ) ) {
			return '';
		}

		$stats         = Sensei()->course->get_progress_stats( $course_id );
		$total_lessons = $stats['lessons_count'];
		$completed     = $stats['completed_lessons_count'];
		$percentage    = $stats['completed_lessons_percentage'];

		// translators: %1$d number of lessons completed, %2$d number of total lessons, %3$s percentage.
		$attributes['label']      = sprintf( __( '%1$d of %2$d lessons completed (%3$s)', 'sensei-lms' ), $completed, $total_lessons, floor( $percentage ) . '%' );
		$attributes['percentage'] = $percentage;

		return \Sensei\Blocks\Shared\Progress_Bar::render( $attributes );
	}
}
