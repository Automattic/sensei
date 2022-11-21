<?php
/**
 * File containing Sensei_Home_News_Provider class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that generates all the information relevant to the news section in the Sensei Home screen.
 */
class Sensei_Home_News_Provider {
	/**
	 * The remote data helper.
	 *
	 * @var Sensei_Home_Remote_Data_API $remote_data_api
	 */
	private $remote_data_api;

	/**
	 * Sensei_Home_News_Provider constructor.
	 *
	 * @param Sensei_Home_Remote_Data_API $remote_data_api The remote data helper.
	 */
	public function __construct( Sensei_Home_Remote_Data_API $remote_data_api ) {
		$this->remote_data_api = $remote_data_api;
	}

	/**
	 * Returns all the information for the news section.
	 *
	 * @return array|null
	 */
	public function get() {
		$remote_data = $this->remote_data_api->fetch( HOUR_IN_SECONDS );
		if (
			! $remote_data
			|| $remote_data instanceof WP_Error
			|| ! isset( $remote_data['news'] )
			|| ! isset( $remote_data['news']['items'] )
			|| ! is_array( $remote_data['news']['items'] )
		) {
			return null;
		}

		$news          = [
			'items'    => $remote_data['news']['items'],
			'more_url' => $remote_data['news']['more_url'] ?? null,
		];
		$news['items'] = array_filter( array_map( [ $this, 'format_item' ], $news['items'] ) );

		return $news;
	}

	/**
	 * Correctly format a news item.
	 *
	 * @param array $item The news item.
	 *
	 * @return array|null
	 */
	private function format_item( $item ) {
		if ( ! isset( $item['title'] ) || ! isset( $item['url'] ) || ! isset( $item['date'] ) ) {
			return null;
		}

		$date_format    = get_option( 'date_format' );
		$formatted_date = date_i18n( $date_format, strtotime( $item['date'] ) );

		return [
			'title' => $item['title'],
			'url'   => $item['url'],
			'date'  => $formatted_date,
		];
	}
}
