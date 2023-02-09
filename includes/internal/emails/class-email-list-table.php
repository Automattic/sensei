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
 * The class responsible for generating the email list table in the settings.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_List_Table extends Sensei_List_Table {
	/**
	 * The email group that will be listed.
	 *
	 * @var string|null
	 */
	private $group;

	/**
	 * The constructor.
	 *
	 * @param string|null $group The email group.
	 */
	public function __construct( string $group = null ) {
		$this->group = $group;

		parent::__construct( 'emails' );

		// Remove the search form.
		remove_action( 'sensei_before_list_table', [ $this, 'table_search_form' ], 5 );
	}

	/**
	 * Define the columns that are going to be used in the table.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'subject'       => __( 'Subject', 'sensei-lms' ),
			'description'   => __( 'Description', 'sensei-lms' ),
			'last_modified' => __( 'Last Modified', 'sensei-lms' ),
		];

		/**
		 * Filter the columns that are displayed in email list.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_list_columns
		 *
		 * @param {array}  $columns    The table columns.
		 * @param {object} $list_table Email_List_Table instance.
		 *
		 * @return {array} The modified table columns.
		 */
		return apply_filters( 'sensei_email_list_columns', $columns, $this );
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'sensei_emails_per_page' );
		$pagenum  = $this->get_pagenum();
		$offset   = $pagenum > 1 ? $per_page * ( $pagenum - 1 ) : 0;

		$query_args = [
			'post_type'      => Email_Post_Type::POST_TYPE,
			'posts_per_page' => $per_page,
			'offset'         => $offset,
		];

		if ( $this->group ) {
			$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Query limited by pagination.
				[
					'key'   => 'sensei_email_group', // TODO: Replace the meta key by a constant defined elsewhere.
					'value' => $this->group,
				],
			];
		}

		$query = new WP_Query( $query_args );

		$this->items = $query->posts;

		$this->set_pagination_args(
			[
				'total_items' => $query->found_posts,
				'total_pages' => $query->max_num_pages,
				'per_page'    => $per_page,
			]
		);
	}

	/**
	 * Get the data for each row.
	 *
	 * @param \WP_Post $item The email post.
	 *
	 * @return array
	 */
	protected function get_row_data( $item ) {
		$title = _draft_or_post_title( $item );

		$actions['edit'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			get_edit_post_link( $item->ID ),
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'sensei-lms' ), $title ) ),
			__( 'Edit', 'sensei-lms' )
		);

		// TODO: Add the "Disable" action.

		$subject = sprintf(
			'<strong><a href="%s" class="row-title">%s</a></strong>%s',
			esc_url( get_edit_post_link( $item ) ),
			esc_html( $title ),
			$this->row_actions( $actions )
		);

		$description = get_post_meta( $item->ID, 'sensei_email_description', true );

		$last_modified = sprintf(
			/* translators: Time difference between two dates. %s: Number of seconds/minutes/etc. */
			__( '%s ago', 'sensei-lms' ),
			human_time_diff( strtotime( $item->post_modified_gmt ) )
		);

		$row_data = [
			'subject'       => $subject,
			'description'   => $description,
			'last_modified' => $last_modified,
		];

		/**
		 * Filter sensei_learner_admin_get_row_data, for adding/removing row data.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_list_row_data
		 *
		 * @param {array}  $row_data The Row Data.
		 * @param {object} $post The post instance.
		 * @param {object} $list_table Email_List_Table instance.
		 *
		 * @return {array}
		 */
		return apply_filters( 'sensei_email_list_row_data', $row_data, $item, $this );
	}
}
