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
	 * The key to use for storing the notices as user meta
	 */
	private const USER_META_KEY = 'sensei_notices';

	/**
	 * Notices.
	 *
	 *  @var $notices
	 */
	protected $notices;

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
		add_action( 'init', [ $this, 'maybe_load_notices' ] );
		add_action( 'shutdown', [ $this, 'maybe_persist_notices' ] );
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
	}

	/**
	 * Output the container to put the notices.
	 *
	 * @return void
	 */
	public function maybe_print_notices_container() {
		?>
		<div class="sensei-notices-container"></div>
		<?php
	}

	/**
	 * Output all notices added as scripts to the last "sensei-notices-container" element.
	 *
	 * @return void
	 */
	public function maybe_print_notices_on_script() {
		if ( ! empty( $this->notices ) ) {
			$result = '';
			foreach ( $this->notices as $notice ) {
				$result .= $this->generate_notice( $notice['type'], $notice['content'] );
			}
			?>
			<script type="text/javascript">
				( function(d) {
					const containers = Array.from( d.querySelectorAll( ".sensei-notices-container" ) );
					containers.pop().innerHTML = <?php echo wp_json_encode( $result ); ?>;
				} )(document);
			</script>
			<?php
			// empty the notice queue to avoid reprinting the same notices.
			$this->clear_notices();
		}
	}

	/**
	 * Load the notices from the user meta, if the user is logged in, and delete them.
	 *
	 * @return void
	 */
	public function maybe_load_notices() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$values  = get_user_meta( $user_id, self::USER_META_KEY );

			$this->notices = array_merge( $this->notices, ...$values );
			foreach ( $values as $value ) {
				delete_user_meta( $user_id, self::USER_META_KEY, $value );
			}
		}
	}

	/**
	 * If the user is logged in and there's notices to print, persist the saved notices as user meta, and clear the
	 * notice list.
	 *
	 * @return void
	 */
	public function maybe_persist_notices() {
		if ( ! empty( $this->notices ) && is_user_logged_in() ) {
			add_user_meta( get_current_user_id(), self::USER_META_KEY, $this->notices );
			$this->clear_notices();
		}
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
			$this->maybe_print_notices_container();
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
