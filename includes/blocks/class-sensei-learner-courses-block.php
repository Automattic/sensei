<?php
/**
 * File containing the Sensei_Learner_Courses_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Learner_Courses_Block
 */
class Sensei_Learner_Courses_Block {
	/**
	 * User courses helper.
	 *
	 * @var Sensei_Shortcode_User_Courses
	 */
	private $user_courses;

	/**
	 * Sensei_Learner_Courses_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/learner-courses',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/learner-courses-block' )
		);
	}

	/**
	 * Renders learner courses block in the frontend.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The inner block content.
	 *
	 * @return string The HTML of the block.
	 */
	public function render( $attributes, $content ): string {

		$this->user_courses = new Sensei_Shortcode_User_Courses( [], null, null );
		$query              = $this->user_courses->setup_course_query();

		$tabs    = $this->render_tabs();
		$courses = array_map( [ $this, 'render_course' ], $query->posts );
		$courses = join( '', $courses );

		return '<section id="sensei-user-courses wp-block-sensei-lms-learner-courses">' . $tabs . $courses . '</section>';

	}

	/**
	 * Render course status filter tabs.
	 *
	 * @return string Tabs HTML.
	 */
	public function render_tabs() {
		ob_start();
		$this->user_courses->course_toggle_actions();
		return ob_get_clean();
	}

	/**
	 * Render a course entry.
	 *
	 * @param WP_Post $course
	 *
	 * @return string Course entry HTML.
	 */
	public function render_course( $course ) {
		$class       = 'wp-block-sensei-lms-learner-courses__courses-list';
		$title       = get_the_title( $course );
		$description = get_the_excerpt( $course );
		$thumbnail   = get_the_post_thumbnail( $course, 'thumbnail', [ 'class' => $class . '__featured-image alignleft' ] );

		ob_start();
		Sensei_Course::the_course_action_buttons( $course );
		$actions = ob_get_clean();

		return <<<HTML
<div class="{$class}__item">
			<div>
					<header class="{$class}__header">
						<h3 class="{$class}__title">
							{$title}
						</h3>
					</header>
					{$thumbnail}
					<p class="{$class}__description">
						{$description}
					</p>
					<div style="clear: both"></div>
					{$actions}
				</div>
		</div>
HTML;

	}

}
