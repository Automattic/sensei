<?php
/**
 * File containing Sensei_Home_Notices class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that gathers and produces the local notices for Sensei Home.
 */
class Sensei_Home_Notices {
	const HOME_NOTICE_KEY_PREFIX = 'sensei_home_';

	/**
	 * The remote data helper.
	 *
	 * @var Sensei_Home_Remote_Data_API $remote_data_api
	 */
	private $remote_data_api;

	/**
	 * Screen ID to show notices on.
	 *
	 * @var string
	 */
	private $screen_id;

	/**
	 * Cache of local plugin updates.
	 *
	 * @var array
	 */
	private $local_plugin_updates;

	/**
	 * Sensei_Home_Notices constructor.
	 *
	 * @param Sensei_Home_Remote_Data_API $remote_data_api The remote data helper.
	 * @param string                      $screen_id       The screen ID to show notices on.
	 */
	public function __construct( Sensei_Home_Remote_Data_API $remote_data_api, string $screen_id ) {
		$this->remote_data_api = $remote_data_api;
		$this->screen_id       = $screen_id;
	}

	/**
	 * Add the hooks related to this class.
	 */
	public function init() {
		add_filter( 'sensei_show_admin_notices_' . $this->screen_id, '__return_false' );
		add_filter( 'sensei_admin_notices', [ $this, 'add_update_notices' ], 10, 2 );
		add_filter( 'sensei_admin_notices', [ $this, 'add_review_notice' ], 10, 2 );
	}

	/**
	 * Add the notice asking the user for review.
	 *
	 * @access private
	 *
	 * @param array    $notices The notices to add the review notices to.
	 * @param int|null $max_age The max age (seconds) of the source data.
	 *
	 * @return array
	 */
	public function add_review_notice( $notices, $max_age = null ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return $notices;
		}

		$data = $this->remote_data_api->fetch( $max_age );

		if ( $data instanceof \WP_Error || empty( $data['reviews'] ) ) {
			return $notices;
		}

		$notice_id     = self::HOME_NOTICE_KEY_PREFIX . 'sensei_review';
		$yes_notice_id = $notice_id . '_yes';
		$no_notice_id  = $notice_id . '_no';

		$base_notice = [
			'level'       => 'success',
			'type'        => 'user',
			'conditions'  => [
				[
					'type'    => 'screens',
					'screens' => [ $this->screen_id ],
				],
				[
					'type'            => 'installed_since',
					'installed_since' => $data['reviews']['show_after'],
				],
			],
			'dismissible' => true,
		];

		$notices[ $notice_id ] = array_merge(
			$base_notice,
			[
				'message' => __( 'Are you enjoying Sensei LMS?', 'sensei-lms' ),
				'actions' => [
					[
						'primary' => false,
						'label'   => __( 'Yes', 'sensei-lms' ),
						'url'     => add_query_arg(
							[
								'_wpnonce'      => wp_create_nonce( $notice_id ),
								'review_answer' => '1',
							]
						),
						'tasks'   => [
							[
								'type' => 'preventDefault',
							],
							[
								'type'      => 'hide',
								'notice_id' => $notice_id,
							],
							[
								'type'      => 'show',
								'notice_id' => $yes_notice_id,
							],
						],
					],
					[
						'primary' => false,
						'label'   => __( 'No', 'sensei-lms' ),
						'tasks'   => [
							[
								'type' => 'preventDefault',
							],
							[
								'type'      => 'hide',
								'notice_id' => $notice_id,
							],
							[
								'type'      => 'show',
								'notice_id' => $no_notice_id,
							],
						],
					],
				],
			]
		);

		$notices[ $no_notice_id ] = array_merge(
			$base_notice,
			[
				'parent_id' => $notice_id,
				'message'   => __( "Let us know how we can improve your experience. We're always happy to help.", 'sensei-lms' ),
				'info_link' => [
					'label' => __( 'Share with us how can we help', 'sensei-lms' ),
					'url'   => $data['reviews']['feedback_url'],
					'tasks' => [
						[
							'type'      => 'dismiss',
							'notice_id' => $no_notice_id,
						],
					],
				],
			]
		);

		$notices[ $yes_notice_id ] = array_merge(
			$base_notice,
			[
				'parent_id' => $notice_id,
				'message'   => __( 'Great to hear! Would you be able to help us by leaving a review on WordPress.org?', 'sensei-lms' ),
				'info_link' => [
					'label' => __( 'Write a review for us', 'sensei-lms' ),
					'url'   => $data['reviews']['review_url'],
					'tasks' => [
						[
							'type'      => 'dismiss',
							'notice_id' => $yes_notice_id,
						],
					],
				],
			]
		);

