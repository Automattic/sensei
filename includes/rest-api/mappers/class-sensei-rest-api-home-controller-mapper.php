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
	 * Maps a category to its REST representation.
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
	 * Maps an item to its REST representation.
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
}
