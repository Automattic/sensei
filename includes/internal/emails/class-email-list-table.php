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
	 * The Email_Repository instance.
	 *
	 * @var Email_Repository
	 */
	private $repository;

	/**
	 * The constructor.
	 *
	 * @internal
	 *
	 * @param Email_Repository $repository The Email_Repository instance.
	 */
	public function __construct( Email_Repository $repository ) {
		parent::__construct( 'email' );
		$this->repository = $repository;

		// Remove the search form.
		remove_action( 'sensei_before_list_table', [ $this, 'table_search_form' ], 5 );
	}

	/**
	 * Define the columns that are going to be used in the table.
	 *
	 * @internal
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
		 * Filter the columns that are displayed on the email list.
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
	 *
	 * @internal
	 *
	 * @param string|null $type The email type that will be listed.
	 */
	public function prepare_items( string $type = null ) {
		$per_page = $this->get_items_per_page( 'sensei_emails_per_page' );
		$pagenum  = $this->get_pagenum();
		$offset   = $pagenum > 1 ? $per_page * ( $pagenum - 1 ) : 0;
		$results  = $this->repository->get_all( $type, $per_page, $offset );

		$this->items = $results->items;
		$this->set_pagination_args(
			[
				'total_items' => $results->total_items,
				'total_pages' => $results->total_pages,
				'per_page'    => $per_page,
			]
		);
	}

	/**
	 * Get the data for each row.
	 *
	 * @param \WP_Post $post The email post.
	 *
	 * @return array
	 */
	protected function get_row_data( $post ) {
		$title   = _draft_or_post_title( $post );
		$actions = $this->get_row_actions( $post );

		$subject = sprintf(
			'<strong><a href="%s" class="row-title">%s</a></strong>%s',
			esc_url( get_edit_post_link( $post ) ),
			esc_html( $title ),
			$this->row_actions( $actions )
		);

		$description = get_post_meta( $post->ID, '_sensei_email_description', true );

		$last_modified = sprintf(
			/* translators: Time difference between two dates. %s: Number of seconds/minutes/etc. */
			__( '%s ago', 'sensei-lms' ),
			human_time_diff( strtotime( $post->post_modified_gmt ) )
		);

		$row_data = [
			'subject'       => $subject,
			'description'   => $description,
			'last_modified' => $last_modified,
		];

		/**
		 * Filter the row data displayed on the email list.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_list_row_data
		 *
		 * @param {array}  $row_data The row data.
		 * @param {object} $post The post.
		 * @param {object} $list_table Email_List_Table instance.
		 *
		 * @return {array}
		 */
		return apply_filters( 'sensei_email_list_row_data', $row_data, $post, $this );
	}

	/**
	 * Get the CSS class of the row.
	 *
	 * @param \WP_Post $post The current item.
	 *
	 * @return string
	 */
	protected function get_row_class( $post ): string {
		$is_published = 'publish' === get_post_status( $post );

		return $is_published
			? 'sensei-wp-list-table-row--enabled'
			: 'sensei-wp-list-table-row--disabled';
	}

	/**
	 * Get the row actions that are visible when hovering over the row.
	 *
	 * @param \WP_Post $post The email post.
	 *
	 * @return array
	 */
	private function get_row_actions( $post ): array {
		$title        = _draft_or_post_title( $post );
		$is_published = 'publish' === get_post_status( $post );
		$actions      = [];

		$actions['edit'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			get_edit_post_link( $post->ID ),
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'sensei-lms' ), $title ) ),
			__( 'Edit', 'sensei-lms' )
		);

		if ( $is_published ) {
			$actions['disable-email'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				wp_nonce_url( "post.php?action=disable-email&amp;post=$post->ID", 'disable-email-post_' . $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Disable &#8220;%s&#8221;', 'sensei-lms' ), $title ) ),
				__( 'Disable', 'sensei-lms' )
			);
		} else {
			$actions['enable-email'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				wp_nonce_url( "post.php?action=enable-email&amp;post=$post->ID", 'enable-email-post_' . $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Enable &#8220;%s&#8221;', 'sensei-lms' ), $title ) ),
				__( 'Enable', 'sensei-lms' )
			);
		}

		/**
		 * Filter the row actions displayed on the email list.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_list_row_actions
		 *
		 * @param {array}  $actions The row actions.
		 * @param {object} $post The post.
		 * @param {object} $list_table Email_List_Table instance.
		 *
		 * @return {array}
		 */
		return apply_filters( 'sensei_email_list_row_actions', $actions, $post, $this );
	}
}
