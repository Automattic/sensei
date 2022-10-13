<?php
/**
 * File containing the Sensei_Home_Extensions_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Home_Extensions_Provider class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Extensions_Provider {


	/**
	 * The Sensei Home remote data API.
	 *
	 * @var Sensei_Home_Remote_Data_API
	 */
	private $api;

	/**
	 * The constructor.
	 *
	 * @param Sensei_Home_Remote_Data_API $api The API to retrieve Sensei Home data from SenseiLMS.com.
	 */
	public function __construct( Sensei_Home_Remote_Data_API $api ) {
		$this->api = $api;
	}

	/**
	 * Get extensions information.
	 *
	 * @return array
	 */
	public function get() {
		// Retrieve plugins list from remote.
		$data           = $this->api->fetch( HOUR_IN_SECONDS, true );
		$remote_plugins = $data['plugins'] ?? [];

		// Retrieve installed plugins information.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$installed_plugins = get_plugins();

		$extensions = array_map(
			function ( $remote_plugin ) use ( $installed_plugins ) {

				$plugin_file    = $remote_plugin['plugin_file'] ?? null;
				$remote_version = $remote_plugin['version'] ?? null;

				$is_installed      = isset( $installed_plugins[ $plugin_file ] );
				$installed_version = $is_installed ? $installed_plugins[ $plugin_file ]['Version'] : null;

				return [
					// Remote plugin data.
					'title'             => $remote_plugin['title'],
					'product_slug'      => $remote_plugin['product_slug'],
					'excerpt'           => $remote_plugin['excerpt'] ?? null,
					'image'             => $remote_plugin['image_large'] ?? null,
					'price'             => $remote_plugin['price'] ?? null,
					'link'              => $remote_plugin['link'] ?? null,
					'version'           => $remote_version,
					'plugin_file'       => $plugin_file,
					// Installed status information.
					'is_installed'      => $is_installed,
					'is_activated'      => $is_installed && is_plugin_active( $plugin_file ),
					'installed_version' => $installed_version,
					'has_update'        => $is_installed && $remote_version && version_compare( $remote_version, $installed_version, '>' ),

				];
			},
			$remote_plugins
		);

		/**
		 * Filters the list of extensions that will be later displayed in the Sensei Home page.
		 *
		 * @since $$next-version$$
		 *
		 * @typedef Extension
		 * @type {object}
		 * @property {string} title The extension title.
		 * @property {string} product_slug The extension product slug.
		 * @property {string} description The extension description.
		 * @property {string} image URL to be used as extension image.
		 * @property {float}  price The extension price.
		 * @property {string} link URL to get more information about the extension.
		 * @property {string} version The latest version number.
		 * @property {string} plugin_file The main plugin file path.
		 * @property {bool}   is_installed Whether the extension is installed or not.
		 * @property {bool}   is_activated Whether the extension is activated or not.
		 * @property {string} installed_version Optional. The installed version.
		 * @property {bool}   has_update Optional. For installed extensions whether the update has an update available or not.
		 *
		 * @param {Extension[]} $extensions
		 *
		 * @return {Extension[]} The actual extensions.
		 */
		return apply_filters( 'sensei_home_extensions', $extensions );
	}

	/**
	 * Given a list of extensions and the currently installed plugins returns the former extended with some properties about installation status.
	 *
	 * @param array[] $extensions The list of extensions.
	 * @param array[] $installed_plugins The plugins currently installed.
	 * @return array
	 */
	private function add_installed_properties( $extensions, $installed_plugins ) {

		$result = [];

		foreach ( $extensions as $extension ) {
			$plugin_file = $extension['plugin_file'];

			$is_installed      = isset( $installed_plugins[ $plugin_file ] );
			$installed_version = $is_installed ? $installed_plugins[ $plugin_file ]['Version'] : null;

			$result[] = array_merge(
				$extension,
				[
					'is_installed'      => $is_installed,
					'is_activated'      => $is_installed && is_plugin_active( $plugin_file ),
					'installed_version' => $installed_version,
					'has_update'        => $is_installed && isset( $extension['version'] ) && version_compare( $extension['version'], $installed_version, '>' ),
				]
			);
		}

		return $result;
	}
}
