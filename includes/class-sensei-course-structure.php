<?php
/**
 * File containing the class Sensei_Course_Structure.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains methods for retrieving and saving a Sensei course's structure.
 *
 * @since 3.6.0
 */
class Sensei_Course_Structure {
	const PUBLISHED_POST_STATUSES = [ 'publish', 'private' ];

	/**
	 * Course instances.
	 *
	 * @var self[]
	 */
	private static $instances = [];

	/**
	 * The course post ID.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Get an instance of this class for a course.
	 *
	 * @param int $course_id The course post ID.
	 *
	 * @return static
	 */
	public static function instance( int $course_id ) : self {
		if ( ! isset( self::$instances[ $course_id ] ) ) {
			self::$instances[ $course_id ] = new static( $course_id );
		}

		return self::$instances[ $course_id ];
	}

	/**
	 * Sensei_Course_Structure constructor.
	 *
	 * @param int $course_id The course post ID.
	 */
	private function __construct( int $course_id ) {
		$this->course_id = $course_id;
	}

	/**
	 * Get the course structure.
	 *
	 * @see Sensei_Course_Structure::prepare_lesson()
	 * @see Sensei_Course_Structure::prepare_module()
	 *
	 * @param string  $context  Context that structure is being retrieved for. Possible values: edit, view.
	 * @param boolean $no_cache Avoid query cache.
	 *
	 * @return array {
	 *     An array which has course structure information.
	 *
	 *     @type array Each element is an array with either module or lesson information as defined in prepare_lesson()
	 *                 and prepare_module().
	 * }
	 */
	public function get( $context = 'view', $no_cache = false ) {
		if ( $no_cache ) {
			add_filter( 'posts_where', [ $this, 'filter_no_cache_where' ] );
		}

		$context = in_array( $context, [ 'view', 'edit' ], true ) ? $context : 'view';

		$structure = [];

		$published_lessons_only = 'view' === $context;
		$post_status            = $published_lessons_only ? self::PUBLISHED_POST_STATUSES : 'any';
		$no_module_lessons      = wp_list_pluck( Sensei()->modules->get_none_module_lessons( $this->course_id, $post_status ), 'ID' );

		$modules = $this->get_modules();
		foreach ( $modules as $module_term ) {
			$module = $this->prepare_module( $module_term, $post_status );

			if ( ! empty( $module['lessons'] ) || 'edit' === $context ) {
				$structure[] = $module;
			}
		}

		foreach ( $no_module_lessons as $lesson_id ) {
			$lesson = get_post( $lesson_id );
			if ( ! $lesson ) {
				continue;
			}

			$structure[] = $this->prepare_lesson( $lesson );
		}

		if ( $no_cache ) {
			remove_filter( 'posts_where', [ $this, 'filter_no_cache_where' ] );
		}

		return $structure;
	}

	/**
	 * Filter where adding an extra condition to avoid cache.
	 *
	 * @access private
	 *
	 * @param string $where Current where.
	 *
	 * @return string Where with extra condition to avoid cache.
	 */
	public function filter_no_cache_where( $where ) {
		$time = time();

		return $where . ' AND ' . $time . ' = ' . $time;
	}

	/**
	 * Prepare the result for a module.
	 *
	 * @see Sensei_Course_Structure::prepare_lesson()
	 *
	 * @param WP_Term      $module_term        Module term.
	 * @param array|string $lesson_post_status Lesson post status(es).
	 *
	 * @return array {
	 *     An array which has module information.
	 *
	 *     @type string $type        The type of the array which is 'module'.
	 *     @type int    $id          The module term id.
	 *     @type string $title       The module name.
	 *     @type string $description The module description.
	 *     @type array  $lessons     An array of the module lessons. See Sensei_Course_Structure::prepare_lesson().
	 * }
	 */
	private function prepare_module( WP_Term $module_term, $lesson_post_status ) : array {
		$lessons = $this->get_module_lessons( $module_term->term_id, $lesson_post_status );
		$module  = [
			'type'        => 'module',
			'id'          => $module_term->term_id,
			'title'       => $module_term->name,
			'description' => $module_term->description,
			'lessons'     => [],
		];

		foreach ( $lessons as $lesson ) {
			$module['lessons'][] = $this->prepare_lesson( $lesson );
		}

		return $module;
	}

