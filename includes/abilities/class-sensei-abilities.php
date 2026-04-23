<?php
/**
 * Sensei Abilities registration.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Sensei abilities with the WordPress Abilities API.
 *
 * Requires WordPress 6.9+ (Abilities API). On earlier versions the
 * init is a no-op — the class loads but registers nothing.
 */
class Sensei_Abilities {

	const CATEGORY_SLUG = 'sensei';

	/**
	 * Initialize the abilities registration.
	 *
	 * No-ops on WordPress versions without the Abilities API (<6.9).
	 */
	public static function init(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register the Sensei ability category.
	 */
	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY_SLUG,
			array(
				'label'       => __( 'Sensei', 'sensei-lms' ),
				'description' => __( 'Abilities for interacting with Sensei LMS.', 'sensei-lms' ),
			)
		);
	}

	/**
	 * Register all Sensei abilities.
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_get_courses_ability();
	}

	/**
	 * Register the sensei/get-courses ability.
	 */
	private static function register_get_courses_ability(): void {
		$course_item_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'          => array( 'type' => 'integer' ),
				'title'       => array( 'type' => 'string' ),
				'status'      => array( 'type' => 'string' ),
				'url'         => array( 'type' => 'string' ),
				'teacher'     => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'display_name' => array( 'type' => 'string' ),
						'user_login'   => array( 'type' => 'string' ),
					),
				),
				'categories'  => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array( 'type' => 'integer' ),
							'slug' => array( 'type' => 'string' ),
							'name' => array( 'type' => 'string' ),
						),
					),
				),
				'created_at'  => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
				'modified_at' => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
			),
		);

		wp_register_ability(
			'sensei/get-courses',
			array(
				'label'               => __( 'Get courses', 'sensei-lms' ),
				'description'         => __( 'List Sensei courses. Teachers see only their own.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(
						'ids'        => array(
							'type'        => 'array',
							'description' => __( 'Fetch specific courses by their IDs.', 'sensei-lms' ),
							'items'       => array( 'type' => 'integer' ),
						),
						'teachers'   => array(
							'type'        => 'array',
							'description' => __( 'Filter by one or more teacher user IDs. Ignored for non-admin callers, who always see only their own courses.', 'sensei-lms' ),
							'items'       => array( 'type' => 'integer' ),
						),
						'categories' => array(
							'type'        => 'array',
							'description' => __( 'Filter by course-category taxonomy slugs.', 'sensei-lms' ),
							'items'       => array( 'type' => 'string' ),
						),
						'status'     => array(
							'type'        => 'string',
							'description' => __( 'Filter by post status.', 'sensei-lms' ),
							'enum'        => array( 'publish', 'draft', 'pending', 'private', 'any' ),
							'default'     => 'any',
						),
						'search'     => array(
							'type'        => 'string',
							'description' => __( 'Search course titles and content.', 'sensei-lms' ),
						),
						'after'      => array(
							'type'        => 'string',
							'format'      => 'date-time',
							'description' => __( 'Only return courses created on or after this ISO 8601 date-time.', 'sensei-lms' ),
						),
						'before'     => array(
							'type'        => 'string',
							'format'      => 'date-time',
							'description' => __( 'Only return courses created on or before this ISO 8601 date-time.', 'sensei-lms' ),
						),
						'orderby'    => array(
							'type'        => 'string',
							'description' => __( 'Order results by this field.', 'sensei-lms' ),
							'enum'        => array( 'date', 'modified', 'title' ),
							'default'     => 'date',
						),
						'order'      => array(
							'type'        => 'string',
							'description' => __( 'Sort direction.', 'sensei-lms' ),
							'enum'        => array( 'asc', 'desc' ),
							'default'     => 'desc',
						),
						'page'       => array(
							'type'        => 'integer',
							'description' => __( 'Page number for paginated results.', 'sensei-lms' ),
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page'   => array(
							'type'        => 'integer',
							'description' => __( 'Number of courses to return per page (max 100).', 'sensei-lms' ),
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'items'       => array(
							'type'  => 'array',
							'items' => $course_item_schema,
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_get_courses' ),
				'permission_callback' => array( __CLASS__, 'can_edit_courses' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute sensei/get-courses.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public static function execute_get_courses( $input = array() ): array {
		$args = array(
			'post_type'      => 'course',
			'post_status'    => $input['status'] ?? 'any',
			'posts_per_page' => min( 100, (int) ( $input['per_page'] ?? 20 ) ),
			'paged'          => max( 1, (int) ( $input['page'] ?? 1 ) ),
			'orderby'        => $input['orderby'] ?? 'date',
			'order'          => strtoupper( $input['order'] ?? 'desc' ),
		);

		if ( ! empty( $input['ids'] ) ) {
			$args['post__in'] = array_map( 'intval', $input['ids'] );
		}

		// Teachers can only see their own courses; ignore a `teachers` filter that tries to widen the scope.
		if ( ! current_user_can( 'edit_others_courses' ) ) {
			$args['author__in'] = array( get_current_user_id() );
		} elseif ( ! empty( $input['teachers'] ) ) {
			$args['author__in'] = array_map( 'intval', $input['teachers'] );
		}

		if ( ! empty( $input['categories'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'course-category',
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', $input['categories'] ),
				),
			);
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = $input['search'];
		}

		if ( ! empty( $input['after'] ) || ! empty( $input['before'] ) ) {
			$date_query = array();
			if ( ! empty( $input['after'] ) ) {
				$date_query['after'] = $input['after'];
			}
			if ( ! empty( $input['before'] ) ) {
				$date_query['before'] = $input['before'];
			}
			$date_query['inclusive'] = true;
			$args['date_query']      = array( $date_query );
		}

		$query = new WP_Query( $args );

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$teacher = get_userdata( (int) $post->post_author );

			$terms      = get_the_terms( $post->ID, 'course-category' );
			$categories = array();
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$categories[] = array(
						'id'   => (int) $term->term_id,
						'slug' => $term->slug,
						'name' => $term->name,
					);
				}
			}

			$items[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'status'      => $post->post_status,
				'url'         => (string) get_permalink( $post->ID ),
				'teacher'     => array(
					'id'           => (int) $post->post_author,
					'display_name' => $teacher ? $teacher->display_name : '',
					'user_login'   => $teacher ? $teacher->user_login : '',
				),
				'categories'  => $categories,
				'created_at'  => mysql_to_rfc3339( $post->post_date_gmt ),
				'modified_at' => mysql_to_rfc3339( $post->post_modified_gmt ),
			);
		}

		return array(
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * Permission check: user can edit courses.
	 */
	public static function can_edit_courses(): bool {
		return current_user_can( 'edit_courses' );
	}
}
