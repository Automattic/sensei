<?php
/**
 * File containing the class Sensei_Course_Blocks.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Blocks
 */
class Sensei_Course_Blocks extends Sensei_Blocks_Initializer {
	/**
	 * Course outline block.
	 *
	 * @var Sensei_Course_Outline_Block
	 */
	public $outline;

	/**
	 * Course progress block.
	 *
	 * @var Sensei_Course_Progress_Block
	 */
	public $progress;

	/**
	 * Course progress block.
	 *
	 * @var Sensei_Block_Contact_Teacher
	 */
	public $contact_teacher;

	/**
	 * Take course block.
	 *
	 * @var Sensei_Block_Take_Course
	 */
	public $take_course;

	/**
	 * Sensei_Course_Blocks constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'course' ] );
		add_filter( 'sensei_use_sensei_template', [ 'Sensei_Course_Blocks', 'skip_single_course_template' ] );

	}

	/**
	 * Initialize blocks that are used in course pages.
	 */
	public function initialize_blocks() {
		$this->outline         = new Sensei_Course_Outline_Block();
		$this->progress        = new Sensei_Course_Progress_Block();
		$this->contact_teacher = new Sensei_Block_Contact_Teacher();
		$this->take_course     = new Sensei_Block_Take_Course();
		new Sensei_Conditional_Content_Block();
		new Sensei_Block_View_Results();

		$post_type_object = get_post_type_object( 'course' );

		$block_template = [
			[ 'sensei-lms/button-take-course' ],
			[ 'sensei-lms/button-contact-teacher' ],
			[ 'sensei-lms/course-progress' ],
			[ 'sensei-lms/course-outline' ],
		];

		/**
		 * Customize the course block template.
		 *
		 * @hook  sensei_course_block_template
		 * @since 3.9.0
		 *
		 * @param {string[][]} $template          Array of blocks to use as the default initial state for a course.
		 * @param {string[][]} $original_template Original block template.
		 *
		 * @return {string[][]} Array of blocks to use as the default initial state for a course.
		 */
		$post_type_object->template = apply_filters( 'sensei_course_block_template', $block_template, $post_type_object->template ?? [] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {

		Sensei()->assets->enqueue(
			'sensei-single-course-blocks-style',
			'blocks/single-course-style.css',
			[ 'sensei-shared-blocks-style' ]
		);

		if ( ! is_admin() ) {
			Sensei()->assets->enqueue_script( 'sensei-blocks-frontend' );
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {

		Sensei()->assets->enqueue(
			'sensei-single-course-blocks',
			'blocks/single-course.js',
			[ 'sensei-shared-blocks' ],
			true
		);
		Sensei()->assets->enqueue(
			'sensei-single-course-blocks-editor-style',
			'blocks/single-course-style-editor.css',
			[ 'sensei-shared-blocks-editor-style', 'sensei-editor-components-style' ]
		);

		global $post;
		if ( null !== $post ) {
			Sensei()->assets->preload_data( [ sprintf( '/sensei-internal/v1/course-structure/%d?context=edit', $post->ID ) ] );
		}

	}

	/**
	 * Disable single course template if the course is block based.
	 *
	 * @access private
	 *
	 * @param bool $enabled
	 *
	 * @return bool
	 */
	public static function skip_single_course_template( $enabled ) {
		return is_single() && 'course' === get_post_type() && Sensei()->course->has_sensei_blocks( get_post() )
			? false
			: $enabled;
	}

}
