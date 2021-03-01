<?php
/**
 * All functionality pertaining to displaying of various notices on the frontend.
 *
 * @package Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Notices Class
 *
 * All functionality pertaining to displaying of various notices on the frontend.
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.6.3
 */
class Sensei_Notices {

	/**
	 * Notices.
	 *
	 *  @var $notices
	 */
	protected $notices;

	/**
	 * Has Printed.
	 *
	 * @var boolean $has_printed
	 */
	protected $has_printed;

	/**
	 * The HTML allowed for message boxes.
	 *
	 * @var array The HTML allowed for message boxes.
	 */
	protected $allowed_html;

	/**
	 * Constructor
	 */
	public function __construct() {
		// initialize the notices variable.
		$this->notices      = array();
		$this->has_printed  = false;
		$this->allowed_html = array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'embed'  => array(),
				'iframe' => array(
					'width'           => array(),
					'height'          => array(),
					'src'             => array(),
					'frameborder'     => array(),
					'allowfullscreen' => array(),
				),
			)
		);

		add_action( 'template_redirect', [ $this, 'setup_block_notices' ] );
	}

	/**
	 *  Add a notice to the array of notices for display at a later stage.
	 *
	 * @param string $content Content.
	 * @param string $type    Defaults to alert options( alert, tick , download , info   ).
	 * @param string $key     Notices with the same key will be overwritten.
	 *
	 * @return void
	 */
	public function add_notice( string $content, string $type = 'alert', string $key = null ) {
		// append the new notice.
		if ( null === $key ) {
			$this->notices[] = [
				'content' => $content,
				'type'    => $type,
			];
		} else {
			$this->notices[ $key ] = [
				'content' => $content,
				'type'    => $type,
			];
		}

		// if a notice is added after we've printed print it immediately.
		if ( $this->has_printed ) {
			$this->maybe_print_notices();
		}
	}

	/**
	 * Output all notices added
	 *
	 * @return void
	 */
	public function maybe_print_notices() {
		if ( count( $this->notices ) > 0 ) {
			foreach ( $this->notices  as  $notice ) {

				$classes = 'sensei-message ' . $notice['type'];

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in generate_notice
				echo $this->generate_notice( $notice['type'], $notice['content'] );
			}
			// empty the notice queue to avoid reprinting the same notices.
			$this->clear_notices();

		}

		// set this to print immediately if notices are added after the notices were printed.
		$this->has_printed = true;
	}

	/**
	 * Adds a filter to the content to add notices added by blocks.
	 */
	public function setup_block_notices() {
		if ( is_singular( 'course' ) && Sensei()->course->has_sensei_blocks( get_post() ) ) {
			add_filter( 'the_content', [ $this, 'prepend_notices_to_content' ] );
		}
	}

	/**
	 * Adds the notices before main content.
	 *
	 * @param string $content The post content.
	 *
	 * @access private
	 *
	 * @return string The modified content.
	 */
	public function prepend_notices_to_content( string $content ) : string {
		if ( in_the_loop() && is_main_query() ) {

			ob_start();
			$this->maybe_print_notices();
			$notice = ob_get_clean();

			return $notice . $content;
		}

		return $content;
	}

	/**
	 * Helper method which generates the HTML for a notice.
	 *
	 * @param string $type    Notice type.
	 * @param string $content Notice content.
	 *
	 * @return string The HTML
	 */
	public function generate_notice( string $type, string $content ) : string {
		$classes = 'sensei-message ' . $type;

		return '<div class="' . esc_attr( $classes ) . '">' . wp_kses( $content, $this->allowed_html ) . '</div>';
	}

	/**
	 *  Clear all notices
	 *
	 * @return void
	 */
	public function clear_notices() {
		// assign an empty array to clear all existing notices.
		$this->notices = array();
	}
}

/**
 * Class Woothemes_Sensei_Notices
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class Woothemes_Sensei_Notices extends Sensei_Notices{}
