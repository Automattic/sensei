<?php
/**
 * File containing Sensei_Tools class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tools class.
 *
 * @since 3.7.0
 */
class Sensei_Tools {
	const MESSAGES_TRANSIENT_PREFIX  = 'sensei-lms-tools-messages-';
	const MESSAGES_TRANSIENT_TIMEOUT = HOUR_IN_SECONDS;

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Instantiated array of tools.
	 *
	 * @var Sensei_Tool_Interface[]
	 */
	private $tools;

	/**
	 * Sensei_Tools constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds all filters and actions.
	 *
	 * @since 3.7.0
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ], 90 );
		add_filter( 'sensei_learners_main_column_data', [ Sensei_Tool_Enrolment_Debug::class, 'add_debug_action' ], 10, 3 );
	}

	/**
	 * Get the tools.
	 *
	 * @return Sensei_Tool_Interface[]
	 */
	public function get_tools() {
		if ( ! $this->tools ) {
			$tools   = [];
			$tools[] = new Sensei_Tool_Recalculate_Enrolment();
			$tools[] = new Sensei_Tool_Recalculate_Course_Enrolment();
			$tools[] = new Sensei_Tool_Ensure_Roles();
			$tools[] = new Sensei_Tool_Remove_Deleted_User_Data();
			$tools[] = new Sensei_Tool_Enrolment_Debug();
			$tools[] = new Sensei_Tool_Module_Slugs_Mismatch();

			/**
			 * Array of the tools available to Sensei LMS.
			 *
			 * @since 3.7.0
			 * @hook sensei_tools
			 *
			 * @param {Sensei_Tool_Interface[]} $tools Tool objects for Sensei LMS.
			 *
			 * @return {array}
			 */
			$tools = apply_filters( 'sensei_tools', $tools );

			$this->tools = [];
			foreach ( $tools as $tool ) {
				$this->tools[ $tool->get_id() ] = $tool;
			}
		}

		return $this->tools;
	}

	/**
	 * Adds admin menu pages.
	 */
	public function add_menu_pages() {
		$title = esc_html__( 'Tools', 'sensei-lms' );
		add_submenu_page( 'sensei', $title, $title, 'manage_sensei', 'sensei-tools', [ $this, 'output' ] );
		add_action( 'load-sensei-lms_page_sensei-tools', [ $this, 'process' ] );
	}

	/**
	 * Handle processing actions on the tools page.
	 */
	public function process() {
		$tools = $this->get_tools();

		if ( ! empty( $_GET['tool'] ) ) {
			$tool_id = sanitize_text_field( wp_unslash( $_GET['tool'] ) );
			if ( ! isset( $tools[ $tool_id ] ) ) {
				$this->trigger_invalid_request();

				return;
			}

			$tool = $tools[ $tool_id ];

			if ( ! $tool->is_available() ) {
				$this->add_user_message( __( 'This tool is not currently available. Please try again later.', 'sensei-lms' ), true );

				wp_safe_redirect( $this->get_tools_url() );

				exit;
			}

			if ( $this->is_interactive_tool( $tool ) ) {
				// Let the tool do its own nonce check and processing.
				$tool->process();
			} else {
				// Check the nonce for non-interactive tools.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Do not change nonce.
				if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sensei-tool-' . $tool_id ) ) {
					$this->trigger_invalid_request();

					return;
				}

				$tool->process();

				wp_safe_redirect( $this->get_tools_url() );
				exit;
			}
		}
	}

	/**
	 * Output the tools page.
	 */
	public function output() {
		Sensei()->assets->enqueue( 'sensei-tools', 'css/tools.css' );

		$tools = $this->get_tools();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tool_id = ! empty( $_GET['tool'] ) ? sanitize_text_field( wp_unslash( $_GET['tool'] ) ) : false;

		if (
			$tool_id
			&& isset( $tools[ $tool_id ] )
			&& $this->is_interactive_tool( $tools[ $tool_id ] )
		) {
			$tool = $tools[ $tool_id ];

			ob_start();
			$tool->output();
			$output = ob_get_clean();

			// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Variable used in view.
			$messages = $this->get_user_messages( true );

			include __DIR__ . '/views/html-admin-page-tools-header.php';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output should be escaped in tool.
			echo $output;
			include __DIR__ . '/views/html-admin-page-tools-footer.php';
		} else {
			// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Variable used in view.
			$messages = $this->get_user_messages( true );

			include __DIR__ . '/views/html-admin-page-tools.php';
		}
	}

	/**
	 * Get the tool URL.
	 *
	 * @param Sensei_Tool_Interface $tool Tool object.
	 */
	public function get_tool_url( Sensei_Tool_Interface $tool ) {
		$tool_id = $tool->get_id();
		$url     = add_query_arg( 'tool', $tool_id, $this->get_tools_url() );
		if ( ! $this->is_interactive_tool( $tool ) ) {
			$url = wp_nonce_url( $url, 'sensei-tool-' . $tool_id );
		}

		return $url;
	}

	/**
	 * Check if a tool is interactive.
	 *
	 * @param Sensei_Tool_Interface $tool Tool object.
	 *
	 * @return bool True if it is an interactive tool.
	 */
	public function is_interactive_tool( Sensei_Tool_Interface $tool ) {
		return $tool instanceof Sensei_Tool_Interactive_Interface;
	}

	/**
	 * Get the URL for the tools listing page.
	 *
	 * @return string
	 */
	public function get_tools_url() {
		return admin_url( 'admin.php?page=sensei-tools' );
	}

	/**
	 * Get the user messages.
	 *
	 * @param bool $flush Flush the user messages at the same time.
	 *
	 * @return array
	 */
	private function get_user_messages( $flush = false ) {
		$messages_key = $this->get_user_message_transient_name();
		$messages     = get_transient( $messages_key );

		if ( empty( $messages ) ) {
			$messages = [];
		} else {
			$messages = json_decode( $messages, true );
		}

		if ( $flush ) {
			delete_transient( $messages_key );
		}

		return $messages;
	}

	/**
	 * Add a user message to display on the tools page.
	 *
	 * @param string $message  User message to display.
	 * @param bool   $is_error True this message is an error.
	 *
	 * @return bool
	 */
	public function add_user_message( $message, $is_error = false ) {
		$messages_key = $this->get_user_message_transient_name();
		$messages     = $this->get_user_messages( false );

		$messages[] = [
			'message'  => $message,
			'is_error' => $is_error,
		];

		set_transient( $messages_key, wp_json_encode( $messages ), self::MESSAGES_TRANSIENT_TIMEOUT );

		return true;
	}

	/**
	 * Get the name of the transient that stores user messages.
	 *
	 * @return string
	 */
	private function get_user_message_transient_name() {
		return self::MESSAGES_TRANSIENT_PREFIX . get_current_user_id();
	}

	/**
	 * Trigger invalid request and redirect.
	 *
	 * @param Sensei_Tool_Interface $tool Tool object to possibly redirect to.
	 */
	public function trigger_invalid_request( $tool = null ) {
		$redirect = $this->get_tools_url();

		if ( $tool ) {
			$redirect = $this->get_tool_url( $tool );
		}

		$this->add_user_message( __( 'There was a problem validating your request. Please try again.', 'sensei-lms' ), true );

		wp_safe_redirect( $redirect );
		exit;
	}

}
