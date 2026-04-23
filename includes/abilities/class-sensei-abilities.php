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
		self::register_get_students_ability();
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

	/**
	 * Register the sensei/get-students ability.
	 */
	private static function register_get_students_ability(): void {
		wp_register_ability(
			'sensei/get-students',
			array(
				'label'               => __( 'Get students', 'sensei-lms' ),
				'description'         => __( 'List Sensei learners, optionally filtered by course, progress state, or search.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(
						'ids'                     => array(
							'type'        => 'array',
							'description' => __( 'Fetch specific learners by user ID.', 'sensei-lms' ),
							'items'       => array( 'type' => 'integer' ),
						),
						'course'                  => array(
							'type'        => 'integer',
							'description' => __( 'Return only learners enrolled in this course ID.', 'sensei-lms' ),
						),
						'progress_status'         => array(
							'type'        => 'string',
							'description' => __( 'Filter by progress state on the specified course. Requires `course`.', 'sensei-lms' ),
							'enum'        => array( 'in-progress', 'completed', 'not-started' ),
						),
						'has_pending_submissions' => array(
							'type'        => 'boolean',
							'description' => __( 'Return only learners with ungraded quiz submissions. Scoped to `course` if provided.', 'sensei-lms' ),
						),
						'search'                  => array(
							'type'        => 'string',
							'description' => __( 'Search by display name, login, or email.', 'sensei-lms' ),
						),
						'page'                    => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page'                => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'items'       => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'              => array( 'type' => 'integer' ),
									'display_name'    => array( 'type' => 'string' ),
									'user_login'      => array( 'type' => 'string' ),
									'user_email'      => array( 'type' => 'string' ),
									'progress_status' => array(
										'type' => 'string',
										'enum' => array( 'in-progress', 'completed', 'not-started' ),
									),
								),
							),
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_get_students' ),
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
	 * Execute sensei/get-students.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public static function execute_get_students( $input = array() ): array {
		$per_page = min( 100, (int) ( $input['per_page'] ?? 20 ) );
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );

		$query_args = array(
			'number' => $per_page,
			'paged'  => $page,
			'fields' => 'ID',
		);

		if ( ! empty( $input['ids'] ) ) {
			$query_args['include'] = array_map( 'intval', $input['ids'] );
		}

		if ( ! empty( $input['search'] ) ) {
			$query_args['search']         = '*' . esc_attr( $input['search'] ) . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		if ( ! empty( $input['course'] ) ) {
			$enrolment    = Sensei_Course_Enrolment::get_course_instance( (int) $input['course'] );
			$enrolled_ids = $enrolment->get_enrolled_user_ids();

			if ( empty( $enrolled_ids ) ) {
				return array(
					'items'       => array(),
					'total'       => 0,
					'total_pages' => 0,
				);
			}

			$query_args['include'] = ! empty( $query_args['include'] )
				? array_intersect( $query_args['include'], $enrolled_ids )
				: $enrolled_ids;
		}

		$user_query = new WP_User_Query( $query_args );
		$user_ids   = $user_query->get_results();

		$items = array();
		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			$item = array(
				'id'           => (int) $user_id,
				'display_name' => $user->display_name,
				'user_login'   => $user->user_login,
				'user_email'   => $user->user_email,
			);

			if ( ! empty( $input['course'] ) ) {
				$item['progress_status'] = self::resolve_progress_status( (int) $user_id, (int) $input['course'] );
			}

			$items[] = $item;
		}

		if ( ! empty( $input['progress_status'] ) && ! empty( $input['course'] ) ) {
			$items = array_values(
				array_filter(
					$items,
					static fn( $item ) => ( $item['progress_status'] ?? null ) === $input['progress_status']
				)
			);
		}

		if ( ! empty( $input['has_pending_submissions'] ) ) {
			$course_filter = ! empty( $input['course'] ) ? (int) $input['course'] : null;
			$items         = array_values(
				array_filter(
					$items,
					static fn( $item ) => self::user_has_ungraded_submissions( (int) $item['id'], $course_filter )
				)
			);
		}

		return array(
			'items'       => $items,
			'total'       => (int) $user_query->get_total(),
			'total_pages' => $per_page > 0 ? (int) ceil( $user_query->get_total() / $per_page ) : 0,
		);
	}

	/**
	 * Whether a learner has any ungraded quiz submissions.
	 *
	 * @param int      $user_id   Learner user ID.
	 * @param int|null $course_id Optional course ID to scope the check.
	 */
	private static function user_has_ungraded_submissions( int $user_id, ?int $course_id ): bool {
		$lesson_query_args = array(
			'post_type'      => 'lesson',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		if ( $course_id ) {
			$lesson_query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_lesson_course',
					'value' => $course_id,
				),
			);
		}

		$lesson_ids = get_posts( $lesson_query_args );

		foreach ( $lesson_ids as $lesson_id ) {
			$status = Sensei_Utils::user_lesson_status( (int) $lesson_id, $user_id );
			if ( $status && 'ungraded' === $status->comment_approved ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine a learner's progress state on a course.
	 *
	 * @param int $user_id   Learner user ID.
	 * @param int $course_id Course post ID.
	 */
	private static function resolve_progress_status( int $user_id, int $course_id ): string {
		if ( Sensei_Utils::user_completed_course( $course_id, $user_id ) ) {
			return 'completed';
		}
		if ( Sensei_Utils::has_started_course( $course_id, $user_id ) ) {
			return 'in-progress';
		}
		return 'not-started';
	}
}
