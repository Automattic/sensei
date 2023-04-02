<?php
/**
 * File containing the class Sensei_Admin_Notices.
 *
 * @package sensei-lms
 * @since   3.11.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles showing admin notices.
 * It also includes notices coming from SenseiLMS.com.
 *
 * @access private
 *
 * @class Sensei_Admin_Notices
 */
class Sensei_Admin_Notices {
	const SENSEILMS_NOTICES_API_URL      = 'https://senseilms.com/wp-json/senseilms-notices/1.0/notices';
	const DISMISS_NOTICE_NONCE_ACTION    = 'sensei-lms-dismiss-notice';
	const DISMISSED_NOTICES_OPTION       = 'sensei-dismissed-notices';
	const DISMISSED_NOTICES_USER_META    = 'sensei-dismissed-notices';
	const ALL_SENSEI_SCREENS_PLACEHOLDER = 'sensei*';

	const ALLOWED_HTML = [
		'strong' => [],
		'em'     => [],
		'a'      => [
			'target' => [],
			'href'   => [],
			'rel'    => [],
		],
	];

	const ALLOWED_CAP_CHECKS = [
		'activate_plugins',
		'install_plugins',
		'manage_options',
		'update_core',
		'update_plugins',
		'delete_plugins',
		'edit_posts',
		'edit_others_posts',
	];

	const SENSEI_SCREEN_IDS = [
		'edit-course',
		'edit-lesson',
		'edit-question',
		'edit-sensei_message',
		'edit-module',
		'edit-course-category',
		'edit-question-type',
		'edit-question-category',
		'edit-lesson-tag',
		Sensei_Home::SCREEN_ID,
		'sensei-lms_page_' . Sensei_Analysis::PAGE_SLUG,
		'sensei-lms_page_sensei_learners',
		'sensei-lms_page_sensei-settings',
		'sensei-lms_page_sensei_grading',
		'sensei-lms_page_sensei-tools',
		'admin_page_lesson-order',
	];

