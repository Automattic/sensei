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

		$data    = $this->api->fetch( HOUR_IN_SECONDS, true );
		$plugins = $data['plugins'] ?? [];

		$extensions = array_map( [ $this, 'map_plugin' ], $plugins );

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
		 * @property {float} price The extension price.
		 * @property {string} link URL to get more information about the extension.
		 *
		 * @param {Extension[]} $extensions
		 *
		 * @return {Extension[]} The actual extensions.
		 */
		return apply_filters( 'sensei_home_extensions', $extensions );
	}


	/**
	 * Transform remote plugin data into extension format.
	 *
	 * @param array $remote_plugin_data Plugin information on remote.
	 * @return array
	 */
	private function map_plugin( $remote_plugin_data ) {

		return [
			'title'        => $remote_plugin_data['title'],
			'product_slug' => $remote_plugin_data['product_slug'],
			'excerpt'      => $remote_plugin_data['excerpt'] ?? null,
			'image'        => $remote_plugin_data['image_large'] ?? null,
			'price'        => $remote_plugin_data['price'] ?? null,
			'link'         => $remote_plugin_data['link'] ?? null,
		];
	}

}
