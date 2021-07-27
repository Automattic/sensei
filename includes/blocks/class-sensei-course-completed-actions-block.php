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
		$this->register_block();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @access private
	 */
	public function enqueue_scripts() {
		wp_localize_script(
			'sensei-single-page-blocks',
			'sensei_course_completed_actions',
			[
				'course_archive_page_url' => Sensei_Course::get_courses_page_url(),
				'feature_flag'            => Sensei()->feature_flags->is_enabled( 'course_completed_page' ),
			]
		);
	}

	/**
	 * Register the block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-completed-actions',
			[],
			Sensei()->assets->src_path( 'blocks/course-completed-actions' )
		);
	}
}
