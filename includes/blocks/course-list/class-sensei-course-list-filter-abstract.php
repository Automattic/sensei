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
	 * Get the name of the filter.
	 */
	public function get_filter_name(): string {
		return static::FILTER_NAME;
	}

	/**
	 * Get the content to be rendered inside the filter block.
	 *
	 * @param int $query_id The id of the Query block this filter is rendering inside.
	 */
	abstract public function get_content( int $query_id ): string;

	/**
	 * Get a list of course Ids to be excluded from the course list block.
	 *
	 * @param int $query_id The id of the Query block this filter is rendering inside.
	 */
	abstract public function get_course_ids_to_be_excluded( int $query_id ): array;

}
