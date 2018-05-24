<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
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
	 * Setup the shortcode object
	 *
	 * @since 1.9.0
	 * @param array $attributes
	 * @param string $content
	 * @param string $shortcode the shortcode that was called for this instance
	 */
	public function __construct( $attributes, $content, $shortcode ){
		$this->renderer = new Sensei_Renderer_Single_Course( $attributes );
	}

	/**
	 * Rendering the shortcode this class is responsible for.
	 *
	 * @return string $content
	 */
	public function render() {
		try {
			return $this->renderer->render();
		} catch ( Sensei_Renderer_Missing_Fields_Exception $e ) {
			return sprintf( __( 'Error rendering %s shortcode - %s', 'woothemes-sensei' ), '[sensei_course_page]', $e->getMessage() );
		}
	}

}