	/**
	 * Prepare the result for a lesson.
	 *
	 * @param WP_Post $lesson_post Lesson post object.
	 *
	 * @return array {
	 *     An array which has lesson information.
	 *
	 *     @type string $type  The type of the array which is 'lesson'.
	 *     @type int    $id    The lesson post id.
	 *     @type string $title The lesson title.
	 *     @type bool   $draft True if the lesson is a draft.
	 * }
	 */
	private function prepare_lesson( WP_Post $lesson_post ) : array {
		return [
			'type'    => 'lesson',
			'id'      => $lesson_post->ID,
			'title'   => $lesson_post->post_title,
			'draft'   => 'draft' === $lesson_post->post_status,
			'preview' => Sensei_Utils::is_preview_lesson( $lesson_post->ID ),
		];
	}

	/**
	 * Get the lessons for a module.
	 *
	 * @param int          $module_term_id     Term ID for the module.
	 * @param array|string $lesson_post_status Lesson post status(es).
	 *
	 * @return WP_Post[]
	 */
	private function get_module_lessons( int $module_term_id, $lesson_post_status ) : array {
		$lessons_query = Sensei()->modules->get_lessons_query( $this->course_id, $module_term_id, $lesson_post_status );

		return $lessons_query instanceof WP_Query ? $lessons_query->posts : [];
	}

	/**
	 * Get module terms in the correct order.
	 *
	 * @return WP_Term[]
	 */
	private function get_modules() : array {
		$modules = Sensei()->modules->get_course_modules( $this->course_id );

		if ( is_wp_error( $modules ) ) {
			$modules = [];
		}

		return $modules;
	}

	/**
	 * Save a new course structure.
	 *
	 * @see Sensei_Course_Structure::get()
	 *
	 * @param array $raw_structure Course structure to save in its raw, un-sanitized form as returned by get().
	 *
	 * @return bool|WP_Error
	 */
	public function save( array $raw_structure ) {
		$structure = $this->sanitize_structure( $raw_structure );
		if ( is_wp_error( $structure ) ) {
			return $structure;
		}

		$current_structure                                 = $this->get( 'edit' );
		list( $current_lesson_ids, $current_module_ids,  ) = $this->flatten_structure( $current_structure );

		$lesson_ids   = [];
		$module_order = [];
		$lesson_order = [];
		foreach ( $structure as $item ) {
			if ( 'module' === $item['type'] ) {
				$save_module_result = $this->save_module( $item );
				if ( ! $save_module_result ) {
					return false;
				}

				list( $module_id, $module_lesson_ids ) = $save_module_result;

				$lesson_ids     = array_merge( $lesson_ids, $module_lesson_ids );
				$module_order[] = $module_id;

			} elseif ( 'lesson' === $item['type'] ) {
				$lesson_id = $this->save_lesson( $item );
				if ( ! $lesson_id ) {
					return false;
				}

				$lesson_ids[]   = $lesson_id;
				$lesson_order[] = $lesson_id;

				update_post_meta( $lesson_id, '_order_' . $this->course_id, count( $lesson_order ) );
			}
		}

		// Save the module association.
		$module_diff = array_diff( $current_module_ids, $module_order );
		if ( ! empty( $module_diff ) || count( $current_module_ids ) !== count( $module_order ) ) {
			wp_set_object_terms( $this->course_id, $module_order, 'module' );
		}

		// Save the module order.
		$this->save_module_order( $module_order );

		// Save the lesson order.
		update_post_meta( $this->course_id, '_lesson_order', implode( ',', $lesson_order ) );

		// Delete removed modules and lessons.
		$delete_lesson_ids = array_diff( $current_lesson_ids, $lesson_ids );
		foreach ( $delete_lesson_ids as $lesson_id ) {
			$this->clear_lesson_associations( $lesson_id );
		}

		return true;
	}

