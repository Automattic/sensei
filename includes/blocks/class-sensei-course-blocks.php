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
	 * Sensei_Blocks constructor .
	 *
	 * @param Sensei_Main $sensei
	 */
	public function __construct( $sensei ) {

		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );

		// Init blocks.
		if ( $sensei->feature_flags->is_enabled( 'course_outline' ) ) {
			$this->outline         = new Sensei_Course_Outline_Block();
			$this->progress        = new Sensei_Course_Progress_Block();
			$this->contact_teacher = new Sensei_Block_Contact_Teacher();
			$this->take_course     = new Sensei_Block_Take_Course();
		}
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

		if ( ! is_admin() ) {
			Sensei()->assets->enqueue( 'sensei-single-course-frontend', 'blocks/course-outline/frontend.js' );
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

		Sensei()->assets->enqueue( 'sensei-blocks', 'blocks/index.js', [], true );
		Sensei()->assets->enqueue( 'sensei-single-course-editor', 'blocks/single-course.editor.css' );
	}

}
