<?php
/**
 * File containing Sensei_Home_Guides_Provider class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that generates all the information relevant to the guides section in the Sensei Home screen.
 */
class Sensei_Home_Guides_Provider {
	/**
	 * The remote data helper.
	 *
	 * @var Sensei_Home_Remote_Data_API $remote_data_api
	 */
	private $remote_data_api;

	/**
	 * Sensei_Home_Guides_Provider constructor.
	 *
	 * @param Sensei_Home_Remote_Data_API $remote_data_api The remote data helper.
	 */
	public function __construct( Sensei_Home_Remote_Data_API $remote_data_api ) {
		$this->remote_data_api = $remote_data_api;
	}

	/**
	 * Returns all the information for the guides section.
	 *
	 * @return array|null
	 */
	public function get() {
		$remote_data = $this->remote_data_api->fetch( HOUR_IN_SECONDS );
		if (
			! $remote_data
			|| $remote_data instanceof WP_Error
			|| ! isset( $remote_data['guides'] )
			|| ! isset( $remote_data['guides']['items'] )
			|| ! is_array( $remote_data['guides']['items'] )
		) {
			return null;
		}

		$guides          = [
			'items'    => $remote_data['guides']['items'],
			'more_url' => $remote_data['guides']['more_url'] ?? null,
		];
		$guides['items'] = array_filter( array_map( [ $this, 'format_item' ], $guides['items'] ) );

		return $guides;
	}

	/**
	 * Correctly format a guides item.
	 *
	 * @param array $item The guides item.
	 *
	 * @return array|null
	 */
	private function format_item( $item ) {
		if ( ! isset( $item['title'] ) || ! isset( $item['url'] ) ) {
			return null;
		}

		return [
			'title' => $item['title'],
			'url'   => $item['url'],
		];
	}
}
