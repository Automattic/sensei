<?php
/**
 * File containing the Course_Progress class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Course_Progress is responsible for rendering the '< Prev Lesson | Next Lesson >' blocks.
 */
class Course_Progress_Counter {
	/**
	 * Course_Progress constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-course-progress',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render() : string {
		$post   = get_post();
		$course = $post;

		if ( 'lesson' === $post->post_type ) {
			$course_id = \Sensei()->lesson->get_course_id( $post->ID );
			$course    = get_post( $course_id );
		}

		$lessons_count                = count( \Sensei()->course->course_lessons( $course->ID, null, 'ids' ) );
		$completed_lessons_count      = count( \Sensei()->course->get_completed_lesson_ids( $course->ID ) );
		$completed_lessons_percentage = \Sensei_Utils::quotient_as_absolute_rounded_percentage( $completed_lessons_count, $lessons_count, 2 );

		$output = sprintf(
			/* translators: Placeholder %1$d is the completed lessons count, %2$d is the total lessons count and %3$d is the percentage of completed lessons. */
			__( '%1$d of %2$d lessons complete (%3$d%%)', 'sensei-lms' ),
			$completed_lessons_count,
			$lessons_count,
			$completed_lessons_percentage
		);

		return ( "
			<div class='sensei-course-theme-course-progress'>
				{$output}
			</div>
		" );
	}
}
