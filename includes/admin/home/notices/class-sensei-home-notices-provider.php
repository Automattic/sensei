<?php
/**
 * File containing Sensei_Home_Notices_Provider class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that formats the notices for the Sensei Home endpoint.
 */
class Sensei_Home_Notices_Provider {
	/**
	 * The admin notices helper.
	 *
	 * @var Sensei_Admin_Notices
	 */
	private $admin_notices;
	/**
	 * Screen ID to show notices on.
	 *
	 * @var string
	 */
	private $screen_id;

	/**
	 * Sensei_Home_News_Provider constructor.
	 *
	 * @param Sensei_Admin_Notices $admin_notices   The remote admin notices helper.
	 * @param string               $screen_id       The screen ID to show notices on.
	 */
	public function __construct( $admin_notices = null, $screen_id = null ) {
		$this->admin_notices = $admin_notices;
		$this->screen_id     = $screen_id;
	}

	/**
	 * Fallback for when we're in an environment that doesn't have `Sensei_Admin_Notices`.
	 *
	 * @param int|null $max_age The max age (seconds) of the source data.
	 *
	 * @return array
	 */
	private function local_only( $max_age = null ) : array {
		/**
		 * This filter is documented in `class-sensei-admin-notices.php`.
		 */
		$notices = apply_filters( 'sensei_admin_notices', [], $max_age );

		return array_filter(
			$notices,
			function( $notice_key ) {
				// We only care about home notices for now.
				return strpos( $notice_key, Sensei_Home_Notices::HOME_NOTICE_KEY_PREFIX ) === 0;
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Returns all the information for the notices section.
	 *
	 * @param int|null $max_age The max age (seconds) of the source data.
	 *
	 * @return array
	 */
	public function get( $max_age = HOUR_IN_SECONDS ): array {
		$notices = isset( $this->admin_notices ) ? $this->admin_notices->get_notices_to_display( $this->screen_id, $max_age ) : $this->local_only( $max_age );

		return array_map( [ $this, 'format_item' ], $notices );
	}

	/**
	 * Get the number of notices for the Sensei Home badge.
	 *
	 * @return int
	 */
	public function get_badge_count(): int {
		add_filter( 'sensei_home_remote_data_retry_error', '__return_false' );
		$notices = $this->get( DAY_IN_SECONDS );
		remove_filter( 'sensei_home_remote_data_retry_error', '__return_false' );

		return count( $notices );
	}

	/**
	 * Format a notice item.
	 *
	 * @param array $notice The unformatted notice.
	 * @return array
	 */
	private function format_item( $notice ) {
		$level = 'info';
		if ( array_key_exists( 'level', $notice ) ) {
			$level = $notice['level'];
		} elseif ( array_key_exists( 'style', $notice ) ) {
			$level = $notice['style'];
		}
		return [
			'level'       => $level,
			'heading'     => $notice['heading'] ?? null,
			'message'     => $notice['message'],
			'info_link'   => $notice['info_link'] ?? null,
			'actions'     => $notice['actions'] ?? [],
			// If we have Sensei_Admin_Notices, we consider it as being, by default, dismissible, given that
			// this is the default on Sensei_Admin_Notices.
			'dismissible' => $notice['dismissible'] ?? class_exists( 'Sensei_Admin_Notices' ),
		];
	}
}
