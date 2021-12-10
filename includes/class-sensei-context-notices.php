<?php
/**
 * File containing Sensei_Context_Notices class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Context_Notices class.
 *
 * @since 3.15.0
 */
class Sensei_Context_Notices {
	/**
	 * Notices context.
	 *
	 * @var string
	 */
	private $context;

	/**
	 * Notices instances.
	 *
	 * @var self[]
	 */
	private static $instances = [];

	/**
	 * Notices array.
	 *
	 * @var array
	 */
	private $notices = [];

	/**
	 * Sensei_Context_Notices constructor. Prevents other instances from being created outside of `self::instance()`.
	 *
	 * @param string $context          The context of the notices.
	 */
	private function __construct( string $context ) {
		$this->context = $context;
		$this->notices = [];
	}

	/**
	 * Get an instance of the class.
	 *
	 * @param string $context The context of the notices.
	 *
	 * @return self
	 */
	public static function instance( string $context ) : self {
		if ( ! isset( self::$instances[ $context ] ) ) {
			self::$instances[ $context ] = new self( $context );
		}

		return self::$instances[ $context ];
	}

	/**
	 * It adds a new notice to be displayed.
	 * If `$key` already exists, it replaces the current notice.
	 *
	 * @param string $key     Notice key.
	 * @param string $text    Notice text.
	 * @param string $title   Notice title.
	 * @param array  $actions {
	 *     Actions to display inside the notice. It can contains strings with custom actions,
	 *     or arrays with the following properties.
	 *
	 *     @type string $label Action label.
	 *     @type string $url   Action URL.
	 *     @type string $style Action style.
	 * }
	 * @param string $icon    Notice icon.
	 */
	public function add_notice( string $key, string $text, string $title = null, array $actions = [], $icon = null ) {
		$this->notices[ $key ] = [
			'text'    => $text,
			'title'   => $title,
			'actions' => $actions,
			'icon'    => $icon,
		];
	}

	/**
	 * Remove notice.
	 *
	 * @param string $key Notice key.
	 *
	 * @return bool Whether notice was removed.
	 */
	public function remove_notice( string $key ) : bool {
		if ( isset( $this->notices[ $key ] ) ) {
			unset( $this->notices[ $key ] );
			return true;
		}

		return false;
	}

	/**
	 * Get notices array.
	 *
	 * @return array
	 */
	private function get_notices() : array {
		/**
		 * Filter the Course Theme notices.
		 *
		 * @since 3.15.0
		 * @hook sensei_context_notices
		 *
		 * @param {array}  $notices Course Theme notices.
		 * @param {string} $context  The context.
		 *
		 * @return {array} Filtered Course Theme notices.
		 */
		return apply_filters( 'sensei_context_notices', $this->notices, $this->context );
	}

	/**
	 * Get notices HTML.
	 *
	 * @param string $template The template path to render the notices.
	 *
	 * @return string HTML with the added notices.
	 */
	public function get_notices_html( string $template ) {
		$notices = $this->get_notices();

		if ( empty( $notices ) ) {
			return '';
		}

		ob_start();
		Sensei_Templates::get_template( $template, [ 'notices' => $notices ] );
		return ob_get_clean();
	}
}
