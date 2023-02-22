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
	 * The WP_Query instance.
	 *
	 * @var WP_Query
	 */
	private $query;

	/**
	 * The constructor.
	 *
	 * @internal
	 *
	 * @param WP_Query|null $query The WP_Query instance.
	 */
	public function __construct( WP_Query $query = null ) {
		$this->query = $query ? $query : new WP_Query();

		parent::__construct( 'email' );

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
			'cb'            => '<input type="checkbox" />',
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

		$query_args = [
			'post_type'      => Email_Post_Type::POST_TYPE,
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'meta_key'       => '_sensei_email_description', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Query limited by pagination.
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		];

		if ( $type ) {
			$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Query limited by pagination.
				[
					'key'   => '_sensei_email_type', // TODO: Replace the meta key by a constant defined elsewhere.
					'value' => $type,
				],
			];
		}

		$this->query->query( $query_args );

		$this->items = $this->query->posts;

		$this->set_pagination_args(
			[
				'total_items' => $this->query->found_posts,
				'total_pages' => $this->query->max_num_pages,
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

		$checkbox = sprintf(
			// translators: %1s: Title of the Email.
			'<label class="screen-reader-text">' . __( 'Select %1s', 'sensei-lms' ) . '</label>' .
			'<input id="cb-select-%2$s" type="checkbox" name="email[]" value="%2$s" />',
			$post->post_title,
			$post->ID
		);

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
			'cb'            => $checkbox,
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

	/**
	 * Display table content wrapped inside a form
	 *
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	public function display() {
		echo '<form id="posts-filter" action="' . esc_url( admin_url( 'edit.php' ) ) . '" method="get">';
		parent::display();
		echo '<input type="hidden" name="post_type" value="' . esc_attr( Email_Post_Type::POST_TYPE ) . '">';
		wp_nonce_field( 'sensei_email_bulk_action' );
		echo '</form>';
	}

	/**
	 * Display the bulk actions.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *
	 * @return void
	 */
	public function bulk_actions( $which = '' ) {
		if ( 'top' !== $which ) {
			return;
		}

		parent::bulk_actions( $which );
	}

	/**
	 * Get the bulk actions that are available for the table.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return [
			'bulk-disable-email' => __( 'Disable', 'sensei-lms' ),
			'bulk-enable-email'  => __( 'Enable', 'sensei-lms' ),
		];
	}
}