		return $notices;
	}

	/**
	 * Add the update notices.
	 *
	 * @access private
	 *
	 * @param array    $notices The notices to add the update notices to.
	 * @param int|null $max_age The max age (seconds) of the source data.
	 *
	 * @return array
	 */
	public function add_update_notices( $notices, $max_age = null ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return $notices;
		}

		$data = $this->remote_data_api->fetch( $max_age );
		if ( $data instanceof \WP_Error || empty( $data['versions'] ) ) {
			return $notices;
		}

		$plugins_with_updates = $this->get_plugins_with_updates( $data['versions']['plugins'] ?? [] );

		foreach ( $plugins_with_updates as $plugin_slug => $plugin_data ) {
			$notice_id = self::HOME_NOTICE_KEY_PREFIX . $plugin_slug . '_update_' . $plugin_data['latest_version'];

			/**
			 * Filter whether a plugin has an active licensed.
			 *
			 * Defaults to true for plugins that don't need a license.
			 *
			 * @hook sensei_home_is_plugin_licensed_{$plugin_slug}
			 * @since 4.8.0
			 *
			 * @param {bool} $is_licensed Whether the plugin has an active license.
			 *
			 * @return {bool} Whether the plugin has an active license.
			 */
			$has_license = apply_filters( 'sensei_home_is_plugin_licensed_' . $plugin_slug, ! $plugin_data['licensed'] );

			if ( $has_license && ! $this->is_plugin_update_available( $plugin_slug, $plugin_data['latest_version'] ) ) {
				// If this is a licensed plugin, wait until WordPress knows about the update package to present the notice.
				continue;
			}

			if ( $has_license ) {
				$notice = $this->get_plugin_update_notice( $plugin_data );
			} elseif ( ! $plugin_data['active'] ) {
				$notice = $this->get_deactivated_plugin_update_notice( $plugin_data );
			} else {
				$notice = $this->get_unlicensed_plugin_update_notice( $plugin_data );
			}

			$notices[ $notice_id ] = $notice;
		}

		return array_filter( $notices );
	}

	/**
	 * Check to make sure the latest version is available to be installed for the plugin.
	 *
	 * @param string $plugin_slug    The plugin slug.
	 * @param string $latest_version The latest known version of the plugin.
	 *
	 * @return bool
	 */
	protected function is_plugin_update_available( $plugin_slug, $latest_version ) {
		$available_updates = $this->get_local_plugin_updates();

		return isset( $available_updates[ $plugin_slug ] ) && $latest_version === $available_updates[ $plugin_slug ];
	}

	/**
	 * Get the plugin updates that are locally known. Unlicensed plugins should never be known.
	 *
	 * @return array
	 */
	private function get_local_plugin_updates() {
		if ( ! isset( $this->local_plugin_updates ) ) {
			$this->local_plugin_updates = [];
			require_once ABSPATH . 'wp-admin/includes/update.php';

			$available_updates = get_plugin_updates();
			foreach ( $available_updates as $plugin_data ) {
				$plugin_slug    = dirname( $plugin_data->update->plugin ?? null );
				$update_version = $plugin_data->update->new_version ?? null;
				$update_package = $plugin_data->update->package ?? null;

				if ( ! $plugin_slug || ! $update_version || ! $update_package ) {
					continue;
				}

				$this->local_plugin_updates[ $plugin_slug ] = $update_version;
			}
		}

		return $this->local_plugin_updates;
	}

	/**
	 * Get the base settings for a plugin update notice.
	 *
	 * @param array $plugin_data The plugin update data.
	 *
	 * @return array
	 */
	private function get_base_plugin_notice( $plugin_data ) {
		$changelog_url = $plugin_data['changelog'] ?? false;

		$info_link = false;
		if ( ! empty( $changelog_url ) ) {
			$info_link = [
				'label' => __( 'What\'s new', 'sensei-lms' ),
				'url'   => esc_url_raw( $changelog_url ),
			];
		}

		// We only want this to be dismissible if Sensei LMS is active and available because it can handle the dismiss requests.
		$is_dismissible = class_exists( 'Sensei_Admin_Notices' );

		return [
			'level'       => 'info',
			'type'        => 'site-wide',
			'info_link'   => $info_link,
			'conditions'  => [
				[
					'type'    => 'screens',
					'screens' => [ $this->screen_id ],
				],
			],
			'dismissible' => $is_dismissible,
			'actions'     => [],
		];
	}

	/**
	 * Get the settings for a plugin update notice on an unlicensed plugin.
	 *
	 * @param array $plugin_data The plugin update data.
	 *
	 * @return array
	 */
	private function get_unlicensed_plugin_update_notice( array $plugin_data ):array {
		$plugin_name    = $plugin_data['name'];
		$latest_version = $plugin_data['latest_version'];

		$notice            = $this->get_base_plugin_notice( $plugin_data );
		$notice['message'] = wp_kses(
			sprintf(
				// translators: First placeholder is the plugin name; second placeholder is the latest version.
				__( 'There is a new version of <strong>%1$s</strong> available (%2$s). Please activate the plugin license in order to proceed with the update process.', 'sensei-lms' ),
				$plugin_name,
				$latest_version
			),
			[ 'strong' => [] ]
		);

		return $notice;
	}

	/**
	 * Get the settings for a plugin update notice on a deactivated and licensed plugin.
	 *
	 * @param array $plugin_data The plugin update data.
	 *
	 * @return array|null
	 */
	private function get_deactivated_plugin_update_notice( array $plugin_data ) {
		$plugin_name    = $plugin_data['name'];
		$plugin_file    = $plugin_data['plugin_basename'];
		$latest_version = $plugin_data['latest_version'];

		$notice            = $this->get_base_plugin_notice( $plugin_data );
		$notice['message'] = wp_kses(
			sprintf(
				// translators: First placeholder is the plugin name and second placeholder is the latest version available.
				__( 'There is a new version of <strong>%1$s</strong> available (%2$s). Please activate the plugin in order to proceed with the update process.', 'sensei-lms' ),
				$plugin_name,
				$latest_version
			),
			[ 'strong' => [] ]
		);

		$notice['actions'][] = [
			'label' => __( 'Activate', 'sensei-lms' ),
			'url'   => add_query_arg( '_wpnonce', wp_create_nonce( 'activate-plugin_' . $plugin_file ), self_admin_url( 'plugins.php?action=activate&plugin=' . $plugin_file ) ),
		];

		return $notice;
	}

	/**
	 * Get the settings for a plugin update notice on a licensed plugin.
	 *
	 * @param array $plugin_data The plugin update data.
	 *
	 * @return array|null
	 */
	private function get_plugin_update_notice( array $plugin_data ): array {
		$plugin_name    = $plugin_data['name'];
		$plugin_file    = $plugin_data['plugin_basename'];
		$latest_version = $plugin_data['latest_version'];

		$notice            = $this->get_base_plugin_notice( $plugin_data );
		$notice['message'] = wp_kses(
			sprintf(
					// translators: First placeholder is the plugin name and second placeholder is the latest version available.
				__( 'There is a new version of <strong>%1$s</strong> available (%2$s). Please update to ensure you have the latest features and fixes.', 'sensei-lms' ),
				$plugin_name,
				$latest_version
			),
			[ 'strong' => [] ]
		);

		$notice['actions'][] = [
			'label' => __( 'Update', 'sensei-lms' ),
			'url'   => add_query_arg( '_wpnonce', wp_create_nonce( 'upgrade-plugin_' . $plugin_file ), self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $plugin_file ) ),
		];

		return $notice;
	}

	/**
	 * Get the plugin updates available based on the SenseiLMS.com API.
	 *
	 * @param array $versions  The version data from the SenseiLMS.com API.
	 *
	 * @return array
	 */
	private function get_plugins_with_updates( $versions ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins_with_updates = [];
		$plugins              = get_plugins();

		foreach ( $plugins as $plugin_basename => $plugin_data ) {
			$plugin_slug = dirname( $plugin_basename );
			if ( ! isset( $versions[ $plugin_slug ]['version'] ) ) {
				continue;
			}

			$plugin_version = $plugin_data['Version'];
			$latest_version = $versions[ $plugin_slug ]['version'];

			if ( version_compare( $plugin_version, $latest_version, '<' ) ) {
				$plugins_with_updates[ $plugin_slug ] = [
					'name'            => $plugin_data['Name'],
					'plugin_basename' => $plugin_basename,
					'plugin_version'  => $plugin_version,
					'latest_version'  => $latest_version,
					'changelog'       => $versions[ $plugin_slug ]['changelog'] ?? null,
					'licensed'        => $versions[ $plugin_slug ]['licensed'] ?? false,
					'active'          => is_plugin_active( $plugin_basename ),
				];
			}
		}

		return $plugins_with_updates;
	}

}
