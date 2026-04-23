<?php
/**
 * Sensei Abilities registration.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;

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
		self::register_update_enrollment_ability();
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
					),
				),
				'categories'  => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array( 'type' => 'integer' ),
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
						'status'   => array(
							'type'        => 'string',
							'description' => __( 'Filter by post status.', 'sensei-lms' ),
							'enum'        => array( 'publish', 'draft', 'pending', 'private', 'any' ),
							'default'     => 'any',
						),
						'search'   => array(
							'type'        => 'string',
							'description' => __( 'Search course titles and content.', 'sensei-lms' ),
						),
						'page'     => array(
							'type'        => 'integer',
							'description' => __( 'Page number for paginated results.', 'sensei-lms' ),
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
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
		);

		if ( ! current_user_can( 'edit_others_courses' ) ) {
			$args['author__in'] = array( get_current_user_id() );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = $input['search'];
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
				'description'         => __( 'List Sensei students, optionally filtered by course, progress state, or search.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(
						'course'          => array(
							'type'        => 'integer',
							'description' => __( 'Return only students enrolled in this course ID.', 'sensei-lms' ),
						),
						'progress_status' => array(
							'type'        => 'string',
							'description' => __( 'Filter by progress state on the specified course. Requires `course`.', 'sensei-lms' ),
							'enum'        => array( Course_Progress_Interface::STATUS_IN_PROGRESS, Course_Progress_Interface::STATUS_COMPLETE ),
						),
						'search'          => array(
							'type'        => 'string',
							'description' => __( 'Search by display name, login, or email.', 'sensei-lms' ),
						),
						'page'            => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page'        => array(
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
									'user_email'      => array( 'type' => 'string' ),
									'progress_status' => array(
										'type' => 'string',
										'enum' => array( Course_Progress_Interface::STATUS_IN_PROGRESS, Course_Progress_Interface::STATUS_COMPLETE ),
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

		if ( ! empty( $input['search'] ) ) {
			$query_args['search']         = '*' . $input['search'] . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		if ( ! empty( $input['course'] ) ) {
			$enrolment    = Sensei_Course_Enrolment::get_course_instance( (int) $input['course'] );
			$enrolled_ids = $enrolment->get_enrolled_user_ids();

			// WP_User_Query treats an empty `include` as "no restriction" and returns every user,
			// so short-circuit here when no one is enrolled.
			if ( empty( $enrolled_ids ) ) {
				return array(
					'items'       => array(),
					'total'       => 0,
					'total_pages' => 0,
				);
			}

			$query_args['include'] = $enrolled_ids;
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
				'user_email'   => $user->user_email,
			);

			if ( ! empty( $input['course'] ) ) {
				$status = self::resolve_progress_status( (int) $user_id, (int) $input['course'] );
				if ( null !== $status ) {
					$item['progress_status'] = $status;
				}
			}

			$items[] = $item;
		}

		// Progress status is per-user-per-course state that can't be joined in WP_User_Query,
		// so filter after materializing the page.
		if ( ! empty( $input['progress_status'] ) && ! empty( $input['course'] ) ) {
			$items = array_values(
				array_filter(
					$items,
					static fn( $item ) => ( $item['progress_status'] ?? null ) === $input['progress_status']
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
	 * Determine a student's progress state on a course.
	 *
	 * Returns null when the student has no progress record for the course
	 * (matches Sensei's data model — there is no "not started" status).
	 *
	 * @param int $user_id   Student user ID.
	 * @param int $course_id Course post ID.
	 */
	private static function resolve_progress_status( int $user_id, int $course_id ): ?string {
		if ( Sensei_Utils::user_completed_course( $course_id, $user_id ) ) {
			return Course_Progress_Interface::STATUS_COMPLETE;
		}
		if ( Sensei_Utils::has_started_course( $course_id, $user_id ) ) {
			return Course_Progress_Interface::STATUS_IN_PROGRESS;
		}
		return null;
	}

	/**
	 * Register the sensei/update-enrollment ability.
	 */
	private static function register_update_enrollment_ability(): void {
		wp_register_ability(
			'sensei/update-enrollment',
			array(
				'label'               => __( 'Update enrollment', 'sensei-lms' ),
				'description'         => __( 'Enroll or remove students on Sensei courses.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'course_ids', 'user_ids', 'action' ),
					'properties'           => array(
						'course_ids' => array(
							'type'        => 'array',
							'description' => __( 'One or more course IDs to modify enrollment on.', 'sensei-lms' ),
							'items'       => array( 'type' => 'integer' ),
							'minItems'    => 1,
						),
						'user_ids'   => array(
							'type'        => 'array',
							'description' => __( 'One or more user IDs to enroll or remove.', 'sensei-lms' ),
							'items'       => array( 'type' => 'integer' ),
							'minItems'    => 1,
						),
						'action'     => array(
							'type'        => 'string',
							'description' => __( 'Whether to enroll or remove the listed users.', 'sensei-lms' ),
							'enum'        => array( 'enroll', 'remove' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => array( __CLASS__, 'execute_update_enrollment' ),
				'permission_callback' => array( __CLASS__, 'can_edit_courses_from_input' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute sensei/update-enrollment.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public static function execute_update_enrollment( $input ): array {
		$course_ids = array_map( 'intval', $input['course_ids'] );
		$user_ids   = array_map( 'intval', $input['user_ids'] );
		$action     = $input['action'];

		$controller = new Sensei_REST_API_Course_Students_Controller( 'sensei-internal/v1' );
		$request    = new WP_REST_Request( 'enroll' === $action ? 'POST' : 'DELETE', '' );
		$request->set_param( 'course_ids', $course_ids );
		$request->set_param( 'student_ids', $user_ids );

		$response = 'enroll' === $action
			? $controller->batch_create_items( $request )
			: $controller->batch_remove_items( $request );

		return array(
			'course_ids' => $course_ids,
			'action'     => $action,
			'results'    => $response->get_data(),
		);
	}

	/**
	 * Permission check: user can edit every course in the input.
	 *
	 * @param array $input Ability input.
	 */
	public static function can_edit_courses_from_input( $input = array() ): bool {
		if ( empty( $input['course_ids'] ) || ! is_array( $input['course_ids'] ) ) {
			return false;
		}
		$post_type = get_post_type_object( 'course' );
		if ( ! $post_type ) {
			return false;
		}
		foreach ( $input['course_ids'] as $course_id ) {
			$course = get_post( (int) $course_id );
			if ( ! $course || 'course' !== $course->post_type ) {
				return false;
			}
			if ( ! current_user_can( $post_type->cap->edit_post, (int) $course_id ) ) {
				return false;
			}
		}
		return true;
	}
}
