<?php
/**
 * File containing the Email_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email_Repository class.
 *
 * @since 4.12.0
 */
class Email_Repository {

	/**
	 * Email identifier meta key.
	 *
	 * @var string
	 */
	private const META_IDENTIFIER = '_sensei_email_identifier';

	/**
	 * Email type meta key.
	 *
	 * @var string
	 */
	private const META_TYPE = '_sensei_email_type';

	/**
	 * Email description meta key.
	 *
	 * @param string
	 */
	private const META_DESCRIPTION = '_sensei_email_description';


	/**
	 * Email default page template
	 *
	 * @param string
	 */
	private const META_PAGE_TEMPLATE = '_wp_page_template';


	/**
	 * Email pro meta key.
	 *
	 * @param string
	 */
	private const META_IS_PRO = '_sensei_email_is_pro';

	/**
	 * Check if email exists for identifier.
	 *
	 * @internal
	 *
	 * @param string $identifier Email identifier.
	 * @return bool
	 */
	public function has( string $identifier ): bool {
		$query = new WP_Query(
			[
				'post_type'  => Email_Post_Type::POST_TYPE,
				'meta_key'   => self::META_IDENTIFIER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $identifier, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value,
			]
		);

		return $query->post_count > 0;
	}

	/**
	 * Create email for identifier.
	 *
	 * @internal
	 *
	 * @param string $identifier  Email identifier.
	 * @param array  $types       Email types.
	 * @param string $subject     Email subject.
	 * @param string $description Email description.
	 * @param string $content     Email content.
	 * @param bool   $is_pro      Is pro email.
	 * @param bool   $disabled    Is the email disabled.
	 *
	 * @return int|false Email post ID. Returns false if email already exists. Returns WP_Error on failure.
	 */
	public function create( string $identifier, array $types, string $subject, string $description, string $content, bool $is_pro = false, bool $disabled = false ) {
		if ( $this->has( $identifier ) ) {
			return false;
		}

		$email_data = [
			'post_status'  => $disabled ? 'draft' : 'publish',
			'post_type'    => Email_Post_Type::POST_TYPE,
			'post_title'   => $subject,
			'post_content' => $content,
			'meta_input'   => [
				self::META_IDENTIFIER    => $identifier,
				self::META_DESCRIPTION   => $description,
				self::META_PAGE_TEMPLATE => Email_Page_Template::SLUG,
				self::META_IS_PRO        => $is_pro,
			],
		];

		$email_id = wp_insert_post( $email_data );

		foreach ( $types as $type ) {
			add_post_meta( $email_id, self::META_TYPE, $type );
		}

		return $email_id;
	}

	/**
	 * Delete email for identifier.
	 *
	 * @internal
	 *
	 * @param string $identifier Email identifier.
	 * @return bool
	 */
	public function delete( string $identifier ): bool {
		$query = new WP_Query(
			[
				'select'     => 'ID',
				'post_type'  => Email_Post_Type::POST_TYPE,
				'meta_key'   => self::META_IDENTIFIER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $identifier, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		if ( 0 === $query->post_count ) {
			return false;
		}

		$result = true;
		foreach ( $query->posts as $post ) {
			$last_result = wp_delete_post( $post->ID, true );
			if ( ! $last_result ) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Get email for identifier.
	 *
	 * @internal
	 *
	 * @param string $identifier Email identifier.
	 * @return \WP_Post|null
	 */
	public function get( string $identifier ) {
		$query = new WP_Query(
			[
				'post_type'  => Email_Post_Type::POST_TYPE,
				'meta_key'   => self::META_IDENTIFIER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $identifier, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		if ( 0 === $query->post_count ) {
			return null;
		}

		return $query->posts[0];
	}

	/**
	 * Get all emails for type.
	 *
	 * @internal
	 *
	 * @param string|null $type Email type.
	 * @param int         $per_page Posts per page. Default 10.
	 * @param int         $offset Offset. Default 0.
	 * @return object Object with results.
	 *                `items` is an array of `WP_Post` objects.
	 *                `total_items` is the total number of results.
	 *                `total_pages` is the total number of pages.
	 */
	public function get_all( string $type = null, $per_page = 10, $offset = 0 ) {
		$query_args = [
			'post_type'      => Email_Post_Type::POST_TYPE,
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'meta_key'       => self::META_DESCRIPTION, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		];

		if ( $type ) {
			$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Query limited by pagination.
				[
					'key'   => self::META_TYPE,
					'value' => $type,
				],
			];
		}

		$query = new WP_Query( $query_args );

		$result = (object) [
			'items'       => $query->posts,
			'total_items' => $query->found_posts,
			'total_pages' => $query->max_num_pages,
		];

		return $result;
	}

	/**
	 * Returns if there are any emails in the repository.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public function has_emails() {
		$query = new WP_Query(
			[
				'post_type'      => Email_Post_Type::POST_TYPE,
				'posts_per_page' => 1,
			]
		);

		return 0 < (int) $query->post_count;
	}
}
