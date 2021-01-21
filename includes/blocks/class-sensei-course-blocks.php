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
class Sensei_Course_Blocks {
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
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_filter( 'sensei_use_sensei_template', [ 'Sensei_Course_Blocks', 'skip_single_course_template' ] );
		add_action( 'template_redirect', [ $this, 'maybe_initialize_blocks' ] );
		add_action( 'current_screen', [ $this, 'maybe_initialize_blocks' ] );
	}

	/**
	 * Check if course blocks should be initialized and do initialization.
	 *
	 * @access private
	 */
	public function maybe_initialize_blocks() {
		if ( is_admin() ) {
			$screen = get_current_screen();

			if ( ! $screen->is_block_editor || 'course' !== $screen->post_type ) {
				return;
			}
		} elseif ( 'course' !== get_post_type() ) {
			return;
		}

		$this->initialize_blocks();
	}

	/**
	 * Initialize blocks that are used in course pages.
	 */
	public function initialize_blocks() {
		$this->outline         = new Sensei_Course_Outline_Block();
		$this->progress        = new Sensei_Course_Progress_Block();
		$this->contact_teacher = new Sensei_Block_Contact_Teacher();
		$this->take_course     = new Sensei_Block_Take_Course();
		new Sensei_Restricted_Content_Block();

		$post_type_object = get_post_type_object( 'course' );

		$post_type_object->template = [
			[ 'sensei-lms/button-take-course' ],
			[ 'sensei-lms/button-contact-teacher' ],
			[ 'sensei-lms/course-progress' ],
			[ 'sensei-lms/course-outline' ],
		];
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-single-course', 'blocks/single-course.css' );
		Sensei()->assets->enqueue( 'sensei-shared-blocks-style', 'blocks/shared-blocks-style.css' );

		if ( ! is_admin() ) {
			Sensei()->assets->enqueue( 'sensei-shared-blocks-frontend', 'blocks/shared-blocks-frontend.js' );
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-single-course-blocks', 'blocks/sensei-single-course-blocks.js', [], true );
		Sensei()->assets->enqueue( 'sensei-single-course-editor', 'blocks/single-course.editor.css' );

		Sensei()->assets->enqueue( 'sensei-shared-blocks', 'blocks/shared-blocks.js', [], true );
		Sensei()->assets->enqueue( 'sensei-shared-blocks-editor-style', 'blocks/shared-blocks-style.editor.css' );
	}

	/**
	 * Disable single course template if there is an outline block present.
	 *
	 * @access private
	 *
	 * @param bool $enabled
	 *
	 * @return bool
	 */
	public static function skip_single_course_template( $enabled ) {
		return is_single() && 'course' === get_post_type() && ! Sensei()->course->is_legacy_course( get_post() )
			? false
			: $enabled;
	}

}
