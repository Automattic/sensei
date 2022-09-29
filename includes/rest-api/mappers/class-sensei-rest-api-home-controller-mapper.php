<?php
/**
 * File containing Sensei_REST_API_Home_Controller_Mapper class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class responsible for mapping Sensei Home domain classes to REST structure.
 */
class Sensei_REST_API_Home_Controller_Mapper {

	/**
	 * Maps an array of Sensei_Home_Quick_Links_Category to a basic array structure to be used as response for the REST API.
	 *
	 * @param Sensei_Home_Quick_Links_Category[] $categories A list of Quick Links categories.
	 * @return array
	 */
	public function map_quick_links( array $categories ): array {
		return array_map( [ $this, 'map_quick_links_category' ], $categories );
	}

	/**
	 * Maps a Quick Links category to its REST representation.
	 *
	 * @param Sensei_Home_Quick_Links_Category $category The quick links category to map.
	 * @return array
	 */
	private function map_quick_links_category( Sensei_Home_Quick_Links_Category $category ): array {
		return [
			'title' => $category->get_title(),
			'items' => array_map( [ $this, 'map_quick_links_item' ], $category->get_items() ),
		];
	}

	/**
	 * Maps a Quick Links item to its REST representation.
	 * Url might represent some special actions by using the `sensei://` protocol.
	 * Check `Sensei_Home_Quick_Links_Provider::ACTION_*` constants for available values.
	 *
	 * @param Sensei_Home_Quick_Links_Item $item The quick links item to map.
	 * @return array
	 */
	private function map_quick_links_item( Sensei_Home_Quick_Links_Item $item ): array {
		return [
			'title' => $item->get_title(),
			'url'   => $item->get_url(),
		];
	}

	/**
	 * Maps an array of Sensei_Home_Help_Category to a basic array structure to be used as response for the REST API.
	 *
	 * @param Sensei_Home_Help_Category[] $categories A list of Help categories.
	 * @return array
	 */
	public function map_help( array $categories ): array {
		return array_map( [ $this, 'map_help_category' ], $categories );
	}

	/**
	 * Maps a Help category to its REST representation.
	 *
	 * @param Sensei_Home_Help_Category $category The help category to map.
	 * @return array
	 */
	private function map_help_category( Sensei_Home_Help_Category $category ): array {
		return [
			'title' => $category->get_title(),
			'items' => array_map( [ $this, 'map_help_item' ], $category->get_items() ),
		];
	}

	/**
	 * Maps a Help item to its REST representation.
	 *
	 * @param Sensei_Home_Help_Item $item The help item to map.
	 * @return array
	 */
	private function map_help_item( Sensei_Home_Help_Item $item ): array {
		return [
			'title'      => $item->get_title(),
			'url'        => $item->get_url(),
			'icon'       => $item->get_icon(),
			'extra_link' => ! is_null( $item->get_extra_link() ) ? $this->map_help_extra_link( $item->get_extra_link() ) : null,
		];
	}

	/**
	 * Maps an extra link for a Help item to its REST representation.
	 *
	 * @param Sensei_Home_Help_Extra_Link $link The help extra link to map.
	 * @return array
	 */
	private function map_help_extra_link( Sensei_Home_Help_Extra_Link $link ): array {
		return [
			'label' => $link->get_label(),
			'url'   => $link->get_url(),
		];
	}

	/**
	 * Maps a Sensei_Home_Promo_Banner to a basic array structure to be used as response for the REST API.
	 *
	 * @param Sensei_Home_Promo_Banner $banner All the information relative to the promotional banner.
	 *
	 * @return array
	 */
	public function map_promo_banner( Sensei_Home_Promo_Banner $banner ): array {
		return [
			'is_visible' => $banner->is_visible(),
		];
	}

	/**
	 * Maps a Sensei_Home_Tasks to a basic array structure to be used as response for the REST API.
	 *
	 * @param Sensei_Home_Tasks $tasks The tasks structure.
	 *
	 * @return array
	 */
	public function map_tasks( Sensei_Home_Tasks $tasks ): array {
		$mapped = [];
		foreach ( $tasks->get_items() as $task ) {
			$mapped[ $task->get_id() ] = $this->map_task( $task );
		}

		return [
			/**
			 * Filter to alter the list of tasks that will be sent to the frontend.
			 * New tasks can be added and existing tasks modified or removed.
			 * Each item must have:
			 * - title: the title of the task.
			 * - priority: number used in frontend to sort tasks.
			 * - url: optional string with a url users will be taken when clicking on the task.
			 * - image: optional string with a path/url to a featured image.
			 * - done: boolean to mark task as done or not.
			 *
			 * @hook sensei_home_tasks_items
			 * @since $$next-version$$
			 *
			 * @param array $tasks An array indexed by task ID.
			 *
			 * @return {bool}
			 */
			'items' => apply_filters( 'sensei_home_tasks_items', $mapped ),
		];
	}

	/**
	 * Maps a specific Sensei_Home_Task to a basic array structure. Will execute the code in `is_completed` for all entries.
	 *
	 * @param Sensei_Home_Task $task The actual task to map.
	 * @return array
	 */
	private function map_task( Sensei_Home_Task $task ): array {
		return [
			'title'    => $task->get_title(),
			'priority' => $task->get_priority(),
			'url'      => $task->get_url(),
			'image'    => $task->get_image(),
			'done'     => $task->is_completed(),
		];
	}
}
