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
 * @since $$next-version$$
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
	 *
	 * @return int|false Email post ID. Returns false if email already exists. Returns WP_Error on failure.
	 */
	public function create( string $identifier, array $types, string $subject, string $description, string $content ) {
		if ( $this->has( $identifier ) ) {
			return false;
		}

		$email_data = [
			'post_status'  => 'publish',
			'post_type'    => Email_Post_Type::POST_TYPE,
			'post_title'   => $subject,
			'post_content' => $content,
			'meta_input'   => [
				self::META_IDENTIFIER  => $identifier,
				self::META_DESCRIPTION => $description,
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
	 * @param string $type Email type.
	 * @param int    $limit Limit. Default 10.
	 * @param int    $offset Offset. Default 0.
	 * @return \WP_Post[]
	 */
	public function get_all( string $type, $limit = 10, $offset = 0 ): array {
		return get_posts(
			[
				'post_type'      => Email_Post_Type::POST_TYPE,
				'meta_key'       => self::META_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => $limit,
				'offset'         => $offset,
			]
		);
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

		return 0 === (int) $query->post_count;
	}
}