	/**
	 * Save a module item.
	 *
	 * @see Sensei_Course_Structure::prepare_module()
	 *
	 * @param array $item Item to save as returned from prepare_module().
	 *
	 * @return false|array[] {
	 *     If successful, we return this:
	 *
	 *     @type int   $0 $module_id  Saved module ID.
	 *     @type array $1 $lesson_ids All the lesson IDs from this module.
	 * }
	 */
	private function save_module( array $item ) {
		if ( $item['id'] ) {
			$term = get_term( $item['id'], 'module' );
			$slug = $this->get_module_slug( $item['title'] );

			// Slug has changed.
			if ( $term->slug !== $slug ) {
				$existing_module_id = $this->get_existing_module( $item['title'] );
				$module_id          = $existing_module_id ? $existing_module_id : $this->create_module( $item );
			} else {
				$module_id = $this->update_module( $item );
			}
		} else {
			$module_id = $this->create_module( $item );
		}

		if ( ! $module_id ) {
			return false;
		}

		$lesson_ids       = [];
		$lesson_order_key = '_order_module_' . $module_id;
		foreach ( $item['lessons'] as $lesson_item ) {
			$lesson_id = $this->save_lesson( $lesson_item, $module_id );
			if ( ! $lesson_id ) {
				return false;
			}

			wp_set_object_terms( $lesson_id, [ $module_id ], 'module' );
			update_post_meta( $lesson_id, $lesson_order_key, count( $lesson_ids ) );
			delete_post_meta( $lesson_id, '_order_' . $this->course_id );

			$lesson_ids[] = $lesson_id;
		}

		return [
			$module_id,
			$lesson_ids,
		];
	}

	/**
	 * Attempt to find an existing module available to the user.
	 *
	 * @param string $module_name Module name.
	 *
	 * @return int|null Term ID if found.
	 */
	private function get_existing_module( string $module_name ) {
		$slug            = $this->get_module_slug( $module_name );
		$existing_module = get_term_by( 'slug', $slug, 'module' );

		if ( $existing_module ) {
			return (int) $existing_module->term_id;
		}

		return null;
	}

	/**
	 * Create a module.
	 *
	 * @param array $item Item to create.
	 *
	 * @return false|int
	 */
	private function create_module( array $item ) {
		$args = [
			'description' => $item['description'],
			'slug'        => $this->get_module_slug( $item['title'] ),
		];

		$create_result = wp_insert_term( $item['title'], 'module', $args );
		if ( is_wp_error( $create_result ) ) {
			return false;
		}

		return (int) $create_result['term_id'];
	}

	/**
	 * Update an existing module.
	 *
	 * @param array $item Item to save.
	 *
	 * @return false|int
	 */
	private function update_module( array $item ) {
		$term = get_term( $item['id'], 'module' );

		$changed_args = [];
		if ( $term->name !== $item['title'] ) {
			$changed_args['name'] = $item['title'];
		}
		if ( $term->description !== $item['description'] ) {
			$changed_args['description'] = $item['description'];
		}

		if ( ! empty( $changed_args ) ) {
			$change_result = wp_update_term(
				$item['id'],
				'module',
				$changed_args
			);

			if ( is_wp_error( $change_result ) ) {
				return false;
			}
		}

		return $term->term_id;
	}

	/**
	 * Get module slug.
	 *
	 * @param string $title Module title.
	 *
	 * @return string Slug.
	 */
	private function get_module_slug( $title ) {
		$teacher_user_id = get_post( $this->course_id )->post_author;

		return user_can( $teacher_user_id, 'manage_options' )
			? sanitize_title( $title )
			: intval( $teacher_user_id ) . '-' . sanitize_title( $title );
	}

