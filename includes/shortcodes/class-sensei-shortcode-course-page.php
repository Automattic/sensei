<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 *
 * Renders the [sensei_course_page] shortcode. Display a single course based on the ID parameter given
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_Course_Page
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_Course_Page implements Sensei_Shortcode_Interface {

	/**
	 * @var int $id The ID of the course to render.
	 */
	private $id;

	/**
	 * @var Sensei_Renderer_Single_Course $renderer The renderer to use for
	 *                                              rendering the shortcode
	 *                                              content.
	 */
	private $renderer;

	/**
	 * Setup the shortcode object
	 *
	 * @since 1.9.0
	 * @param array  $attributes
	 * @param string $content
	 * @param string $shortcode the shortcode that was called for this instance
	 */
	public function __construct( $attributes, $content, $shortcode ) {
		$this->id = isset( $attributes['id'] ) ? $attributes['id'] : '';

		if ( $this->id ) {
			$this->renderer = new Sensei_Renderer_Single_Post( $this->id, 'single-course.php', $attributes );
		}
	}

	/**
	 * Rendering the shortcode this class is responsible for.
	 *
	 * @return string $content
	 */
	public function render() {
		if ( empty( $this->id ) ) {
			return sprintf(
				// translators: Placeholder is the example shortcode text.
				__( 'Please supply a course ID for the shortcode: %s', 'sensei-lms' ),
				'[sensei_course_page id=""]'
			);
		}

		try {
			// Ensure the global vars are set properly for the Lessons display.
			add_action( 'sensei_single_course_lessons_before', array( $this->renderer, 'set_global_vars' ), 1, 0 );
			return $this->renderer->render();
		} catch ( Sensei_Renderer_Missing_Fields_Exception $e ) {
			// translators: Placeholders are the shortcode name and the error message.
			return sprintf( __( 'Error rendering %1$s shortcode - %2$s', 'sensei-lms' ), '[sensei_course_page]', $e->getMessage() );
		}
	}

}
