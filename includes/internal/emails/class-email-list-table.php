<?php
/**
 * File containing the Email_List_Table class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei_List_Table;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Email_List_Table
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_List_Table extends Sensei_List_Table {
	public function __construct() {
		parent::__construct( 'emails' );

		// Remove the search form.
		remove_action( 'sensei_before_list_table', array( $this, 'table_search_form' ), 5 );
	}

	public function get_columns() {
		return [
			'subject'       => __( 'Subject', 'sensei-lms' ),
			'description'   => __( 'Description', 'sensei-lms' ),
			'last_modified' => __( 'Last Modified', 'sensei-lms' ),
		];
	}

	public function prepare_items() {
		$query = new WP_Query(
			[
				'post_type'      => Email_Post_Type::POST_TYPE,
				'posts_per_page' => -1,
			]
		);

		$this->items = $query->posts;
	}

	protected function get_row_data( $item ) {
		return [
			'subject'       => '<a href="' . esc_url( get_edit_post_link( $item ) ) . '">' . esc_html( $item->post_title ) . '</a>',
			'description'   => 'description',
			'last_modified' => $item->post_modified,
		];
	}
}

