<?php
/**
 * File containing the Email_List_Table class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei_List_Table;
use WP_Post;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The class responsible for generating the email list table in the settings.
 *
 * @internal
 *
 * @since 4.12.0
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
			'cb'            => '<input type="checkbox" />',
			'description'   => __( 'Email', 'sensei-lms' ),
			'subject'       => __( 'Subject', 'sensei-lms' ),
			'last_modified' => __( 'Last Modified', 'sensei-lms' ),
		];

		/**
		 * Filter the columns that are displayed on the email list.
		 *
		 * @since 4.12.0
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
	 * Returns if current implementation uses native row actions.
	 *
	 * @return bool
	 */
	protected function has_native_row_actions() {
		return true;
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

		$is_available = $this->is_email_available( $post );

		$checkbox = sprintf(
			// translators: %1s: Title of the Email.
			'<label class="screen-reader-text">' . __( 'Select %1s', 'sensei-lms' ) . '</label>' .
			'<input id="cb-select-%2$s" type="checkbox" name="email[]" value="%2$s" />',
			$post->post_title,
			$post->ID
		);

		$description = $is_available ?
			sprintf(
				'<strong><a href="%1$s" class="row-title">%2$s</a></strong>%3$s',
				esc_url( get_edit_post_link( $post ) ),
				get_post_meta( $post->ID, '_sensei_email_description', true ),
				$this->row_actions( $actions )
			) : sprintf(
				'<strong class="sensei-email-unavailable">%1$s</strong><span class="awaiting-mod sensei-upsell-pro-badge">%2$s</span>%3$s',
				get_post_meta( $post->ID, '_sensei_email_description', true ),
				__( 'Pro', 'sensei-lms' ),
				$this->row_actions( $actions )
			);

		$subject = esc_html( $title );

		$last_modified = sprintf(
			/* translators: Time difference between two dates. %s: Number of seconds/minutes/etc. */
			__( '%s ago', 'sensei-lms' ),
			human_time_diff( strtotime( get_gmt_from_date( $post->post_modified ) ) )
		);

		$row_data = [
			'cb'            => $checkbox,
			'description'   => $description,
			'subject'       => $subject,
			'last_modified' => $last_modified,
		];

		/**
		 * Filter the row data displayed on the email list.
		 *
		 * @since 4.12.0
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
		$is_available = $this->is_email_available( $post );

		return $is_published && $is_available
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
		$is_available = $this->is_email_available( $post );
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

		$actions['preview-email'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			Email_Preview::get_preview_link( $post->ID ),
			/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'sensei-lms' ), $title ) ),
			__( 'Preview', 'sensei-lms' )
		);

		if ( ! $is_available ) {
			$actions = [
				'upgrade-to-pro' => sprintf(
					'<a href="%1$s" aria-label="%2$s">%2$s</a>',
					esc_url( 'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=email_customization_pro' ),
					__( 'Upgrade to Sensei Pro', 'sensei-lms' )
				),
			];
		}
		/**
		 * Filter the row actions displayed on the email list.
		 *
		 * @since 4.12.0
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
	 * @since 4.12.0
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

	/**
	 * Check if the email is available.
	 *
	 * @param \WP_Post $post The email post.
	 *
	 * @return boolean True if the email is available, false otherwise.
	 */
	private function is_email_available( $post ) {
		$available = ! get_post_meta( $post->ID, '_sensei_email_is_pro', true );

		/**
		 * Filter if the email is available.
		 *
		 * @since 4.12.0
		 * @hook sensei_email_is_available
		 *
		 * @param {boolean} $available True if the email is available, false otherwise.
		 * @param {object}  $post The post.
		 * @param {object}  $list_table Email_List_Table instance.
		 *
		 * @return {boolean}
		 */
		return apply_filters( 'sensei_email_is_available', $available, $post, $this );
	}
}