	/**
	 * Save module order.
	 *
	 * @param array $module_order Module order to save.
	 */
	private function save_module_order( array $module_order ) {
		$current_module_order_raw = get_post_meta( $this->course_id, '_module_order', true );
		$current_module_order     = $current_module_order_raw ? array_map( 'intval', $current_module_order_raw ) : [];

		if (
			( $current_module_order || ! empty( $module_order ) )
			&& ( $current_module_order !== $module_order )
		) {
			if ( empty( $module_order ) ) {
				delete_post_meta( $this->course_id, '_module_order' );
			} else {
				update_post_meta( $this->course_id, '_module_order', array_map( 'strval', $module_order ) );
			}
		}
	}

	/**
	 * Save a lesson item.
	 *
	 * @see Sensei_Course_Structure::prepare_lesson()
	 *
	 * @param array $item      Item to save.
	 * @param int   $module_id Module ID.
	 *
	 * @return false|int
	 */
	private function save_lesson( array $item, int $module_id = null ) {
		if ( $item['id'] ) {
			$lesson_id = $this->update_lesson( $item );
		} else {
			$lesson_id = $this->create_lesson( $item );
		}

		if ( $lesson_id ) {
			if ( ! $module_id ) {
				$module_id = [];
			}

			wp_set_object_terms( $lesson_id, $module_id, 'module' );
		}

		return $lesson_id;
	}

	/**
	 * Create a lesson.
	 *
	 * @param array $item Item to create.
	 *
	 * @return false|int
	 */
	private function create_lesson( array $item ) {
		$post_args = [
			'post_title'  => $item['title'],
			'post_type'   => 'lesson',
			'post_status' => 'draft',
			'meta_input'  => [
				'_lesson_course'  => $this->course_id,
				'_needs_template' => true,
			],
		];

		$post_id = wp_insert_post( $post_args );
		if ( ! $post_id ) {
			return false;
		}

		$this->create_quiz( $post_id );

		return $post_id;
	}

	/**
	 * Create an empty quiz for the lesson.
	 *
	 * @param int $lesson_id Lesson ID to create quiz for.
	 */
	private function create_quiz( int $lesson_id ) {
		$lesson = get_post( $lesson_id );

		$post_args = [
			'post_content' => '',
			'post_status'  => $lesson->post_status,
			'post_title'   => $lesson->post_title,
			'post_type'    => 'quiz',
			'post_parent'  => $lesson_id,
			'meta_input'   => [
				'_quiz_lesson' => $lesson_id,
			],
		];

		$quiz_id = wp_insert_post( $post_args );
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

	}

	/**
	 * Update an existing lesson.
	 *
	 * @param array $item Item to save.
	 *
	 * @return false|int
	 */
	private function update_lesson( array $item ) {
		$lesson = get_post( $item['id'] );
		if ( $lesson->post_title !== $item['title'] ) {
			$post_args = [
				'ID'         => $lesson->ID,
				'post_title' => $item['title'],
			];

			$update_result = wp_update_post( $post_args );
			if ( ! $update_result || is_wp_error( $update_result ) ) {
				return false;
			}
		}

		$current_course = (int) get_post_meta( $lesson->ID, '_lesson_course', true );
		if ( $this->course_id !== $current_course ) {
			$this->clear_lesson_associations( $lesson->ID );
			update_post_meta( $lesson->ID, '_lesson_course', $this->course_id );
		}

		return $lesson->ID;
	}

