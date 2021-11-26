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
	 * Array with notices to show in the specific context.
	 *
	 * @var array
	 */
	private $notices = [];

	/**
	 * CSS class prefix.
	 *
	 * @var string
	 */
	private $css_class_prefix = 'sensei-lms';

	/**
	 * Sensei_Context_Notices constructor. Prevents other instances from being created outside of `self::instance()`.
	 *
	 * @param string $context          The context of the notices.
	 * @param string $css_class_prefix CSS class prefix to be applied in the HTML.
	 */
	private function __construct( string $context, string $css_class_prefix = null ) {
		$this->context             = $context;
		$this->notices[ $context ] = [];

		if ( ! empty( $css_class_prefix ) ) {
			$this->css_class_prefix = $css_class_prefix;
		}
	}

	/**
	 * Get an instance of the class.
	 *
	 * @param string $context The context of the notices.
	 * @param string $css_class_prefix CSS class prefix to be applied in the HTML.
	 *
	 * @return self
	 */
	public static function instance( string $context, string $css_class_prefix = null ) : self {
		if ( ! isset( self::$instances[ $context ] ) ) {
			self::$instances[ $context ] = new self( $context, $css_class_prefix );
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
	 *     Actions to display inside the notice.
	 *
	 *     @type string $label Action label.
	 *     @type string $url   Action URL.
	 *     @type string $style Action style.
	 * }
	 */
	public function add_notice( string $key, string $text, string $title = null, array $actions = [] ) {
		$this->notices[ $this->context ][ $key ] = [
			'text'    => $text,
			'title'   => $title,
			'actions' => $actions,
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
		if ( isset( $this->notices[ $this->context ][ $key ] ) ) {
			unset( $this->notices[ $this->context ][ $key ] );
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
		return apply_filters( 'sensei_context_notices', $this->notices[ $this->context ], $this->context );
	}

	/**
	 * Get actions HTML.
	 *
	 * @param array $actions
	 *
	 * @return string HTML with the actions.
	 */
	private function get_actions_html( $actions ) {
		$html = array_map(
			function( $action ) {
				return '<li>
					<a
						href="' . esc_url( $action['url'] ) . '"
						class="' . $this->css_class_prefix . '__button is-' . esc_attr( $action['style'] ) . '"
					>
						' . wp_kses_post( $action['label'] ) . '
					</a>
				</li>';
			},
			$actions
		);

		return '<ul class="' . $this->css_class_prefix . '-notice__actions">' . implode( '', $html ) . '</ul>';
	}

	/**
	 * Get notices HTML.
	 *
	 * @return string HTML with the added notices.
	 */
	public function get_notices_html() {
		$notices = $this->get_notices();

		if ( empty( $notices ) ) {
			return '';
		}

		$notices_html = array_map(
			function( $notice ) {
				$title = empty( $notice['title'] ) ? '' : '<h3 class="' . $this->css_class_prefix . '-notice__title">' . wp_kses_post( $notice['title'] ) . '</h3>';

				return '<div class="' . $this->css_class_prefix . '-notice">
					' . $title . '
					<p class="' . $this->css_class_prefix . '-notice__text">' . wp_kses_post( $notice['text'] ) . '</p>
					' . $this->get_actions_html( $notice['actions'] ) . '
				</div>';
			},
			$notices
		);

		return '<div>' . implode( '', $notices_html ) . '</div>';
	}
}
