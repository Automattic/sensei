<?php
/**
 * File containing the class Sensei_Course_List_Filter_Abstract.
 *
 * @package sensei
 */

/**
 * Class Sensei_Course_List_Filter_Abstract.
 *
 * @since 4.6.4
 */
abstract class Sensei_Course_List_Filter_Abstract {
	/**
	 * Name of the filter.
	 *
	 * @var string
	 */
	const FILTER_NAME = '';

	/**
	 * Unique key for the filter param.
	 *
	 * @var string
	 */
	const PARAM_KEY = '';

	/**
	 * Get the content to be rendered inside the filter block.
	 *
	 * @param WP_Block $block The block instance.
	 */
	abstract public function get_content( WP_Block $block ): string;

	/**
	 * Get a list of course Ids to be excluded from the course list block.
	 *
	 * @param int $query_id The id of the Query block this filter is rendering inside.
	 */
	abstract public function get_course_ids_to_be_excluded( int $query_id ): array;

	/**
	 * Set filter keys in GET request with default filter values.
	 *
	 * @param int   $query_id The id of the Query block this filter is rendering inside.
	 * @param array $default_options The default options for the filter.
	 */
	public function set_default_option_as_filter_query_param( int $query_id, array $default_options ): void {
		$filter_param_key = static::PARAM_KEY . $query_id;
		$default_option   = $default_options[ static::FILTER_NAME ] ?? '';

		if ( ! key_exists( $filter_param_key, $_GET ) && ! empty( $default_option ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Argument is used to filter courses.
			$_GET[ $filter_param_key ] = $default_option;
		}
	}
}