	/**
	 * Clear any previous associations a lesson had with a course.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	private function clear_lesson_associations( int $lesson_id ) {
		delete_post_meta( $lesson_id, '_lesson_course' );
		$lesson_modules = get_the_terms( $lesson_id, 'module' );
		if ( is_array( $lesson_modules ) ) {
			foreach ( $lesson_modules as $module ) {
				delete_post_meta( $lesson_id, '_order_module_' . $module->term_id );
			}
		}

		wp_set_object_terms( $lesson_id, [], 'module' );
	}

	/**
	 * Parses the lesson IDs and module IDs from the structure.
	 *
	 * @see Sensei_Course_Structure::get()
	 *
	 * @param array $structure Structure to flatten as returned by get().
	 *
	 * @return array[] {
	 *     @type array $0 $lesson_ids    All the lesson IDs.
	 *     @type array $1 $module_ids    All the module IDs.
	 *     @type array $2 $module_titles All the module titles.
	 * }
	 */
	private function flatten_structure( array $structure ) : array {
		$lesson_ids    = [];
		$module_ids    = [];
		$module_titles = [];

		foreach ( $structure as $item ) {
			if ( ! isset( $item['type'] ) ) {
				continue;
			}

			if ( 'module' === $item['type'] ) {
				if ( ! empty( $item['id'] ) ) {
					$module_ids[] = $item['id'];
				}

				if ( isset( $item['title'] ) ) {
					$module_titles[] = $item['title'];
				}

				if ( ! empty( $item['lessons'] ) ) {
					foreach ( $item['lessons'] as $lesson_item ) {
						if ( ! empty( $lesson_item['id'] ) ) {
							$lesson_ids[] = $lesson_item['id'];
						}
					}
				}
			} elseif ( 'lesson' === $item['type'] && ! empty( $item['id'] ) ) {
				$lesson_ids[] = $item['id'];
			}
		}

		return [
			$lesson_ids,
			$module_ids,
			$module_titles,
		];
	}

	/**
	 * Parse, validate, and sanitize the structure input.
	 *
	 * @see Sensei_Course_Structure::get()
	 *
	 * @param array $raw_structure Structure array as returned by get().
	 *
	 * @return WP_Error|array False if the input is invalid.
	 */
	private function sanitize_structure( array $raw_structure ) {
		list( $lesson_ids, $module_ids, $module_titles ) = $this->flatten_structure( $raw_structure );
		$module_titles                                   = array_filter( $module_titles );

		if (
			array_unique( $module_ids ) !== $module_ids
			|| array_unique( $lesson_ids ) !== $lesson_ids
		) {
			return new WP_Error(
				'sensei_course_structure_duplicate_items',
				__( 'Individual lesson or modules cannot appear multiple times in the same course.', 'sensei-lms' )
			);
		}

		if ( array_unique( $module_titles ) !== $module_titles ) {
			return new WP_Error(
				'sensei_course_structure_duplicate_module_title',
				__( 'Different modules cannot have the same name.', 'sensei-lms' )
			);
		}

		$structure = [];

		foreach ( $raw_structure as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				return new WP_Error(
					'sensei_course_structure_invalid_item',
					__( 'Each item must be an array.', 'sensei-lms' )
				);
			}

			$item = $this->sanitize_item( $raw_item );
			if ( is_wp_error( $item ) ) {
				return $item;
			}

			$structure[] = $item;
		}

