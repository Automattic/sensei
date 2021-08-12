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
 * Class that handles showing the notices from SenseiLMS.com.
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
		'sensei-lms_page_sensei_analysis',
		'sensei-lms_page_sensei_learners',
		'sensei-lms_page_sensei-settings',
		'sensei-lms_page_sensei_grading',
		'sensei-lms_page_sensei-extensions',
		'sensei-lms_page_sensei-tools',
		'lesson_page_lesson-order',
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
	 * Class constructor.
	 */
	public function __construct() {
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
		$screen = get_current_screen();

		return $screen ? $screen->id : null;
	}

	/**
	 * Get notices.
	 *
	 * @return array
	 */
	protected function get_notices() {
		$transient_key = implode( '_', [ 'sensei_notices', Sensei()->version, determine_locale() ] );
		$notices       = get_transient( $transient_key );
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
					$notices = $notices_response_body['notices'];
					set_transient( $transient_key, $notices, HOUR_IN_SECONDS );
				}
			}
		}

		if ( ! $notices || ! is_array( $notices ) ) {
			$notices = [];
		}

		return $notices;
	}

	/**
	 * Output the admin notice.
	 *
	 * @access private
	 */
	public function add_admin_notices() {
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

		wp_enqueue_script( 'sensei-dismiss-notices' );

		?>
		<div class="notice sensei-notice is-dismissible" data-dismiss-action="sensei_dismiss_notice" data-dismiss-notice="<?php echo esc_attr( $notice_id ); ?>"
				data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( self::DISMISS_NOTICE_NONCE_ACTION ) ); ?>">
			<?php
			echo '<div class="sensei-notice__wrapper">';
			if ( ! empty( $notice['heading'] ) ) {
				echo '<div class="sensei-notice__heading">';
				echo wp_kses( $notice['heading'], self::ALLOWED_HTML );
				echo '</div>';
			}
			echo '<div class="sensei-notice__content">';
			echo wp_kses( $notice['message'], self::ALLOWED_HTML );
			echo '</div>';
			echo '</div>';
			if ( ! empty( $notice['actions'] ) ) {
				echo '<div class="sensei-notice__actions">';
				foreach ( $notice['actions'] as $action ) {
					if ( ! isset( $action['label'], $action['url'] ) ) {
						continue;
					}

					$button_class = ! isset( $action['primary'] ) || $action['primary'] ? 'button-primary' : 'button-secondary';
					echo '<a href="' . esc_url( $action['url'] ) . '" target="' . esc_attr( $action['target'] ?? '_self' ) . '" rel="noopener noreferrer" class="button ' . esc_attr( $button_class ) . '">';
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
	 * @return array
	 */
	public function get_notices_to_display() {
		$notices = [];
		foreach ( $this->get_notices() as $notice_id => $notice ) {
			$notice = $this->normalize_notice( $notice );

			$is_user_notification = 'user' === $notice['type'];

			if ( ! isset( $notice['message'] ) || $this->is_notice_dismissed( $notice_id, $is_user_notification ) || ! $this->check_notice_conditions( $notice ) ) {
				continue;
			}

			$notices[ $notice_id ] = $notice;
		}

		return $notices;
	}

	/**
	 * Check notice conditions.
	 *
	 * @param array $notice The notice configuration.
	 *
	 * @return bool
	 */
	private function check_notice_conditions( $notice ) {
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
					if ( ! $this->condition_check_screen( $condition['screens'] ) ) {
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
			}
		}

		// If no screens condition was set, only show this message on Sensei screens.
		if ( $can_see_notice && ! $has_screen_condition && ! $this->condition_check_screen( [ self::ALL_SENSEI_SCREENS_PLACEHOLDER ] ) ) {
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
	 * @param array $allowed_screens Array of allowed screen IDs. `sensei*` is a special screen ID for any Sensei screen.
	 * @return bool
	 */
	private function condition_check_screen( array $allowed_screens ) : bool {
		$allowed_screen_ids = array_merge( self::SENSEI_SCREEN_IDS, self::OTHER_ALLOWED_SCREEN_IDS );
		$condition_pass     = true;

		if ( in_array( 'sensei*', $allowed_screens, true ) ) {
			$allowed_screens = array_merge( $allowed_screens, self::SENSEI_SCREEN_IDS );
		}

		$screens   = array_intersect( $allowed_screen_ids, $allowed_screens );
		$screen_id = $this->get_screen_id();
		if ( ! $screen_id || ! in_array( $screen_id, $screens, true ) ) {
			$condition_pass = false;
		}

		return $condition_pass;
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
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
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

		$is_user_notification = 'user' === $notices[ $notice_id ]['type'];
		if ( ! $is_user_notification && ! current_user_can( 'manage_options' ) ) {
			wp_die( '', '', 403 );
		}

		$dismissed_notices   = $this->get_dismissed_notices( $is_user_notification );
		$dismissed_notices[] = $notice_id;

		$this->save_dismissed_notices( $dismissed_notices, $is_user_notification );
	}
}
