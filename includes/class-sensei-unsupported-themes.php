<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Themes class.
 *
 * Handles all content rendering for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Themes {

	/**
	 * Singleton instance.
	 *
	 * @var string
	 */
	private static $_instance;

	/**
	 * Whether we are handling the request.
	 *
	 * @var bool
	 */
	protected $_is_handling_request = false;

	/**
	 * Handler objects registered for handling requests.
	 *
	 * @var array
	 */
	protected $_handlers;

	/**
	 * Initialize rendering system for unsupported themes.
	 *
	 * @since 1.12.0
	 */
	public static function init() {
		$instance = self::get_instance();
		$instance->maybe_handle_request();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.12.0
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new Sensei_Unsupported_Themes();
		}
		return self::$_instance;
	}

	/**
	 * Reset the singleton instance (used for testing).
	 *
	 * @since 1.12.0
	 */
	public static function reset() {
		self::$_instance = null;
	}

	/**
	 * Private constructor.
	 *
	 * @since 1.12.0
	 */
	private function __construct() {
		// Set up registered handlers.
		$this->_handlers = array(
			new Sensei_Unsupported_Theme_Handler_CPT( 'course' ),
			new Sensei_Unsupported_Theme_Handler_CPT( 'lesson' ),
			new Sensei_Unsupported_Theme_Handler_CPT(
				'sensei_message',
				array(
					'show_pagination'   => true,
					'template_filename' => 'single-message.php',
				)
			),
			new Sensei_Unsupported_Theme_Handler_CPT( 'quiz' ),
			new Sensei_Unsupported_Theme_Handler_Module(),
			new Sensei_Unsupported_Theme_Handler_Course_Results(),
			new Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive(),
			new Sensei_Unsupported_Theme_Handler_Teacher_Archive(),
			new Sensei_Unsupported_Theme_Handler_Learner_Profile(),
			new Sensei_Unsupported_Theme_Handler_Message_Archive(),
			new Sensei_Unsupported_Theme_Handler_Course_Archive(),
		);
	}

	/**
	 * Determine whether this class is handling the rendering for this
	 * request.
	 *
	 * @since 1.12.0
	 *
	 * @return bool
	 */
	public function is_handling_request() {
		return $this->_is_handling_request;
	}

	/**
	 * Set up handling for this request if possible. If the request is
	 * handled here, sets the instance variable $_is_handling_request.
	 *
	 * @since 1.12.0
	 */
	protected function maybe_handle_request() {
		// Do nothing if this theme supports Sensei.
		if ( sensei_does_theme_support_templates() ) {
			return;
		}

		/**
		 * Filters if Sensei templates and content wrappers should be used. For development purposes.
		 *
		 * @hook   sensei_use_sensei_template
		 *
		 * @param  {bool} $use_templates Whether to use Sensei templates for the request.
		 *
		 * @since  3.6.0
		 * @access private
		 */
		if ( ! apply_filters( 'sensei_use_sensei_template', true ) ) {
			return;
		}

		// Use the first handler that can handle this request.
		foreach ( $this->_handlers as $handler ) {
			if ( $handler->can_handle_request() ) {
				$this->_is_handling_request = true;
				$handler->handle_request();
				break;
			}
		}
	}

}