		return $structure;
	}

	/**
	 * Validate and sanitize input item of structure.
	 *
	 * @see Sensei_Course_Structure::prepare_lesson()
	 * @see Sensei_Course_Structure::prepare_module()
	 *
	 * @param array $raw_item Module or lesson as returned by prepare_lesson or prepare_module.
	 *
	 * @return array|WP_Error
	 */
	private function sanitize_item( array $raw_item ) {
		$validate = $this->validate_item_structure( $raw_item );
		if ( is_wp_error( $validate ) ) {
			return $validate;
		}

		$item = [
			'type'  => $raw_item['type'],
			'id'    => ! empty( $raw_item['id'] ) ? intval( $raw_item['id'] ) : null,
			'title' => trim( sanitize_text_field( $raw_item['title'] ) ),
		];

		if ( 'module' === $raw_item['type'] ) {
			if ( $item['id'] ) {
				$term = get_term( $item['id'], 'module' );
				if ( ! $term || is_wp_error( $term ) ) {
					return new WP_Error(
						'sensei_course_structure_missing_module',
						// translators: Placeholder is ID for module.
						sprintf( __( 'Module with id "%d" was not found', 'sensei-lms' ), $item['id'] )
					);
				}
			} else {
				// Attempt to find an existing module available to the user.
				$item['id'] = $this->get_existing_module( $item['title'] );
			}

			$item['description'] = isset( $raw_item['description'] ) ? trim( wp_kses_post( $raw_item['description'] ) ) : null;
			$item['lessons']     = [];
			foreach ( $raw_item['lessons'] as $raw_lesson ) {
				$lesson = $this->sanitize_item( $raw_lesson );
				if ( is_wp_error( $lesson ) ) {
					return $lesson;
				}

				if ( 'lesson' !== $lesson['type'] ) {
					return new WP_Error(
						'sensei_course_structure_invalid_module_lesson',
						__( 'Module lessons array can only contain lessons.', 'sensei-lms' )
					);
				}

				$item['lessons'][] = $lesson;
			}
		} elseif ( 'lesson' === $raw_item['type'] ) {
			if ( $item['id'] ) {
				$lesson = get_post( $item['id'] );
				if ( ! $lesson || in_array( $lesson->post_status, [ 'trash', 'auto-draft' ], true ) || 'lesson' !== $lesson->post_type ) {
					return new WP_Error(
						'sensei_course_structure_missing_lesson',
						// translators: Placeholder is ID for lesson.
						sprintf( __( 'Lesson with id "%d" was not found', 'sensei-lms' ), $item['id'] )
					);
				}
			}
		}

		return $item;
	}

	/**
	 * Validate item is build correctly.
	 *
	 * @see Sensei_Course_Structure::prepare_lesson()
	 * @see Sensei_Course_Structure::prepare_module()
	 *
	 * @param array $raw_item Raw item to sanitize.
	 *
	 * @return true|WP_Error
	 */
	private function validate_item_structure( array $raw_item ) {
		if ( ! isset( $raw_item['type'] ) || ! in_array( $raw_item['type'], [ 'module', 'lesson' ], true ) ) {
			return new WP_Error(
				'sensei_course_structure_invalid_item_type',
				__( 'All items must have a `type` set.', 'sensei-lms' )
			);
		}

		if ( ! isset( $raw_item['title'] ) || '' === trim( sanitize_text_field( $raw_item['title'] ) ) ) {
			if ( 'module' === $raw_item['type'] ) {
				return new WP_Error(
					'sensei_course_structure_modules_missing_title',
					__( 'Please ensure all modules have a name before saving.', 'sensei-lms' )
				);
			}

			return new WP_Error(
				'sensei_course_structure_lessons_missing_title',
				__( 'Please ensure all lessons have a name before saving.', 'sensei-lms' )
			);
		}

		if (
			'module' === $raw_item['type']
			&& (
				! isset( $raw_item['lessons'] )
				|| ! is_array( $raw_item['lessons'] )
			)
		) {
			return new WP_Error(
				'sensei_course_structure_missing_lessons',
				__( 'Module items must include a `lessons` array.', 'sensei-lms' )
			);
		}

		return true;
	}

	/**
	 * Sort structure.
	 *
	 * @param array  $structure Structure to be sorted.
	 * @param int[]  $order     Order to sort the lessons.
	 * @param string $type      Type to be sorted (lesson or module).
	 *
	 * @return array Sorted structure.
	 */
	public static function sort_structure( $structure, $order, $type ) {
		if ( ! empty( $order )
		&& [ 0 ] !== $order ) {
			usort(
				$structure,
				function( $a, $b ) use ( $order, $type ) {
					// One of the types is not being sorted.
					if ( $type !== $a['type'] || $type !== $b['type'] ) {
						// If types are equal, keep in the current positions.
						if ( $a['type'] === $b['type'] ) {
							return 0;
						}

						// Always keep the modules before the lessons.
						return 'module' === $a['type'] ? - 1 : 1;
					}

					$a_position = array_search( $a['id'], $order, true );
					$b_position = array_search( $b['id'], $order, true );

					// If both weren't sorted, keep the current positions.
					if ( false === $a_position && false === $b_position ) {
						return 0;
					}

					// Keep not sorted items in the end.
					if ( false === $a_position ) {
						return 1;
					}

					return false === $b_position || $a_position < $b_position ? -1 : 1;
				}
			);
		}
		return $structure;
	}
}
