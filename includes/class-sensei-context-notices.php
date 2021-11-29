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
	 *     Actions to display inside the notice.
	 *
	 *     @type string $label Action label.
	 *     @type string $url   Action URL.
	 *     @type string $style Action style.
	 * }
	 */
	public function add_notice( string $key, string $text, string $title = null, array $actions = [] ) {
		$this->notices[ $key ] = [
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
	 * Get actions HTML.
	 *
	 * @param array  $actions          Actions array.
	 * @param string $css_class_prefix CSS class prefix to be applied in the HTML.
	 *
	 * @return string HTML with the actions.
	 */
	private function get_actions_html( array $actions, string $css_class_prefix ) {
		if ( empty( $actions ) ) {
			return '';
		}

		$html = array_map(
			function( $action ) use ( $css_class_prefix ) {
				return '<li>
					<a
						href="' . esc_url( $action['url'] ) . '"
						class="' . $css_class_prefix . '__button is-' . esc_attr( $action['style'] ) . '"
					>
						' . wp_kses_post( $action['label'] ) . '
					</a>
				</li>';
			},
			$actions
		);

		return '<ul class="' . $css_class_prefix . '-notice__actions">' . implode( '', $html ) . '</ul>';
	}

	/**
	 * Get notices HTML.
	 *
	 * @param string $css_class_prefix CSS class prefix to be applied in the HTML.
	 *
	 * @return string HTML with the added notices.
	 */
	public function get_notices_html( string $css_class_prefix = 'sensei-lms' ) {
		$notices = $this->get_notices();

		if ( empty( $notices ) ) {
			return '';
		}

		$notices_html = array_map(
			function( $notice ) use ( $css_class_prefix ) {
				$title = empty( $notice['title'] ) ? '' : '<h3 class="' . $css_class_prefix . '-notice__title">' . wp_kses_post( $notice['title'] ) . '</h3>';

				return '<div class="' . $css_class_prefix . '-notice">
					' . $title . '
					<p class="' . $css_class_prefix . '-notice__text">' . wp_kses_post( $notice['text'] ) . '</p>
					' . $this->get_actions_html( $notice['actions'], $css_class_prefix ) . '
				</div>';
			},
			$notices
		);

		return '<div>' . implode( '', $notices_html ) . '</div>';
	}
}