	const OTHER_ALLOWED_SCREEN_IDS = [
		'dashboard',
		'update-core',
		'themes',
		'edit-page',
		'edit-post',
		'edit-product',
		'plugins',
		'plugins-network',
		'woocommerce_page_wc-admin',
		'woocommerce_page_wc-addons',
	];

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

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
	 * Class constructor.
	 */
	private function __construct() {
		// Silence is golden.
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'admin_notices', [ $this, 'add_admin_notices' ] );
		add_action( 'wp_ajax_sensei_dismiss_notice', [ $this, 'handle_notice_dismiss' ] );
	}

	/**
	 * Get the screen ID.
	 *
	 * @return string|null
	 */
	protected function get_screen_id() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return null;
		}

		$screen = get_current_screen();

		return $screen ? $screen->id : null;
	}

	/**
	 * Get notices.
	 *
	 * @param int|null $max_age The max age (seconds) of the source data.
	 *
	 * @return array
	 */
	protected function get_notices( $max_age = null ) {
		$transient_key = implode( '_', [ 'sensei_notices', Sensei()->version, determine_locale() ] );
		$data          = get_transient( $transient_key );
		$notices       = false;

		// If the data is too old, fetch it again.
		if ( $max_age && is_array( $data ) ) {
			$age = time() - ( $data['_fetched'] ?? 0 );
			if ( $age > $max_age ) {
				$data = false;
			}
		}

		if ( isset( $data['notices'] ) ) {
			$notices = $data['notices'];
		}

		if ( false === $notices ) {
			$notices_response = wp_safe_remote_get(
				add_query_arg(
					array(
						'version' => Sensei()->version,
						'lang'    => determine_locale(),
					),
					self::SENSEILMS_NOTICES_API_URL
				)
			);

			if ( ! is_wp_error( $notices_response ) && 200 === wp_remote_retrieve_response_code( $notices_response ) ) {
				$notices_response_body = json_decode( wp_remote_retrieve_body( $notices_response ), true );
				if ( $notices_response_body && isset( $notices_response_body['notices'] ) ) {
					$notices     = $notices_response_body['notices'];
					$cached_data = [
						'_fetched' => time(),
						'notices'  => $notices,
					];
					set_transient( $transient_key, $cached_data, DAY_IN_SECONDS );
				}
			}
		}

		if ( ! $notices || ! is_array( $notices ) ) {
			$notices = [];
		}

		/**
		 * Filters the admin notices.
		 *
		 * @hook sensei_admin_notices
		 *
		 * @param {array}    $notices The admin notices.
		 * @param {int|null} $max_age The max age (seconds) of the source data.
		 *
		 * @return {array} The admin notices.
		 */
		$notices = apply_filters( 'sensei_admin_notices', $notices, $max_age );

		return $notices;
	}

	/**
	 * Output the admin notice.
	 *
	 * @access private
	 */
	public function add_admin_notices() {
		$screen_id = $this->get_screen_id();

		/**
		 * Adds the ability to hide notices on a specific screen.
		 *
		 * @hook sensei_show_admin_notices_{$screen_id}
		 * @since 4.8.0
		 *
		 * @param {bool} $hide_notices_on_screen Whether to hide notices on the screen.
		 *
		 * @return {bool} Whether to hide notices on the screen.
		 */
		if ( ! apply_filters( "sensei_show_admin_notices_{$screen_id}", true ) ) {
			return;
		}

		foreach ( $this->get_notices_to_display() as $notice_id => $notice ) {
			$this->add_admin_notice( $notice_id, $notice );
		}
	}

	/**
	 * Output the admin notice.
	 *
	 * @param string $notice_id The unique notice ID.
	 * @param array  $notice The notice configuration.
	 */
	private function add_admin_notice( $notice_id, $notice ) {
		if ( empty( $notice['actions'] ) || ! is_array( $notice['actions'] ) ) {
			$notice['actions'] = [];
		}

		$notice_class = '';
		if ( ! empty( $notice['style'] ) ) {
			$notice_class = 'sensei-notice-' . $notice['style'];
		}

		$is_dismissible       = $notice['dismissible'];
		$notice_wrapper_extra = '';
		if ( $is_dismissible ) {
			wp_enqueue_script( 'sensei-dismiss-notices' );
			$notice_class        .= ' is-dismissible';
			$notice_wrapper_extra = sprintf( ' data-dismiss-action="sensei_dismiss_notice" data-dismiss-notice="%1$s" data-dismiss-nonce="%2$s"', esc_attr( $notice_id ), esc_attr( wp_create_nonce( self::DISMISS_NOTICE_NONCE_ACTION ) ) );
		}
		?>
		<div class="notice sensei-notice <?php echo esc_attr( $notice_class ); ?>"
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			echo $notice_wrapper_extra;
			?>
		>
			<?php
			if ( ! empty( $notice['icon'] ) ) {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic parts escaped in the function.
				echo Sensei()->assets->get_icon( $notice['icon'], 'sensei-notice__icon' );
			}
			echo '<div class="sensei-notice__wrapper">';
			echo '<div class="sensei-notice__content">';
			if ( ! empty( $notice['heading'] ) ) {
				echo '<div class="sensei-notice__heading">';
				echo wp_kses( $notice['heading'], self::ALLOWED_HTML );
				echo '</div>';
			}
			echo wp_kses( $notice['message'], self::ALLOWED_HTML );
			echo '</div>';
			echo '</div>';
			if ( ! empty( $notice['actions'] ) ) {
				echo '<div class="sensei-notice__actions">';
				foreach ( $notice['actions'] as $action ) {
					if ( ! isset( $action['label'] ) || ( ! isset( $action['url'] ) && ! isset( $action['tasks'] ) ) ) {
						continue;
					}

					$button_class = ! isset( $action['primary'] ) || $action['primary'] ? 'button-primary' : 'button-secondary';
					$extra_attrs  = '';
					if ( isset( $action['tasks'] ) ) {
						wp_enqueue_script( 'sensei-dismiss-notices' );
						$extra_attrs = ' data-sensei-notice-tasks="' . esc_attr( wp_json_encode( $action['tasks'] ) ) . '"';
					}
					echo '<a href="' . esc_url( $action['url'] ) . '" target="' . esc_attr( $action['target'] ?? '_self' ) . '" rel="noopener noreferrer" class="button ' . esc_attr( $button_class ) . '"' . $extra_attrs . '>';
					echo esc_html( $action['label'] );
					echo '</a>';
				}
				echo '</div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get the notices to display to the user.
	 *
	 * @access private
	 *
	 * @param string   $screen_id The screen ID.
	 * @param int|null $max_age   The max age (seconds) of the source data.
	 *
	 * @return array
	 */
	public function get_notices_to_display( $screen_id = null, $max_age = null ) {
		$notices = [];
		foreach ( $this->get_notices( $max_age ) as $notice_id => $notice ) {
			$notice = $this->normalize_notice( $notice );

			$is_user_notification = 'user' === $notice['type'];

			if (
				! isset( $notice['message'] )
				|| ( $notice['dismissible'] && $this->is_notice_dismissed( $notice_id, $is_user_notification ) )
				|| ! $this->check_notice_conditions( $notice, $screen_id )
			) {
				continue;
			}

			$notices[ $notice_id ] = $notice;
		}

		return $notices;
	}

	/**
	 * Check notice conditions.
	 *
	 * @param array  $notice The notice configuration.
	 * @param string $screen_id The screen ID.
	 *
	 * @return bool
	 */
	private function check_notice_conditions( $notice, $screen_id = null ) {
		if ( ! isset( $notice['conditions'] ) || ! is_array( $notice['conditions'] ) ) {
			$notice['conditions'] = [];
		}

		$has_screen_condition = false;
		$can_see_notice       = true;

		foreach ( $notice['conditions'] as $condition ) {
			if ( ! isset( $condition['type'] ) ) {
				continue;
			}

			switch ( $condition['type'] ) {
				case 'min_php':
					if ( ! isset( $condition['version'] ) ) {
						break;
					}

					if ( ! $this->condition_check_min_php( $condition['version'] ) ) {
						$can_see_notice = false;
						break 2;
					}

					break;
				case 'min_wp':
					if ( ! isset( $condition['version'] ) ) {
						break;
					}

					if ( ! $this->condition_check_min_wp( $condition['version'] ) ) {
						$can_see_notice = false;
						break 2;
					}

					break;
				case 'user_cap':
					if ( ! isset( $condition['capabilities'] ) || ! is_array( $condition['capabilities'] ) ) {
						break;
					}

					if ( ! $this->condition_check_capabilities( $condition['capabilities'] ) ) {
						$can_see_notice = false;
						break 2;
					}

					break;
				case 'screens':
					if ( ! isset( $condition['screens'] ) || ! is_array( $condition['screens'] ) ) {
						break;
					}

					$has_screen_condition = true;
					if ( ! $this->condition_check_screen( $condition['screens'], $screen_id ) ) {
						$can_see_notice = false;
						break 2;
					}

					break;
				case 'plugins':
					if ( ! isset( $condition['plugins'] ) || ! is_array( $condition['plugins'] ) ) {
						break;
					}

					if ( ! $this->condition_check_plugin( $condition['plugins'] ) ) {
						$can_see_notice = false;
						break 2;
					}
					break;

				case 'installed_since':
					if ( ! isset( $condition['installed_since'] ) ) {
						break;
					}

					if ( ! $this->condition_installed_since( $condition['installed_since'] ) ) {
						$can_see_notice = false;
						break 2;
					}
					break;
			}
		}

		// If no screens condition was set, only show this message on Sensei screens.
		if ( $can_see_notice && ! $has_screen_condition && ! $this->condition_check_screen( [ self::ALL_SENSEI_SCREENS_PLACEHOLDER ], $screen_id ) ) {
			$can_see_notice = false;
		}

		return $can_see_notice;
	}

	/**
	 * Check a PHP version condition.
	 *
	 * @param string $min_version Minimum PHP version.
	 * @return bool
	 */
	private function condition_check_min_php( string $min_version ) : bool {
		return version_compare( phpversion(), $min_version, '>=' );
	}

	/**
	 * Check a WP version condition.
	 *
	 * @param string $min_version Minimum WP version.
	 * @return bool
	 */
	private function condition_check_min_wp( string $min_version ) : bool {
		return version_compare( get_bloginfo( 'version' ), $min_version, '>=' );
	}

	/**
	 * Check a capability condition.
	 *
	 * @param array $allowed_caps Array of capabilities that the user must have.
	 * @return bool
	 */
	private function condition_check_capabilities( array $allowed_caps ) : bool {
		$condition_pass = true;

		foreach ( $allowed_caps as $cap ) {
			if ( ! in_array( $cap, self::ALLOWED_CAP_CHECKS, true ) ) {
				continue;
			}

			if ( ! current_user_can( $cap ) ) {
				$condition_pass = false;
				break;
			}
		}

		return $condition_pass;
	}

	/**
	 * Check a screen condition.
	 *
	 * @param array  $allowed_screens Array of allowed screen IDs. `sensei*` is a special screen ID for any Sensei screen.
	 * @param string $screen_id       The screen ID.
	 *
	 * @return bool
	 */
	private function condition_check_screen( array $allowed_screens, $screen_id = null ) : bool {
		/**
		 * Filter the array of screen IDs that are part of Sensei, and where we should show Sensei notices on.
		 *
		 * @since 4.12.0
		 * @hook sensei_notices_screen_ids
		 *
		 * @param {array} Array of Screen IDs that are part of Sensei.
		 * @return {array} Updated array of screen IDs that are part of Sensei.
		 */
		$sensei_screen_ids  = apply_filters( 'sensei_notices_screen_ids', self::SENSEI_SCREEN_IDS );
		$allowed_screen_ids = array_merge( $sensei_screen_ids, self::OTHER_ALLOWED_SCREEN_IDS );
		$condition_pass     = true;

		if ( in_array( 'sensei*', $allowed_screens, true ) ) {
			$allowed_screens = array_merge( $allowed_screens, $sensei_screen_ids );
		}

		$screens   = array_intersect( $allowed_screen_ids, $allowed_screens );
		$screen_id = $screen_id ?? $this->get_screen_id();

		if ( ! $screen_id || ! in_array( $screen_id, $screens, true ) ) {
			$condition_pass = false;
		}

		return $condition_pass;
	}

	/**
	 * Check an "installed since" condition
	 *
	 * @param int|string $installed_since Time to check the installation time for.
	 *
	 * @return bool
	 */
	private function condition_installed_since( $installed_since ) : bool {
		$installed_at = get_option( 'sensei_installed_at' );
		if ( $installed_since && is_string( $installed_since ) ) {
			$installed_since = strtotime( '-' . $installed_since );
		}
		if ( ! $installed_at || ! $installed_since ) {
			return false;
		}
		return $installed_at <= $installed_since;
	}

	/**
	 * Check a plugin condition.
	 *
	 * @param array $allowed_plugins Array of the plugins to check for.
	 *
	 * @return bool
	 */
	private function condition_check_plugin( array $allowed_plugins ) : bool {
		$condition_pass = true;
		$active_plugins = $this->get_active_plugins();

		foreach ( $allowed_plugins as $plugin_basename => $plugin_condition ) {
			$plugin_active  = isset( $active_plugins[ $plugin_basename ] );
			$plugin_version = isset( $active_plugins[ $plugin_basename ]['Version'] ) ? $active_plugins[ $plugin_basename ]['Version'] : false;

			if ( false === $plugin_condition ) {
				// The plugin should not be active.
				if ( $plugin_active ) {
					$condition_pass = false;
					break;
				}
			} elseif ( true === $plugin_condition ) {
				// The plugin just needs to be active.
				if ( ! $plugin_active ) {
					$condition_pass = false;
					break;
				}
			} elseif ( isset( $plugin_condition['min'] ) || isset( $plugin_condition['max'] ) ) {
				// There is a plugin version condition, but we expect the plugin to be activated.
				if ( ! $plugin_active ) {
					$condition_pass = false;
					break;
				}

				if ( isset( $plugin_condition['min'] ) && version_compare( $plugin_version, $plugin_condition['min'], '<' ) ) {
					// If the activated plugin version is older than the minimum required, do not show the notice.
					$condition_pass = false;
					break;
				}

				if ( isset( $plugin_condition['max'] ) && version_compare( $plugin_version, $plugin_condition['max'], '>' ) ) {
					// If the activated plugin version is newer than the maximum required, do not show the notice.
					$condition_pass = false;
					break;
				}
			}
		}

		return $condition_pass;
	}

	/**
	 * Partial wrapper for for `get_plugins()` function. Filters out non-active plugins.
	 *
	 * @return array Key is basename of active plugins and value is version.
	 */
	protected function get_active_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		foreach ( $plugins as $plugin_basename => $plugin_data ) {
			if ( ! is_plugin_active( $plugin_basename ) ) {
				unset( $plugins[ $plugin_basename ] );
			}
		}

		return $plugins;
	}

	/**
	 * Normalize notices.
	 *
	 * @param array $notice The notice configuration.
	 *
	 * @return array
	 */
	private function normalize_notice( $notice ) {
		if ( ! isset( $notice['conditions'] ) || ! is_array( $notice['conditions'] ) ) {
			$notice['conditions'] = [];
		}

		if ( ! isset( $notice['type'] ) ) {
			$notice['type'] = 'site-wide';
		}

		if ( 'site-wide' === $notice['type'] ) {
			// Only admins can see and manage site-wide notifications.
			$notice['conditions'][] = [
				'type'         => 'user_cap',
				'capabilities' => [ 'manage_options' ],
			];
		}

		if ( ! isset( $notice['dismissible'] ) ) {
			$notice['dismissible'] = true;
		}

		return $notice;
	}

	/**
	 * Check to see if this notice is dismissed.
	 *
	 * @param string $notice_id            Unique identifier for the notice.
	 * @param bool   $is_user_notification True if this is for a user notification (vs site-wide notification).
	 *
	 * @return bool
	 */
	private function is_notice_dismissed( $notice_id, $is_user_notification ) {
		$dismissed_notices = $this->get_dismissed_notices( $is_user_notification );

		return in_array( $notice_id, $dismissed_notices, true );
	}

	/**
	 * Get the dismissed notifications (either for the user or site-wide).
	 *
	 * @param bool $is_user_notification True if this is for a user notification (vs site-wide notification).
	 *
	 * @return array
	 */
	private function get_dismissed_notices( $is_user_notification ) {
		if ( $is_user_notification ) {
			$dismissed_notices = get_user_meta( get_current_user_id(), self::DISMISSED_NOTICES_USER_META, true );
			if ( ! $dismissed_notices ) {
				$dismissed_notices = [];
			}
		} else {
			$dismissed_notices = get_option( self::DISMISSED_NOTICES_OPTION, [] );
		}

		return $dismissed_notices;
	}

	/**
	 * Save dismissed notices.
	 *
	 * @param array $dismissed_notices Array of dismissed notices.
	 * @param bool  $is_user_notification True if we are setting user notifications (vs site-wide notifications).
	 */
	private function save_dismissed_notices( $dismissed_notices, $is_user_notification ) {
		if ( $is_user_notification ) {
			update_user_meta( get_current_user_id(), self::DISMISSED_NOTICES_USER_META, $dismissed_notices );
		} else {
			update_option( self::DISMISSED_NOTICES_OPTION, $dismissed_notices );
		}
	}

	/**
	 * Handle the dismissal of the notice.
	 *
	 * @access private
	 */
	public function handle_notice_dismiss() {
		check_ajax_referer( self::DISMISS_NOTICE_NONCE_ACTION, 'nonce' );

		$notices   = $this->get_notices();
		$notice_id = isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : false;
		if ( ! $notice_id || ! isset( $notices[ $notice_id ] ) ) {
			return;
		}

		$notice = $this->normalize_notice( $notices[ $notice_id ] );

		$is_user_notification = 'user' === $notice['type'];
		if (
			! $notice['dismissible']
			|| ( ! $is_user_notification && ! current_user_can( 'manage_options' ) )
		) {
			wp_die( '', '', 403 );
		}

		$dismissed_notices   = $this->get_dismissed_notices( $is_user_notification );
		$dismissed_notices[] = $notice_id;

		$this->save_dismissed_notices( $dismissed_notices, $is_user_notification );
	}
}
