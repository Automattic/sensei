<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Modules Class
 *
 * Sensei Module Functionality
 *
 * @package Content
 * @author Automattic
 *
 * @since 1.8.0
 */
class Sensei_Core_Modules {

	private $file;
	private $order_page_slug;
	public $taxonomy;

	public function __construct( $file ) {
		$this->file            = $file;
		$this->taxonomy        = 'module';
		$this->order_page_slug = 'module-order';

		// setup taxonomy
		add_action( 'init', array( $this, 'setup_modules_taxonomy' ), 10 );

		// Manage lesson meta boxes for taxonomy
		add_action( 'add_meta_boxes', array( $this, 'modules_metaboxes' ), 20, 2 );

		// Save lesson meta box
		add_action( 'save_post', array( $this, 'save_lesson_module' ), 10, 1 );

		// Frontend styling
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		// Admin styling
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20, 2 );

		// Handle module completion record
		add_action( 'sensei_lesson_status_updated', array( $this, 'update_lesson_status_module_progress' ), 10, 3 );
		add_action( 'sensei_user_lesson_reset', array( $this, 'save_lesson_module_progress' ), 10, 2 );
		add_action( 'wp', array( $this, 'save_module_progress' ), 10 );

		add_action( 'admin_menu', array( $this, 'add_submenus' ) );
		add_action( 'admin_post_order_modules', array( $this, 'handle_order_modules' ) );
		add_filter( 'manage_course_posts_columns', array( $this, 'course_columns' ), 11, 1 );
		add_action( 'manage_course_posts_custom_column', array( $this, 'course_column_content' ), 11, 2 );
		add_filter( 'manage_lesson_posts_columns', array( $this, 'add_lesson_columns' ), 11, 1 );
		add_action( 'manage_lesson_posts_custom_column', array( $this, 'add_lesson_column_content' ), 11, 2 );

		// Ensure modules always show under courses
		add_action( 'admin_menu', array( $this, 'remove_lessons_menu_model_taxonomy' ), 10 );
		add_action( 'admin_menu', array( $this, 'remove_courses_menu_model_taxonomy' ), 10 );
		add_action( 'admin_menu', array( $this, 'redirect_to_lesson_module_taxonomy_to_course' ), 20 );

		// Add course field to taxonomy
		add_action( $this->taxonomy . '_add_form_fields', array( $this, 'add_module_fields' ), 50, 1 );
		add_action( $this->taxonomy . '_edit_form_fields', array( $this, 'edit_module_fields' ), 1, 1 );
		add_action( 'created_' . $this->taxonomy, array( $this, 'track_module_creation' ), 10 );
		add_action( 'admin_init', array( $this, 'add_module_admin_hooks' ) );
		add_action( 'wp_ajax_sensei_json_search_courses', array( $this, 'search_courses_json' ) );

		// Manage module taxonomy archive page
		add_filter( 'template_include', array( $this, 'module_archive_template' ), 10 );
		add_action( 'pre_get_posts', array( $this, 'module_archive_filter' ), 10, 1 );
		add_filter( 'sensei_lessons_archive_text', array( $this, 'module_archive_title' ) );
		add_action( 'sensei_loop_lesson_inside_before', array( $this, 'module_archive_description' ), 30 );
		add_action( 'sensei_taxonomy_module_content_inside_before', array( $this, 'course_signup_link' ), 30 );
		add_action( 'sensei_taxonomy_module_content_inside_before', array( $this, 'module_archive_description' ), 30 );

		add_filter( 'body_class', array( $this, 'module_archive_body_class' ) );

		// Single Course modules actions. Add to single-course/course-modules.php
		add_action( 'sensei_single_course_modules_before', array( $this, 'course_modules_title' ), 20 );

		// Set up display on single lesson page
		add_filter( 'sensei_breadcrumb_output', array( $this, 'module_breadcrumb_link' ), 10, 2 );

		// Add 'Modules' columns to Analysis tables
		add_filter( 'sensei_analysis_overview_columns', array( $this, 'analysis_overview_column_title' ), 10, 2 );
		add_filter( 'sensei_analysis_course_columns', array( $this, 'analysis_course_column_title' ), 10, 2 );
		add_filter( 'sensei_analysis_course_column_data', array( $this, 'analysis_course_column_data' ), 10, 3 );

		// Manage module taxonomy columns
		add_filter( 'manage_edit-' . $this->taxonomy . '_columns', array( $this, 'taxonomy_column_headings' ), 1, 1 );
		add_filter( 'manage_' . $this->taxonomy . '_custom_column', array( $this, 'taxonomy_column_content' ), 1, 3 );
		add_filter( 'sensei_module_lesson_list_title', array( $this, 'sensei_course_preview_titles' ), 10, 2 );

		// store new modules created on the course edit screen
		add_action( 'wp_ajax_sensei_add_new_module_term', array( 'Sensei_Core_Modules', 'add_new_module_term' ) );
		add_action( 'wp_ajax_sensei_get_course_modules', array( $this, 'ajax_get_course_modules' ) );
		add_action( 'wp_ajax_sensei_get_lesson_module_metabox', array( $this, 'handle_get_lesson_module_metabox' ) );

		// for non admin users, only show taxonomies that belong to them
		add_filter( 'get_terms', array( $this, 'filter_module_terms' ), 20, 3 );
		// add the teacher name next to the module term in for admin users
		add_filter( 'get_terms', array( $this, 'append_teacher_name_to_module' ), 70, 3 );
		add_filter( 'get_object_terms', array( $this, 'filter_course_selected_terms' ), 20, 3 );

		// remove the default modules  metabox
		add_action( 'admin_init', array( 'Sensei_Core_Modules', 'remove_default_modules_box' ) );

		// Add custom navigation.
		add_action( 'in_admin_header', [ $this, 'add_custom_navigation' ] );

		// Update module teacher meta when added to course.
		add_action( 'added_term_relationship', [ $this, 'add_teacher_id_in_module_meta_when_added_to_course' ], 10, 3 );

		// Remove module teacher meta when removed from course.
		add_action( 'delete_term_relationships', [ $this, 'remove_teacher_id_from_module_meta_when_removed_from_course' ], 10, 3 );

		// Update module teacher meta on course teacher update.
		add_action( 'post_updated', [ $this, 'update_module_teacher_id_meta_on_post_teacher_update' ], 10, 3 );
	}

	/**
	 * Add teacher id as term meta when a module is added to a course.
	 *
	 * @since 4.9.0
	 * @access private
	 *
	 * @param int     $post_ID      Post ID.
	 * @param WP_Post $post_after   Post object following the update.
	 * @param WP_Post $post_before  Post object before the update.
	 */
	public function update_module_teacher_id_meta_on_post_teacher_update( int $post_ID, WP_Post $post_after, WP_Post $post_before ) {
		if ( 'course' !== get_post( $post_ID )->post_type ) {
			return;
		}

		if ( $post_after->post_author !== $post_before->post_author ) {
			$modules = Sensei()->modules->get_course_modules( $post_ID );
			foreach ( $modules as $module ) {
				self::update_module_teacher_meta( $module->term_id, $post_after->post_author );
			}
		}
	}

	/**
	 * Add teacher id as term meta when a module is added to a course.
	 *
	 * @since 4.9.0
	 * @access private
	 *
	 * @param int    $object_id Object ID.
	 * @param int    $tt_id     Term taxonomy ID.
	 * @param string $taxonomy  Taxonomy slug.
	 */
	public function add_teacher_id_in_module_meta_when_added_to_course( int $object_id, int $tt_id, string $taxonomy ) {
		if ( 'module' !== $taxonomy ) {
			return;
		}

		$course = get_post( $object_id );

		self::update_module_teacher_meta( $tt_id, $course->post_author );
	}

	/**
	 * Remove teacher id from term meta when a module is added to a course.
	 *
	 * @since 4.9.0
	 * @access private
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $tt_ids    An array of term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy slug.
	 */
	public function remove_teacher_id_from_module_meta_when_removed_from_course( int $object_id, array $tt_ids, string $taxonomy ) {
		if ( 'module' !== $taxonomy ) {
			return;
		}

		foreach ( $tt_ids as $tt_id ) {
			$args    = array(
				'post_type'      => 'course',
				'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
				'posts_per_page' => -1,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $this->taxonomy,
						'field'    => 'id',
						'terms'    => $tt_id,
					),
				),
			);
			$courses = get_posts( $args );
			// Don't remove teacher id if this module is still being used in other courses.
			if ( count( $courses ) < 2 ) {
				delete_term_meta( $tt_id, 'module_author' );
			}
		}
	}

	/**
	 * Highlight the menu item for the modules pages.
	 *
	 * @deprecated 4.8.0
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @param string $submenu_file The submenu file points to the certain item of the submenu.
	 *
	 * @return string
	 */
	public function highlight_menu_item( $submenu_file ) {
		_deprecated_function( __METHOD__, '4.8.0' );

		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, [ 'edit-module', 'course_page_module-order' ], true ) ) {
			$submenu_file = 'edit-tags.php?taxonomy=module&post_type=course';
		}

		return $submenu_file;
	}

	/**
	 * Add custom navigation to the admin pages.
	 *
	 * @since 4.0.0
	 * @access private
	 */
	public function add_custom_navigation() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		if ( ( 'edit-module' === $screen->id ) && ( 'term' !== $screen->base ) ) {
			$this->display_modules_navigation( $screen );
		}
	}

	/**
	 * Display the modules' navigation.
	 *
	 * @param WP_Screen $screen WordPress current screen object.
	 */
	private function display_modules_navigation( WP_Screen $screen ) {
		?>
		<div id="sensei-custom-navigation" class="sensei-custom-navigation">
			<div class="sensei-custom-navigation__heading">
				<div class="sensei-custom-navigation__title">
					<h1><?php esc_html_e( 'Modules', 'sensei-lms' ); ?></h1>
				</div>
				<div class="sensei-custom-navigation__links">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=module-order' ) ); ?>"><?php esc_html_e( 'Order Modules', 'sensei-lms' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Hook in all meta boxes related tot he modules taxonomy
	 *
	 * @since 1.8.0
	 *
	 * @param string  $post_type
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function modules_metaboxes( $post_type, $post ) {
		if ( 'lesson' == $post_type ) {

			// Remove default taxonomy meta box from Lesson edit screen
			remove_meta_box( $this->taxonomy . 'div', 'lesson', 'side' );

			// Add custom meta box to limit module selection to one per lesson
			add_meta_box( $this->taxonomy . '_select', __( 'Module', 'sensei-lms' ), array( $this, 'lesson_module_metabox' ), 'lesson', 'side', 'default' );

		}

		if ( 'course' == $post_type ) {
			// Course modules selection metabox
			add_meta_box( $this->taxonomy . '_course_mb', __( 'Course Modules', 'sensei-lms' ), array( $this, 'course_module_metabox' ), 'course', 'side', 'core' );
		}
	}

	/**
	 * Build content for custom module meta box
	 *
	 * @since 1.8.0
	 * @param  WP_Post $post Current post object.
	 * @return void
	 */
	public function lesson_module_metabox( $post ) {
		$course_id = (int) get_post_meta( $post->ID, '_lesson_course', true );

		$this->output_lesson_module_metabox( $post, $course_id );
	}

	/**
	 * Outputs the lesson module meta box HTML.
	 *
	 * @since 3.15.0
	 *
	 * @param WP_Post $lesson_post The lesson post object.
	 * @param int     $course_id   The course id.
	 */
	private function output_lesson_module_metabox( WP_Post $lesson_post, int $course_id ) {
		// Get current lesson module.
		$module_id = $course_id ? $this->get_lesson_module_if_exists( $lesson_post ) : null;

		$html  = '<div id="lesson-module-metabox-select">';
		$html .= $this->render_lesson_module_select_for_course( $course_id, $module_id );
		$html .= '</div>';

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input'  => array(
						'id'    => array(),
						'name'  => array(),
						'type'  => array(),
						'value' => array(),
					),
					'option' => array(
						'selected' => array(),
						'value'    => array(),
					),
					'select' => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
						'style' => array(),
					),
				)
			)
		);
	}

	/**
	 * Get the lesson module if it Exists. Defaults to 0 if none found.
	 *
	 * @param WP_Post $post The post.
	 * @return int
	 */
	public function get_lesson_module_if_exists( $post ) {
		// Get existing lesson module.
		$lesson_module      = 0;
		$lesson_module_list = wp_get_post_terms( $post->ID, $this->taxonomy );
		if ( is_array( $lesson_module_list ) && count( $lesson_module_list ) > 0 ) {
			foreach ( $lesson_module_list as $single_module ) {
				$lesson_module = $single_module->term_id;
				break;
			}
		}
		return $lesson_module;
	}

	/**
	 * Renders the lesson module select input.
	 *
	 * @since 3.15.0
	 *
	 * @param int|null $course_id         The course post ID.
	 * @param int|null $current_module_id The currently selected module post ID.
	 *
	 * @return string The lesson module select HTML.
	 */
	private function render_lesson_module_select_for_course( int $course_id = null, int $current_module_id = null ): string {
		// Get the available modules for this lesson's course.
		$modules = $course_id ? $this->get_course_modules( $course_id ) : [];

		// Build the HTML.
		$input_name = 'lesson_module';

		$html  = '';
		$html .= '<input type="hidden" name="' . esc_attr( 'woo_lesson_' . $this->taxonomy . '_nonce' ) . '" id="' . esc_attr( 'woo_lesson_' . $this->taxonomy . '_nonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename( $this->file ) ) ) . '" />';

		if ( $modules ) {
			$html .= '<select id="lesson-module-options" name="' . esc_attr( $input_name ) . '" class="widefat" style="width: 100%">' . "\n";
			$html .= '<option value="">' . esc_html__( 'None', 'sensei-lms' ) . '</option>';
			foreach ( $modules as $module ) {
				$html .= '<option value="' . esc_attr( absint( $module->term_id ) ) . '"' . selected( $module->term_id, $current_module_id, false ) . '>' . esc_html( $module->name ) . '</option>' . "\n";
			}
			$html .= '</select>' . "\n";
		} else {
			$html .= '<input type="hidden" name="' . esc_attr( $input_name ) . '" value="">';

			if ( $course_id ) {
				$course_url = admin_url( 'post.php?post=' . $course_id . '&action=edit' );

				/*
				 * translators: The placeholders are as follows:
				 *
				 * %1$s - <em>
				 * %2$s - </em>
				 * %3$s - Opening <a> tag to link to the Course URL.
				 * %4$s - </a>
				 */
				$html .= '<p>' . wp_kses_post( sprintf( __( 'No modules are available for this lesson yet. %1$sPlease add some to %3$sthe course%4$s.%2$s', 'sensei-lms' ), '<em>', '</em>', '<a href="' . esc_url( $course_url ) . '">', '</a>' ) ) . '</p>';
			} else {
				/*
				 * translators: The placeholders are as follows:
				 *
				 * %1$s - <em>
				 * %2$s - </em>
				 */
				$html .= '<p>' . sprintf( __( 'No modules are available for this lesson yet. %1$sPlease select a course first.%2$s', 'sensei-lms' ), '<em>', '</em>' ) . '</p>';
			}
		}

		return $html;
	}

	/**
	 * Delete a term if it is childless and not associated with a lesson or course.
	 *
	 * @param int $module_term_id Term ID for the module.
	 */
	public function remove_if_unused( $module_term_id ) {
		if ( ! $this->is_term_used( $module_term_id ) ) {
			wp_delete_term( $module_term_id, 'module' );
		}
	}

	/**
	 * Check if term either has children or is associated with a lesson or course.
	 *
	 * @param int $module_term_id Term ID for the module.
	 * @return bool True if term is has children or is associated with a lesson or course.
	 */
	public function is_term_used( $module_term_id ) {
		$term_children = get_term_children( $module_term_id, 'module' );

		if ( ! is_wp_error( $term_children ) && ! empty( $term_children ) ) {
			return true;
		}

		$post_query = new WP_Query(
			array(
				'post_type'      => array( 'lesson', 'course' ),
				'tax_query'      => array(
					array(
						'taxonomy' => 'module',
						'field'    => 'id',
						'terms'    => intval( $module_term_id ),
					),
				),
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);

		if ( $post_query->found_posts > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Save module to lesson. This method checks for authorization, and checks
	 * the incoming nonce.
	 *
	 * @since 1.8.0
	 * @param  integer $post_id ID of post
	 * @return mixed            Post ID on permissions failure, boolean true on success
	 */
	public function save_lesson_module( $post_id ) {
		$post = get_post( $post_id );

		// Verify post type and nonce
		if ( ( get_post_type( $post ) != 'lesson' ) || ! isset( $_POST[ 'woo_lesson_' . $this->taxonomy . '_nonce' ] )
			|| ! wp_verify_nonce( $_POST[ 'woo_lesson_' . $this->taxonomy . '_nonce' ], plugin_basename( $this->file ) ) ) {
			return $post_id;
		}

		// Check if user has permissions to edit lessons
		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		// Check if user has permissions to edit this specific post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Get module and course IDs
		$lesson_module_id_key = 'lesson_module';
		$lesson_course_id_key = 'lesson_course';
		$module_id            = isset( $_POST[ $lesson_module_id_key ] ) ? $_POST[ $lesson_module_id_key ] : null;
		$course_id            = isset( $_POST[ $lesson_course_id_key ] ) ? $_POST[ $lesson_course_id_key ] : null;

		// Set the module on the lesson
		$lesson_modules = new Sensei_Core_Lesson_Modules( $post_id );
		$lesson_modules->set_module( $module_id, $course_id );

		return true;
	}

	/**
	 * Display course field on new module screen
	 *
	 * @since 1.8.0
	 * @param object $taxonomy Taxonomy object
	 * @return void
	 */
	public function add_module_fields( $taxonomy ) {
		?>
		<div class="form-field">
			<?php $this->render_module_course_multi_select(); ?>
		</div>
		<input type="hidden" name="from_page" value="module">
		<?php
	}

	/**
	 * Render the Course Multi-Select (used by select2)
	 *
	 * @param array $module_courses The Module courses.
	 * @since 1.9.15
	 * @return void
	 */
	private function render_module_course_multi_select( $module_courses = array() ) {
		?>
		<label for="module_courses"><?php echo esc_html__( 'Course(s)', 'sensei-lms' ); ?></label>
		<select name="module_courses[]"
				id="module_courses"
				class="ajax_chosen_select_courses"
				multiple="multiple"
				data-placeholder="<?php echo esc_attr__( 'Search for courses...', 'sensei-lms' ); ?>"
		>
			<?php foreach ( $module_courses as $module_course ) { ?>
				<option value="<?php echo esc_attr( $module_course['id'] ); ?>" selected="selected">
					<?php echo esc_html( $module_course['details'] ); ?>
				</option>
			<?php } ?>
		</select>
		<span
			class="description"><?php echo esc_html__( 'Search for and select the courses that this module will belong to.', 'sensei-lms' ); ?>
		</span>
		<?php
	}

	/**
	 * Adds hooks for use with editing a taxonomy in WP Admin.
	 *
	 * @since 3.6.0
	 * @access private
	 */
	public function add_module_admin_hooks() {
		add_action( 'edited_' . $this->taxonomy, array( $this, 'save_module_course' ), 10, 2 );
		add_action( 'created_' . $this->taxonomy, array( $this, 'save_module_course' ), 10, 2 );
	}

	/**
	 * Display course field on module edit screen
	 *
	 * @since 1.8.0
	 * @param  object $module Module term object
	 * @return void
	 */
	public function edit_module_fields( $module ) {

		$module_id = $module->term_id;

		// Get module's existing courses
		$args    = array(
			'post_type'      => 'course',
			'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => $this->taxonomy,
					'field'    => 'id',
					'terms'    => $module_id,
				),
			),
		);
		$courses = get_posts( $args );

		// build the defaults array
		$module_courses = array();
		if ( isset( $courses ) && is_array( $courses ) ) {
			foreach ( $courses as $course ) {
				$module_courses[] = array(
					'id'      => $course->ID,
					'details' => $course->post_title,
				);
			}
		}

		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label
					for="module_courses"><?php esc_html_e( 'Course(s)', 'sensei-lms' ); ?></label></th>
			<td>
				<?php $this->render_module_course_multi_select( $module_courses ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save module course on add/edit
	 *
	 * @since 1.8.0
	 * @param  int $module_id ID of module.
	 * @return void
	 */
	public function save_module_course( $module_id ) {
		/*
		 * It is safe to ignore nonce verification here because this is called
		 * from `edited_{$taxonomy}` and `created_{$taxonomy}` on a post to
		 * `edit-tags.php`, which occur after WordPress performs its own nonce
		 * verification.
		 */

		$is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( $is_rest_request || ( isset( $_POST['action'] ) && 'inline-save-tax' == $_POST['action'] ) ) {
			return;
		}

		// Get module's existing courses
		$args    = array(
			'post_type'      => 'course',
			'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => $this->taxonomy,
					'field'    => 'id',
					'terms'    => $module_id,
				),
			),
		);
		$courses = get_posts( $args );

		// Remove module from existing courses
		if ( isset( $courses ) && is_array( $courses ) ) {
			foreach ( $courses as $course ) {
				wp_remove_object_terms( $course->ID, (int) $module_id, $this->taxonomy );
			}
		}

		// Add module to selected courses
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_POST['module_courses'] ) && ! empty( $_POST['module_courses'] ) ) {

			// phpcs:ignore WordPress.Security.NonceVerification
			$course_ids = is_array( $_POST['module_courses'] ) ? $_POST['module_courses'] : explode( ',', $_POST['module_courses'] );

			foreach ( $course_ids as $course_id ) {

				wp_set_object_terms( absint( $course_id ), $module_id, $this->taxonomy, true );

			}
		}
	}

	/**
	 * Track module creation.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @param int $module_id ID of module.
	 */
	public function track_module_creation( $module_id ) {
		$module           = get_term( $module_id );
		$event_properties = [
			// phpcs:ignore WordPress.Security.NonceVerification
			'page'      => isset( $_REQUEST['from_page'] ) ? $_REQUEST['from_page'] : '',
			'parent_id' => -1,
		];

		if ( $module->parent ) {
			$event_properties['parent_id'] = $module->parent;
		}

		sensei_log_event( 'module_add', $event_properties );
	}

	/**
	 * Ajax function to search for courses matching term
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function search_courses_json() {
		// Security check
		check_ajax_referer( 'search-courses', 'security' );

		// Set content type
		header( 'Content-Type: application/json; charset=utf-8' );

		// Get user input
		$term = urldecode( stripslashes( $_GET['term'] ) );

		// Return nothing if term is empty
		if ( empty( $term ) ) {
			die();
		}

		// Set a default if none is given
		$default = isset( $_GET['default'] ) ? $_GET['default'] : __( 'No course', 'sensei-lms' );

		// Set up array of results
		$found_courses = array( '' => $default );

		// Fetch results
		$args    = array(
			'post_type'      => 'course',
			'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			's'              => $term,
		);
		$courses = get_posts( $args );

		// Add results to array
		if ( $courses ) {
			foreach ( $courses as $course ) {
				$found_courses[ $course->ID ] = $course->post_title;
			}
		}

		// Encode and return results for processing & selection
		echo json_encode( $found_courses );
		die();
	}

	public function sensei_course_preview_titles( $title, $lesson_id ) {
		global $post, $current_user;

		$course_id  = $post->ID;
		$title_text = '';

		if ( method_exists( 'Sensei_Utils', 'is_preview_lesson' ) && Sensei_Utils::is_preview_lesson( $lesson_id ) ) {
			$is_user_taking_course = Sensei_Utils::sensei_check_for_activity(
				array(
					'post_id' => $course_id,
					'user_id' => $current_user->ID,
					'type'    => 'sensei_course_status',
				)
			);
			if ( ! $is_user_taking_course ) {
				if ( method_exists( 'Sensei_Frontend', 'sensei_lesson_preview_title_text' ) ) {
					$title_text = Sensei()->frontend->sensei_lesson_preview_title_text( $course_id );
					// Remove brackets for display here
					$title_text = str_replace( '(', '', $title_text );
					$title_text = str_replace( ')', '', $title_text );
					$title_text = '<span class="preview-label">' . $title_text . '</span>';
				}
				$title .= ' ' . $title_text;
			}
		}

		return $title;
	}

	public function module_breadcrumb_link( $html, $separator ) {
		global $post;
		// Lesson
		if ( is_singular( 'lesson' ) ) {
			if ( has_term( '', $this->taxonomy, $post->ID ) ) {
				$module = $this->get_lesson_module( $post->ID );
				if ( $module ) {
					if ( $this->do_link_to_module( $module ) ) {
						$html .= ' ' . $separator . ' <a href="' . esc_url( $module->url ) . '" title="' . __( 'Back to the module', 'sensei-lms' ) . '">' . $module->name . '</a>';
					} else {
						$html .= ' ' . $separator . ' ' . $module->name;
					}
				}
			}
		}
		// Module
		if ( is_tax( $this->taxonomy ) ) {
			if ( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
				$course_id = intval( $_GET['course_id'] );
				$html     .= '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . __( 'Back to the course', 'sensei-lms' ) . '">' . get_the_title( $course_id ) . '</a>';
			}
		}
		return $html;
	}

	/**
	 * Check if we should link to a module in the course outline.
	 *
	 * True if there is a module description or if the `taxonomy-module.php` template has been overridden.
	 *
	 * @since 1.10.0
	 *
	 * @param WP_Term $module
	 * @param bool    $link_to_current Set to true to disable checks for currently displayed module. Default false.
	 * @return bool
	 */
	public function do_link_to_module( WP_Term $module, $link_to_current = false ) {
		// Perhaps don't link to module when on the module page already.
		if ( ! $link_to_current && is_tax( 'module', $module->term_id ) ) {
			$do_link_to_module = false;
		} else {
			$description = trim( $module->description );
			if ( ! empty( $description ) ) {
				$do_link_to_module = true;
			} else {
				$do_link_to_module = $this->is_module_tax_template_overridden();
			}
		}

		/**
		 * Determine if a particular module should be linked to.
		 *
		 * @since 1.10.0
		 *
		 * @param bool    $do_link_to_module  True if module should be linked to.
		 * @param WP_Term $module             Module to check if it should be linked to.
		 * @param bool    $link_to_current    Allow for linking to the currently displayed module.
		 */
		return apply_filters( 'sensei_do_link_to_module', $do_link_to_module, $module, $link_to_current );
	}

	/**
	 * Checks if a module taxonomy template file has been overridden.
	 *
	 * @since 1.10.0
	 *
	 * @return bool True if taxonomy template has been overridden.
	 */
	protected function is_module_tax_template_overridden() {
		$file     = 'taxonomy-module.php';
		$find     = array( $file, Sensei()->template_url . $file );
		$template = locate_template( $find );

		return (bool) $template;
	}

	/**
	 * Set lesson archive template to display on module taxonomy archive page
	 *
	 * @since 1.8.0
	 * @param  string $template Default template
	 * @return string           Modified template
	 */
	public function module_archive_template( $template ) {

		if ( ! is_tax( $this->taxonomy ) ) {
			return $template;
		}

		$file = 'taxonomy-module.php';
		$find = array( $file, Sensei()->template_url . $file );

		// locate the template file
		$template = locate_template( $find );
		if ( ! $template ) {

			$template = Sensei()->plugin_path() . 'templates/' . $file;

		}

		return $template;
	}

	/**
	 * Modify module taxonomy archive query
	 *
	 * @since 1.8.0
	 * @param  object $query The query object passed by reference
	 * @return void
	 */
	public function module_archive_filter( $query ) {
		// If there is already an "orderby" property set
		// then no need to do anything.
		if ( $query->get( 'orderby' ) ) {
			return;
		}

		if ( $query->is_main_query() && is_tax( $this->taxonomy ) ) {

			// Limit to lessons only
			$query->set( 'post_type', 'lesson' );

			// Set order of lessons
			if ( version_compare( Sensei()->version, '1.6.0', '>=' ) ) {
				$module_id = $query->queried_object_id;
				$query->set( 'meta_key', '_order_module_' . $module_id );
				$query->set( 'orderby', 'meta_value_num date' );
			} else {
				$query->set( 'orderby', 'menu_order' );
			}
			$query->set( 'order', 'ASC' );

			// Limit to specific course if specified
			if ( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
				$course_id    = intval( $_GET['course_id'] );
				$meta_query   = [];
				$meta_query[] = array(
					'key'   => '_lesson_course',
					'value' => intval( $course_id ),
				);
				$query->set( 'meta_query', $meta_query );
			}
		}
	}

	/**
	 * Modify archive page title
	 *
	 * @since 1.8.0
	 * @param  string $title Default title
	 * @return string        Modified title
	 */
	public function module_archive_title( $title ) {
		if ( is_tax( $this->taxonomy ) ) {
			$title = apply_filters( 'sensei_module_archive_title', get_queried_object()->name );
		}
		return $title;
	}

	/**
	 * Display module description on taxonomy archive page
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function module_archive_description() {
		// ensure this only shows once on the archive.
		remove_action( 'sensei_loop_lesson_before', array( $this, 'module_archive_description' ), 30 );

		if ( is_tax( $this->taxonomy ) ) {

			$module = get_queried_object();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe handling of course ID query var.
			$course_id = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : null;
			$user_id   = get_current_user_id();

			$module_progress = false;
			if ( $user_id && ! empty( $course_id ) ) {
				$module_progress = $this->get_user_module_progress( $module->term_id, $course_id, $user_id );
			}

			if ( $module_progress && $module_progress > 0 ) {
				$status = __( 'Completed', 'sensei-lms' );
				$class  = 'completed';
				if ( $module_progress < 100 ) {
					$status = __( 'In progress', 'sensei-lms' );
					$class  = 'in-progress';
				}
				echo '<p class="status ' . esc_attr( $class ) . '">' . esc_html( $status ) . '</p>';
			}

			if ( $this->can_view_module_content( $module, $course_id, $user_id ) ) {
				echo '<p class="archive-description module-description">' . wp_kses_post( apply_filters( 'sensei_module_archive_description', nl2br( $module->description ), $module->term_id ) ) . '</p>';
			}
		}
	}

	/**
	 * Check if we can view module content.
	 *
	 * @param WP_Term $module    Module term object. Defaults to the currently queried term.
	 * @param int     $course_id Course post ID. May not be set if not viewing module in course context.
	 * @param int     $user_id   User ID. Defaults to currently logged in user ID.
	 *
	 * @return bool
	 */
	public function can_view_module_content( WP_Term $module = null, $course_id = null, $user_id = null ) {
		$can_view_module_content = false;

		if ( null === $module ) {
			$module = get_queried_object();
		}

		if ( ! $module instanceof WP_Term ) {
			return false;
		}

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if (
			! sensei_is_login_required()
			|| ( $course_id && Sensei()->course->can_access_course_content( $course_id, $user_id, 'module' ) )
		) {
			$can_view_module_content = true;
		}

		/**
		 * Filter if the user can view module content.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $can_view_module_content True if they can view module content.
		 * @param int  $module_term_id          Module term ID.
		 * @param int  $course_id               Course post ID.
		 * @param int  $user_id                 User ID.
		 */
		return apply_filters( 'sensei_can_user_view_module', $can_view_module_content, $module->term_id, $course_id, $user_id );
	}

	/**
	 * Outputs the module course sign-up link.
	 *
	 * @access private
	 * @since 3.0.0
	 */
	public function course_signup_link() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe use of retrieving course ID.
		$course_id = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : null;
		if ( empty( $course_id ) || 'course' !== get_post_type( $course_id ) ) {
			return;
		}

		$show_course_signup_notice = ! $this->can_view_module_content( null, $course_id );

		/**
		 * Filter for if we should show the course sign up notice on the module page.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $show_course_signup_notice True if we should show the signup notice to the user.
		 * @param int  $course_id                 Post ID for the course.
		 */
		if ( ! apply_filters( 'sensei_module_show_course_signup_notice', $show_course_signup_notice, $course_id ) ) {
			return;
		}

		$course_link  = '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr__( 'Sign Up', 'sensei-lms' ) . '">';
		$course_link .= esc_html__( 'course', 'sensei-lms' );
		$course_link .= '</a>';

		// translators: Placeholder is a link to the Course.
		$message_default = sprintf( esc_html__( 'Please sign up for the %1$s before starting the module.', 'sensei-lms' ), $course_link );

		/**
		 * Filter the course sign up notice message on the module page.
		 *
		 * @since 3.0.0
		 *
		 * @param string $message     Message to show user.
		 * @param int    $course_id   Post ID for the course.
		 * @param string $course_link Generated HTML link to the course.
		 */
		$message = apply_filters( 'sensei_module_course_signup_notice_message', $message_default, $course_id, $course_link );

		/**
		 * Filter the course sign up notice message alert level on the module page.
		 *
		 * @since 3.0.0
		 *
		 * @param string $notice_level Notice level to use for the shown alert (alert, tick, download, info).
		 * @param int    $course_id    Post ID for the course.
		 */
		$notice_level = apply_filters( 'sensei_module_course_signup_notice_level', 'info', $course_id );
		Sensei()->notices->add_notice( $message, $notice_level );
	}

	public function module_archive_body_class( $classes ) {
		if ( is_tax( $this->taxonomy ) ) {
			$classes[] = 'module-archive';
		}
		return $classes;
	}

	/**
	 * Trigger save_lesson_module_progress() when a lesson status is updated for a specific user
	 *
	 * @since 1.8.0
	 * @param  string  $status Status of the lesson for the user
	 * @param  integer $user_id ID of user
	 * @param  integer $lesson_id ID of lesson
	 * @return void
	 */
	public function update_lesson_status_module_progress( $status = '', $user_id = 0, $lesson_id = 0 ) {
		$this->save_lesson_module_progress( $user_id, $lesson_id );
	}

	/**
	 * Save lesson's module progress for a specific user
	 *
	 * @since 1.8.0
	 * @param  integer $user_id ID of user
	 * @param  integer $lesson_id ID of lesson
	 * @return void
	 */
	public function save_lesson_module_progress( $user_id = 0, $lesson_id = 0 ) {
		$module    = $this->get_lesson_module( $lesson_id );
		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		if ( $module && $course_id ) {
			$this->save_user_module_progress( intval( $module->term_id ), intval( $course_id ), intval( $user_id ) );
		}
	}

	/**
	 * Save progress of module for user
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function save_module_progress() {
		if ( is_tax( $this->taxonomy ) && is_user_logged_in() && isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			global $current_user;
			wp_get_current_user();
			$user_id = $current_user->ID;

			$module = get_queried_object();

			$this->save_user_module_progress( intval( $module->term_id ), intval( $_GET['course_id'] ), intval( $user_id ) );
		}
	}

	/**
	 * Save module progess for user
	 *
	 * @since 1.8.0
	 *
	 * @param  integer $module_id ID of module
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id ID of user
	 * @return void
	 */
	public function save_user_module_progress( $module_id = 0, $course_id = 0, $user_id = 0 ) {
		$module_progress = $this->calculate_user_module_progress( $user_id, $module_id, $course_id );
		update_user_meta( intval( $user_id ), '_module_progress_' . intval( $course_id ) . '_' . intval( $module_id ), intval( $module_progress ) );

		do_action( 'sensei_module_save_user_progress', $course_id, $module_id, $user_id, $module_progress );
	}

	/**
	 * Get module progress for a user
	 *
	 * @since 1.8.0
	 *
	 * @param  integer $module_id ID of module
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id ID of user
	 * @return mixed              Module progress percentage on success, false on failure
	 */
	public function get_user_module_progress( $module_id = 0, $course_id = 0, $user_id = 0 ) {
		$this->save_user_module_progress( $module_id, $course_id, $user_id );
		$module_progress = get_user_meta( intval( $user_id ), '_module_progress_' . intval( $course_id ) . '_' . intval( $module_id ), true );
		if ( $module_progress ) {
			return (float) $module_progress;
		}
		return false;
	}

	/**
	 * Calculate module progess for user
	 *
	 * @since 1.8.0
	 *
	 * @param  integer $user_id ID of user
	 * @param  integer $module_id ID of module
	 * @param  integer $course_id ID of course
	 * @return integer            Module progress percentage
	 */
	public function calculate_user_module_progress( $user_id = 0, $module_id = 0, $course_id = 0 ) {

		$args    = array(
			'post_type'      => 'lesson',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => $this->taxonomy,
					'field'    => 'id',
					'terms'    => $module_id,
				),
			),
			'meta_query'     => array(
				array(
					'key'   => '_lesson_course',
					'value' => $course_id,
				),
			),
			'fields'         => 'ids',
		);
		$lessons = get_posts( $args );

		if ( is_wp_error( $lessons ) || 0 >= count( $lessons ) ) {
			return 0;
		}

		$completed       = false;
		$lesson_count    = 0;
		$completed_count = 0;
		foreach ( $lessons as $lesson_id ) {
			$completed = Sensei_Utils::user_completed_lesson( $lesson_id, $user_id );
			++$lesson_count;
			if ( $completed ) {
				++$completed_count;
			}
		}
		$module_progress = ( $completed_count / $lesson_count ) * 100;

		return (float) $module_progress;
	}

	/**
	 * Register admin screen for ordering modules
	 *
	 * @since 1.8.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	public function register_modules_admin_menu_items() {
		_deprecated_function( __METHOD__, '4.0.0' );

		// add the modules link under the Course main menu
		add_submenu_page( 'edit.php?post_type=course', __( 'Modules', 'sensei-lms' ), __( 'Modules', 'sensei-lms' ), 'manage_categories', 'edit-tags.php?taxonomy=module&post_type=course', '' );

		// Register new admin page for module ordering.
		add_submenu_page( 'edit.php?post_type=course', __( 'Order Modules', 'sensei-lms' ), __( 'Order Modules', 'sensei-lms' ), 'edit_lessons', $this->order_page_slug, array( $this, 'module_order_screen' ) );
	}

	/**
	 * Add admin screens.
	 *
	 * @since 4.0.0
	 */
	public function add_submenus() {
		add_submenu_page(
			null, // Hide the submenu.
			__( 'Order Modules', 'sensei-lms' ),
			__( 'Order Modules', 'sensei-lms' ),
			'edit_lessons',
			$this->order_page_slug,
			array( $this, 'module_order_screen' )
		);
	}

	/**
	 * Handle the POST request for reordering the Modules.
	 *
	 * @since 1.12.2
	 */
	public function handle_order_modules() {
		check_admin_referer( 'order_modules' );

		$ordered = false;
		if ( isset( $_POST['module-order'] ) && 0 < strlen( $_POST['module-order'] ) ) {
			$ordered = $this->save_course_module_order( esc_attr( $_POST['module-order'] ), esc_attr( $_POST['course_id'] ) );
		}

		wp_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'page'      => $this->order_page_slug,
						'ordered'   => $ordered,
						'course_id' => $_POST['course_id'],
					),
					admin_url( 'admin.php' )
				)
			)
		);
	}

	/**
	 * Display Module Order screen
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public function module_order_screen() {
		?>
		<div id="<?php echo esc_attr( $this->order_page_slug ); ?>"
			 class="wrap <?php echo esc_attr( $this->order_page_slug ); ?>">
			<h1><?php esc_html_e( 'Order Modules', 'sensei-lms' ); ?></h1>
			<?php

			$html = '';

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to unslash or sanitize in this case.
			if ( isset( $_GET['ordered'] ) && $_GET['ordered'] ) {
				$html .= '<div class="updated fade">' . "\n";
				$html .= '<p>' . esc_html__( 'The module order has been saved for this course.', 'sensei-lms' ) . '</p>' . "\n";
				$html .= '</div>' . "\n";
			}

			$courses = Sensei()->course->get_all_courses();

			$html .= '<form action="' . esc_url( admin_url( 'admin.php' ) ) . '" method="get">' . "\n";
			$html .= '<input type="hidden" name="post_type" value="course" />' . "\n";
			$html .= '<input type="hidden" name="page" value="' . esc_attr( $this->order_page_slug ) . '" />' . "\n";
			$html .= '<select id="module-order-course" name="course_id">' . "\n";
			$html .= '<option value="">' . esc_html__( 'Select a course', 'sensei-lms' ) . '</option>' . "\n";

			foreach ( $courses as $course ) {
				if ( has_term( '', $this->taxonomy, $course->ID ) ) {
					$course_id = '';
					if ( isset( $_GET['course_id'] ) ) {
						$course_id = intval( $_GET['course_id'] );
					}
					$html .= '<option value="' . esc_attr( intval( $course->ID ) ) . '" ' . selected( $course->ID, $course_id, false ) . '>' . esc_html( get_the_title( $course->ID ) ) . '</option>' . "\n";
				}
			}

			$html .= '</select>' . "\n";
			$html .= '<input type="submit" class="button-primary module-order-select-course-submit" value="' . esc_attr__( 'Select', 'sensei-lms' ) . '" />' . "\n";
			$html .= '</form>' . "\n";

			if ( isset( $_GET['course_id'] ) ) {
				$course_id = intval( $_GET['course_id'] );
				if ( $course_id > 0 ) {
					$modules = Sensei_Course_Structure::instance( $course_id )->get( 'edit' );
					if ( ! empty( $modules ) ) {
						$html .= '<form id="editgrouping" method="post" action="'
							. esc_url( admin_url( 'admin-post.php' ) )
							. '" class="validate">' . "\n";
						$html .= '<ul class="sortable-module-list">' . "\n";
						foreach ( $modules as $module ) {
							if ( 'module' !== $module['type'] ) {
								continue;
							}

							$html .= '<li class="' . $this->taxonomy . '"><span rel="' . esc_attr( $module['id'] ) . '" style="width: 100%;"> ' . esc_html( $module['title'] ) . '</span></li>' . "\n";
						}
						$html .= '</ul>' . "\n";
						$html .= '<input type="hidden" name="action" value="order_modules" />' . "\n";
						$html .= wp_nonce_field( 'order_modules', '_wpnonce', true, false ) . "\n";
						$html .= '<input type="hidden" name="module-order" value="" />' . "\n";
						$html .= '<input type="hidden" name="course_id" value="' . esc_attr( $course_id ) . '" />' . "\n";
						$html .= '<input type="submit" class="button-primary" value="' . esc_attr__( 'Save module order', 'sensei-lms' ) . '" />' . "\n";
						$html .= '<a href="' . esc_url( admin_url( 'post.php?post=' . $course_id . '&action=edit' ) ) . '" class="button-secondary">' . esc_html__( 'Edit course', 'sensei-lms' ) . '</a>' . "\n";
						$html .= '</form>';
					}
				}
			}

			echo wp_kses(
				$html,
				array_merge(
					wp_kses_allowed_html( 'post' ),
					array(
						// Explicitly allow form tag for WP.com.
						'form'   => array(
							'action' => array(),
							'class'  => array(),
							'id'     => array(),
							'method' => array(),
						),
						'input'  => array(
							'class' => array(),
							'name'  => array(),
							'type'  => array(),
							'value' => array(),
						),
						'option' => array(
							'selected' => array(),
							'value'    => array(),
						),
						'select' => array(
							'id'   => array(),
							'name' => array(),
						),
						'span'   => array(
							'rel'   => array(),
							'style' => array(),
						),
					)
				)
			);

			?>
		</div>
		<?php
	}

	/**
	 * Add custom columns to course list table.
	 *
	 * @since 1.8.0
	 *
	 * @param  array $columns Existing columns.
	 * @return array          Modified columns.
	 */
	public function course_columns( $columns = array() ) {
		$columns['modules'] = __( 'Modules', 'sensei-lms' );

		return $columns;
	}

	/**
	 * Load content in the course custom columns.
	 *
	 * @since 1.8.0
	 *
	 * @param  string  $column    Current column name.
	 * @param  integer $course_id The course ID.
	 * @return void
	 */
	public function course_column_content( $column = '', $course_id = 0 ) {
		if ( 'modules' === $column ) {
			$this->output_course_modules_column( $course_id );
		}
	}

	/**
	 * Output the course modules column HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param int $course_id
	 */
	private function output_course_modules_column( int $course_id ) {
		$modules = $this->get_course_modules( $course_id );

		if ( ! $modules ) {
			return;
		}

		/**
		 * Filter to change the number of links in the course modules column.
		 *
		 * @since 4.0.0
		 * @hook  sensei_module_course_column_max_links_count
		 *
		 * @param  {int} $max_links_count The number of links.
		 * @return {int}
		 */
		$max_links_count = apply_filters( 'sensei_module_course_column_max_links_count', 3 );
		$links_count     = count( $modules );

		foreach ( $modules as $index => $module ) {
			$is_last     = $index + 1 === $links_count;
			$should_hide = $index + 1 > $max_links_count;
			$module_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'post_type'     => 'course',
							$this->taxonomy => $module->slug,
						],
						admin_url( 'edit.php' )
					)
				),
				esc_html( $module->name )
			);
			?>
			<span class="<?php echo esc_attr( $should_hide ? 'hidden' : '' ); ?>">
				<?php echo $module_link . ( $is_last ? '' : ', ' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped. ?>
			</span>
			<?php if ( $is_last && $should_hide ) { ?>
				<a href="#" class="sensei-show-more">
					<?php
					printf(
						/* translators: %d: the number of links to be displayed */
						esc_html__( '+%d more', 'sensei-lms' ),
						intval( $links_count - $max_links_count )
					);
					?>
				</a>
				<?php
			}
		}

		if ( count( $modules ) > 1 ) {
			// Output the edit modules order link.
			echo sprintf(
				'<a class="sensei-wp-list-table-link" href="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'page'      => 'module-order',
							'course_id' => $course_id,
						],
						admin_url( 'admin.php' )
					)
				),
				esc_html__( 'Order Modules', 'sensei-lms' )
			);
		}
	}

	/**
	 * Add custom columns to lesson list table.
	 *
	 * @since  4.0.0
	 * @access private
	 *
	 * @param  array $columns Existing columns.
	 * @return array          Modified columns.
	 */
	public function add_lesson_columns( $columns = array() ) {
		// The lesson module column id should not be equal to "module".
		// @see https://core.trac.wordpress.org/ticket/56185.
		$columns['modules'] = __( 'Module', 'sensei-lms' );

		return $columns;
	}

	/**
	 * Load content in the lesson custom columns.
	 *
	 * @since  4.0.0
	 * @access private
	 *
	 * @param  string  $column    Current column name.
	 * @param  integer $lesson_id The lesson ID.
	 */
	public function add_lesson_column_content( $column = '', $lesson_id = 0 ) {
		if ( 'modules' === $column ) {
			$modules = wp_get_post_terms( $lesson_id, $this->taxonomy );
			$module  = $modules && is_array( $modules ) ? $modules[0] : null;

			if ( $module ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url(
						add_query_arg(
							[
								'post_type'     => 'lesson',
								$this->taxonomy => $module->slug,
							],
							admin_url( 'edit.php' )
						)
					),
					esc_html( $module->name )
				);
			}
		}
	}

	/**
	 * Save module order for course
	 *
	 * @since 1.8.0
	 *
	 * @param  string  $order_string Comma-separated string of module IDs
	 * @param  integer $course_id ID of course
	 * @return boolean                 True on success, false on failure
	 */
	private function save_course_module_order( $order_string = '', $course_id = 0 ) {
		if ( $order_string && $course_id ) {
			remove_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70 );
			$course_structure = Sensei_Course_Structure::instance( $course_id )->get( 'edit' );
			add_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70, 3 );

			$order = array_map( 'absint', explode( ',', $order_string ) );

			$course_structure = Sensei_Course_Structure::sort_structure( $course_structure, $order, 'module' );

			if ( true === Sensei_Course_Structure::instance( $course_id )->save( $course_structure ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get module order for course
	 *
	 * @since 1.8.0
	 *
	 * @param  integer $course_id ID of course
	 * @return mixed              Module order on success, false if no module order has been saved
	 */
	public function get_course_module_order( $course_id = 0 ) {
		if ( $course_id ) {
			$order = get_post_meta( intval( $course_id ), '_module_order', true );
			return $order;
		}
		return false;
	}

	/**
	 * Modify module taxonomy columns
	 *
	 * @since 1.8.0
	 *
	 * @param  array $columns Default columns
	 * @return array          Modified columns
	 */
	public function taxonomy_column_headings( $columns ) {

		unset( $columns['posts'] );

		$columns['lessons'] = __( 'Lessons', 'sensei-lms' );

		return $columns;
	}

	/**
	 * Manage content in custom module taxonomy columns
	 *
	 * @since 1.8.0
	 *
	 * @param  string  $column_data Default data for column
	 * @param  string  $column_name Name of current column
	 * @param  integer $term_id ID of current term
	 * @return string               Modified column data
	 */
	public function taxonomy_column_content( $column_data, $column_name, $term_id ) {

		$args = array(
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => $this->taxonomy,
					'field'    => 'id',
					'terms'    => intval( $term_id ),
				),
			),
		);

		$module = get_term( $term_id, $this->taxonomy );

		switch ( $column_name ) {

			case 'lessons':
				$args['post_type'] = 'lesson';
				$lessons           = get_posts( $args );
				$total_lessons     = count( $lessons );
				$column_data       = '<a href="' . admin_url( 'edit.php?module=' . urlencode( $module->slug ) . '&post_type=lesson' ) . '">' . intval( $total_lessons ) . '</a>';
				break;
		}

		return $column_data;
	}

	/**
	 * Add 'Module' columns to Analysis Lesson Overview table
	 *
	 * @since 1.8.0
	 *
	 * @param  array $columns Default columns
	 * @return array          Modified columns
	 */
	public function analysis_overview_column_title( $columns ) {

		if ( isset( $_GET['view'] ) && 'lessons' == $_GET['view'] ) {
			$new_columns = array();
			if ( is_array( $columns ) && 0 < count( $columns ) ) {
				foreach ( $columns as $column => $title ) {
					$new_columns[ $column ] = $title;
					if ( $column == 'title' ) {
						$new_columns['lesson_module'] = __( 'Module', 'sensei-lms' );
					}
				}
			}

			if ( 0 < count( $new_columns ) ) {
				return $new_columns;
			}
		}

		return $columns;
	}

	/**
	 * Data for 'Module' column Analysis Lesson Overview table
	 *
	 * @since 1.8.0
	 * @deprecated 4.3.0
	 *
	 * @param  array   $columns Table column data
	 * @param  WP_Post $lesson
	 * @return array              Updated column data
	 */
	public function analysis_overview_column_data( $columns, $lesson ) {

		_deprecated_function( __METHOD__, '4.3.0' );

		if ( isset( $_GET['view'] ) && 'lessons' == $_GET['view'] ) {
			$lesson_module      = '';
			$lesson_module_list = wp_get_post_terms( $lesson->ID, $this->taxonomy );
			if ( is_array( $lesson_module_list ) && count( $lesson_module_list ) > 0 ) {
				foreach ( $lesson_module_list as $single_module ) {
					$lesson_module = '<a href="' . esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=' . urlencode( $this->taxonomy ) . '&tag_ID=' . urlencode( $single_module->term_id ) ) ) . '">' . $single_module->name . '</a>';
					break;
				}
			}

			$columns['lesson_module'] = $lesson_module;
		}

		return $columns;
	}

	/**
	 * Add 'Module' columns to Analysis Course table
	 *
	 * @since 1.8.0
	 *
	 * @param  array $columns Default columns
	 * @return array          Modified columns
	 */
	public function analysis_course_column_title( $columns ) {
		if ( isset( $_GET['view'] ) && 'lessons' == $_GET['view'] ) {
			$columns['lesson_module'] = __( 'Module', 'sensei-lms' );
		}
		return $columns;
	}

	/**
	 * Data for 'Module' column in Analysis Course table
	 *
	 * @since 1.8.0
	 *
	 * @param  array   $columns Table column data
	 * @param  WP_Post $lesson
	 * @return array              Updated columns data
	 */
	public function analysis_course_column_data( $columns, $lesson ) {
		if ( isset( $_GET['course_id'] ) ) {
			$lesson_module      = '';
			$lesson_module_list = wp_get_post_terms( $lesson->ID, $this->taxonomy );
			if ( is_array( $lesson_module_list ) && count( $lesson_module_list ) > 0 ) {
				foreach ( $lesson_module_list as $single_module ) {
					$lesson_module = '<a href="' . esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=' . urlencode( $this->taxonomy ) . '&tag_ID=' . urlencode( $single_module->term_id ) ) ) . '">' . $single_module->name . '</a>';
					break;
				}
			}

			$columns['lesson_module'] = $lesson_module;
		}

		return $columns;
	}

	/**
	 * Get module for lesson
	 *
	 * This function also checks if the module still
	 * exists on the course before returning it. Although
	 * the lesson has a module the same module must exist on the
	 * course for it to be valid.
	 *
	 * @since 1.8.0
	 *
	 * @param  integer $lesson_id ID of lesson
	 * @return object             Module taxonomy term object
	 */
	public function get_lesson_module( $lesson_id = 0 ) {
		$lesson_id = intval( $lesson_id );
		if ( ! ( intval( $lesson_id > 0 ) ) ) {
			return false;
		}

		// get taxonomy terms on this lesson
		$modules = wp_get_post_terms( $lesson_id, $this->taxonomy );

		// check if error returned
		if ( empty( $modules )
			|| is_wp_error( $modules )
			|| isset( $modules['errors'] ) ) {

			return false;

		}

		// get the last item in the array there should be only be 1 really.
		// this method works for all php versions.
		foreach ( $modules as $module ) {
			break;
		}

		if ( ! isset( $module ) || ! is_object( $module ) || is_wp_error( $module ) ) {
			return false;
		}

		$module->url = get_term_link( $module, $this->taxonomy );
		$course_id   = intval( get_post_meta( intval( $lesson_id ), '_lesson_course', true ) );
		if ( isset( $course_id ) && 0 < $course_id ) {

			// the course should contain the same module taxonomy term for this to be valid
			if ( ! has_term( $module->term_id, $this->taxonomy, $course_id ) ) {
				return false;
			}

			$module->url = esc_url( add_query_arg( 'course_id', intval( $course_id ), $module->url ) );
		}
		return $module;

	}

	/**
	 * Get ordered array of all modules in course
	 *
	 * @since 1.8.0
	 *
	 * @param  integer $course_id ID of course
	 * @return WP_Term[]          Ordered array of module taxonomy term objects
	 */
	public function get_course_modules( $course_id = 0 ) {

		$course_id = intval( $course_id );
		if ( empty( $course_id ) ) {
			return array();
		}

		// Get modules for course
		$modules = wp_get_post_terms( $course_id, $this->taxonomy );

		// Get custom module order for course
		$order = $this->get_course_module_order( $course_id );

		if ( ! $order ) {
			return $modules;
		}

		// Sort by custom order
		$ordered_modules   = array();
		$unordered_modules = array();
		foreach ( $modules as $module ) {
			$order_key = array_search( $module->term_id, $order );
			if ( $order_key !== false ) {
				$ordered_modules[ $order_key ] = $module;
			} else {
				$unordered_modules[] = $module;
			}
		}

		// Order modules correctly
		ksort( $ordered_modules );

		// Append modules that have not yet been ordered
		if ( count( $unordered_modules ) > 0 ) {
			$ordered_modules = array_merge( $ordered_modules, $unordered_modules );
		}

		// remove order key but maintain order
		$ordered_modules_with_keys_in_sequence = array();
		foreach ( $ordered_modules as $key => $module ) {

			$ordered_modules_with_keys_in_sequence[] = $module;

		}

		return $ordered_modules_with_keys_in_sequence;

	}

	/**
	 * Load frontend CSS
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public function enqueue_styles() {

		$disable_styles = false;
		if ( isset( Sensei()->settings->settings['styles_disable'] ) ) {
			$disable_styles = Sensei()->settings->settings['styles_disable'];
		}

		// Add filter for theme overrides
		$disable_styles = apply_filters( 'sensei_disable_styles', $disable_styles );

		if ( ! $disable_styles ) {
			Sensei()->assets->enqueue( $this->taxonomy . '-frontend', 'css/modules-frontend.css' );
		}

	}

	/**
	 * Load admin Javascript
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		$screen = get_current_screen();

		/**
		 * Filter the page hooks where modules admin script can be loaded on.
		 *
		 * @param array $white_listed_pages
		 */
		$script_on_pages_white_list = apply_filters(
			'sensei_module_admin_script_page_white_lists',
			array( 'admin_page_module-order' )
		);

		// Only load module scripts when adding, editing or ordering modules or editing course/lesson.
		$screen_related =
			$screen &&
			(
				'module' === $screen->taxonomy
				|| 'course' === $screen->id
				|| 'lesson' === $screen->id
			);

		if ( ! ( in_array( $hook, $script_on_pages_white_list ) || $screen_related ) ) {
			return;
		}
		wp_enqueue_script( 'sensei-chosen-ajax' );

		Sensei()->assets->enqueue(
			$this->taxonomy . '-admin',
			'js/modules-admin.js',
			[ 'jquery', 'sensei-chosen-ajax', 'jquery-ui-sortable', 'sensei-core-select2' ],
			true
		);

		// localized module data
		$localize_modulesAdmin = array(
			'search_courses_nonce'        => wp_create_nonce( 'search-courses' ),
			'getLessonModuleMetaBoxNonce' => wp_create_nonce( 'get_lesson_module_metabox_nonce' ),
			'selectPlaceholder'           => __( 'Search for courses', 'sensei-lms' ),
		);

		wp_localize_script( $this->taxonomy . '-admin', 'modulesAdmin', $localize_modulesAdmin );
	}

	/**
	 * Load admin CSS
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public function admin_enqueue_styles() {

		Sensei()->assets->enqueue( $this->taxonomy . '-sortable', 'css/modules-admin.css' );

	}

	/**
	 * Show the title modules on the single course template.
	 *
	 * Function is hooked into sensei_single_course_modules_before.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function course_modules_title() {
		if ( ! sensei_module_has_lessons() || ! Sensei_Utils::show_course_lessons( get_the_ID() ) ) {
			return;
		}

		global $post;

		/**
		 * Filters the module title on the single course page.
		 *
		 * @since 2.2.0
		 *
		 * @param string $html   The HTML to be displayed.
		 * @param int $course_id Course ID.
		 */
		echo wp_kses_post( apply_filters( 'sensei_modules_title', '<header class="modules-title"><h2>' . __( 'Modules', 'sensei-lms' ) . '</h2></header>', $post->ID ) );
	}

	/**
	 * Display the single course modules content this will only show
	 * if the course has modules.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function load_course_module_content_template() {
		if ( ! Sensei_Utils::show_course_lessons( get_the_ID() ) ) {
			return;
		}

		// load backwards compatible template name if it exists in the users theme
		$located_template = locate_template( Sensei()->template_url . 'single-course/course-modules.php' );
		if ( $located_template ) {

			Sensei_Templates::get_template( 'single-course/course-modules.php' );
			return;

		}

		Sensei_Templates::get_template( 'single-course/modules.php' );

	}

	/**
	 * Returns all lessons for the given module ID
	 *
	 * @since 1.8.0
	 *
	 * @param $course_id
	 * @param $term_id
	 * @return array $lessons
	 */
	public function get_lessons( $course_id, $term_id ) {

		$lesson_query = $this->get_lessons_query( $course_id, $term_id );

		if ( isset( $lesson_query->posts ) ) {

			return $lesson_query->posts;

		} else {

			return array();

		}

	}

	/**
	 * Returns all lessons for the given module ID
	 *
	 * @param int          $course_id                  Course post ID.
	 * @param int          $term_id                    Module term ID.
	 * @param array|string $course_lessons_post_status Post status for lessons. Can be an array of statuses.
	 *
	 * @return WP_Query $lessons_query
	 * @since 1.8.0
	 */
	public function get_lessons_query( $course_id, $term_id, $course_lessons_post_status = null ) {
		global $wp_query;
		if ( empty( $term_id ) || empty( $course_id ) ) {

			return array();

		}

		if ( ! $course_lessons_post_status ) {
			$course_lessons_post_status = isset( $wp_query ) && $wp_query->is_preview() ? 'all' : 'publish';
		}

		$args = array(
			'post_type'        => 'lesson',
			'post_status'      => $course_lessons_post_status,
			'posts_per_page'   => -1,
			'meta_query'       => array(
				array(
					'key'     => '_lesson_course',
					'value'   => intval( $course_id ),
					'compare' => '=',
				),
			),
			'tax_query'        => array(
				array(
					'taxonomy' => 'module',
					'field'    => 'id',
					'terms'    => intval( $term_id ),
				),
			),
			'orderby'          => 'menu_order',
			'order'            => 'ASC',
			'suppress_filters' => 0,
		);

		if ( version_compare( Sensei()->version, '1.6.0', '>=' ) ) {
			$args['meta_key'] = '_order_module_' . intval( $term_id );
			$args['orderby']  = 'meta_value_num date ID';
		}

		$lessons_query = new WP_Query( $args );

		return $lessons_query;

	}

	/**
	 * Find the lesson in the given course that doesn't belong
	 * to any of the courses modules
	 *
	 * @param int          $course_id    The course id.
	 * @param string|array $post_status  The status of the lessons.
	 *
	 * @return array $non_module_lessons
	 */
	public function get_none_module_lessons( $course_id, $post_status = 'publish' ) {
		// Return early if no course was passed.
		if ( empty( $course_id ) || 'course' !== get_post_type( $course_id ) ) {
			return [];
		}

		// Fetch terms array which must be excluded from the result.
		$course_modules = $this->get_course_modules( $course_id );
		$base_args      = [];

		if ( ! empty( $course_modules ) && is_array( $course_modules ) ) {
			$term_ids               = wp_list_pluck( $course_modules, 'term_id' );
			$base_args['tax_query'] = [
				[
					'taxonomy' => 'module',
					'field'    => 'id',
					'terms'    => $term_ids,
					'operator' => 'NOT IN',
				],
			];
		}

		return Sensei()->course->course_lessons( $course_id, $post_status, 'all', $base_args );
	}

	/**
	 * Register the modules taxonomy
	 *
	 * @since 1.8.0
	 * @since 1.9.7 Added `not_found` label.
	 */
	public function setup_modules_taxonomy() {

		$labels = array(
			'name'              => __( 'Modules', 'sensei-lms' ),
			'singular_name'     => __( 'Module', 'sensei-lms' ),
			'search_items'      => __( 'Search Modules', 'sensei-lms' ),
			'all_items'         => __( 'All Modules', 'sensei-lms' ),
			'parent_item'       => __( 'Parent Module', 'sensei-lms' ),
			'parent_item_colon' => __( 'Parent Module:', 'sensei-lms' ),
			'view_item'         => __( 'View Module', 'sensei-lms' ),
			'edit_item'         => __( 'Edit Module', 'sensei-lms' ),
			'update_item'       => __( 'Update Module', 'sensei-lms' ),
			'add_new_item'      => __( 'Add New Module', 'sensei-lms' ),
			'new_item_name'     => __( 'New Module Name', 'sensei-lms' ),
			'menu_name'         => __( 'Modules', 'sensei-lms' ),
			'not_found'         => __( 'No modules found.', 'sensei-lms' ),
			'back_to_items'     => __( '&larr; Back to Modules', 'sensei-lms' ),
		);

		/**
		 * Filter to alter the Sensei Modules rewrite slug
		 *
		 * @since 1.8.0
		 * @param string default 'modules'
		 */
		$modules_rewrite_slug = apply_filters( 'sensei_module_slug', 'modules' );

		$args = array(
			'public'             => true,
			'hierarchical'       => true,
			'show_admin_column'  => false,
			'capabilities'       => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'edit_courses',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_courses',
			),
			'show_in_nav_menus'  => false,
			'show_in_quick_edit' => false,
			'show_ui'            => true,
			'rewrite'            => array( 'slug' => $modules_rewrite_slug ),
			'labels'             => $labels,
		);

		register_taxonomy( 'module', array( 'course', 'lesson' ), $args );

	}

	/**
	 * When the wants to edit the lesson modules redirect them to the course modules.
	 *
	 * This function is hooked into the admin_menu
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function redirect_to_lesson_module_taxonomy_to_course() {

		global $typenow , $taxnow;

		if ( 'lesson' == $typenow && 'module' == $taxnow ) {
			wp_safe_redirect( esc_url_raw( 'edit-tags.php?taxonomy=module&post_type=course' ) );
		}

	}

	/**
	 * Completely remove the module menu item under lessons.
	 *
	 * This function is hooked into the admin_menu
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function remove_lessons_menu_model_taxonomy() {
		global $submenu;

		if ( ! isset( $submenu['edit.php?post_type=lesson'] ) || ! is_array( $submenu['edit.php?post_type=lesson'] ) ) {
			return; // exit
		}

		$lesson_main_menu = $submenu['edit.php?post_type=lesson'];
		foreach ( $lesson_main_menu as $index => $sub_item ) {

			if ( 'edit-tags.php?taxonomy=module&amp;post_type=lesson' == $sub_item[2] ) {
				unset( $submenu['edit.php?post_type=lesson'][ $index ] );
			}
		}

	}

	/**
	 * Completely remove the second modules under courses
	 *
	 * This function is hooked into the admin_menu
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function remove_courses_menu_model_taxonomy() {
		global $submenu;

		if ( ! isset( $submenu['edit.php?post_type=course'] ) || ! is_array( $submenu['edit.php?post_type=course'] ) ) {
			return; // exit
		}

		$course_main_menu = $submenu['edit.php?post_type=course'];
		foreach ( $course_main_menu as $index => $sub_item ) {

			if ( 'edit-tags.php?taxonomy=module&amp;post_type=course' == $sub_item[2] ) {
				unset( $submenu['edit.php?post_type=course'][ $index ] );
			}
		}

	}

	/**
	 * Determine the author of a module term term by looking at
	 * the prefixed author id. This function will query the full term object.
	 * Will return the admin user author could not be determined.
	 *
	 * @since 1.8.0
	 *
	 * @param string $term_name
	 * @return array $owners { type WP_User }. Empty array if none if found.
	 */
	public static function get_term_authors( $term_name ) {

		$terms = get_terms(
			array( 'module' ),
			array(
				'name__like' => $term_name,
				'hide_empty' => false,
			)
		);

		$owners = array();
		if ( empty( $terms ) ) {

			return $owners;

		}

		// setup the admin user
		// if there are more handle them appropriately and get the ones we really need that matches the desired name exactly
		foreach ( $terms as $term ) {
			if ( $term->name == $term_name ) {

				// look for the author in the slug
				$owners[] = self::get_term_author( $term->slug );

			}
		}

		return $owners;

	}

	/**
	 * Looks at a term slug and figures out
	 * which author created the slug. The author was
	 * appended when the user saved the module term in the course edit
	 * screen.
	 *
	 * @since 1.8.0
	 *
	 * @param $slug
	 * @return WP_User $author if no author is found or invalid term is passed the admin user will be returned.
	 */
	public static function get_term_author( $slug = '' ) {

		$term_owner = get_user_by( 'email', get_bloginfo( 'admin_email' ) );

		// Fallaback in case the admin email does not match a user, otherwise it shows warnings.
		if ( ! $term_owner ) {
			$site_admins = get_super_admins();

			if ( ! empty( $site_admins ) && is_array( $site_admins ) ) {
				$term_owner = get_user_by( 'login', $site_admins[0] );
			}
		}

		if ( empty( $slug ) ) {

			return $term_owner;

		}
		$term = get_term_by( 'slug', $slug, 'module' );

		if ( $term ) {
			$author_meta = get_term_meta( $term->term_id, 'module_author', true );
			if ( $author_meta ) {
				return get_user_by( 'id', $author_meta );
			}
		}
		// look for the author in the slug.
		$slug_parts = explode( '-', $slug );

		if (
			count( $slug_parts ) > 1
			&& is_numeric( $slug_parts[0] )
		) {

			// get the user data
			$possible_user_id = $slug_parts[0];
			$author           = get_userdata( $possible_user_id );

			// if the user doesnt exist for the first part of the slug
			// then this slug was also created by admin
			if ( is_a( $author, 'WP_User' ) ) {

				$term_owner = $author;

			}
		}

		return $term_owner;
	}

	/**
	 * Display the Sensei modules taxonomy terms metabox
	 *
	 * @since 1.8.0
	 *
	 * @hooked into add_meta_box
	 *
	 * @param WP_Post $post Post object.
	 */
	public function course_module_metabox( $post ) {

		$tax_name = 'module';
		$taxonomy = get_taxonomy( 'module' );

		?>
		<div id="taxonomy-<?php echo esc_attr( $tax_name ); ?>" class="categorydiv">
			<div id="<?php echo esc_attr( $tax_name ); ?>-all" class="tabs-panel">
				<?php
				$name = ( $tax_name === 'category' ) ? 'post_category' : 'tax_input[' . $tax_name . ']';
				echo "<input type='hidden' name='" . esc_attr( $name ) . "[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
				?>
				<ul id="<?php echo esc_attr( $tax_name ); ?>checklist" data-wp-lists="list:<?php echo esc_attr( $tax_name ); ?>" class="categorychecklist form-no-clear">
					<?php
					wp_terms_checklist(
						$post->ID,
						array(
							'taxonomy' => $tax_name,
						)
					);
					?>
				</ul>
			</div>
			<?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>
				<div id="<?php echo esc_attr( $tax_name ); ?>-adder" class="wp-hidden-children">
					<h4>
						<a id="sensei-<?php echo esc_attr( $tax_name ); ?>-add-toggle" href="#<?php echo esc_url( $tax_name ); ?>-add" class="hide-if-no-js">
							<?php
							/* translators: %s: add new taxonomy label */
							printf( esc_html__( '+ %s', 'sensei-lms' ), esc_html( $taxonomy->labels->add_new_item ) );
							?>
						</a>
					</h4>
					<p id="sensei-<?php echo esc_attr( $tax_name ); ?>-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="new<?php echo esc_attr( $tax_name ); ?>"><?php echo esc_html( $taxonomy->labels->add_new_item ); ?></label>
						<input type="text" name="new<?php echo esc_attr( $tax_name ); ?>" id="new<?php echo esc_attr( $tax_name ); ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true"/>
						<a class="button" id="sensei-<?php echo esc_attr( $tax_name ); ?>-add-submit" class="button category-add-submit"><?php echo esc_attr( $taxonomy->labels->add_new_item ); ?></a>
						<?php wp_nonce_field( '_ajax_nonce-add-' . $tax_name, 'add_module_nonce' ); ?>
						<span id="<?php echo esc_attr( $tax_name ); ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Submits a new module term prefixed with the
	 * the current author id.
	 *
	 * @since 1.8.0
	 */
	public static function add_new_module_term() {

		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], '_ajax_nonce-add-module' ) ) {
			wp_send_json_error( array( 'error' => 'wrong security nonce' ) );
		}

		// get the term an create the new term storing infomration
		$term_name = sanitize_text_field( $_POST['newTerm'] );

		if ( current_user_can( 'manage_options' ) ) {

			$term_slug = str_ireplace( ' ', '-', trim( $term_name ) );

		} else {

			$term_slug = get_current_user_id() . '-' . str_ireplace( ' ', '-', trim( $term_name ) );

		}

		$course_id = sanitize_text_field( $_POST['course_id'] );

		// save the term
		$slug = wp_insert_term( $term_name, 'module', array( 'slug' => $term_slug ) );

		// send error for all errors except term exits
		if ( is_wp_error( $slug ) ) {

			// prepare for possible term name and id to be passed down if term exists
			$term_data = array();

			// if term exists also send back the term name and id
			if ( isset( $slug->errors['term_exists'] ) ) {

				$term              = get_term_by( 'slug', $term_slug, 'module' );
				$term_data['name'] = $term_name;
				$term_data['id']   = $term->term_id;

				// set the object terms
				wp_set_object_terms( $course_id, $term->term_id, 'module', true );
			}

			wp_send_json_error(
				array(
					'errors' => $slug->errors,
					'term'   => $term_data,
				)
			);

		}

		// make sure the new term is checked for this course
		wp_set_object_terms( $course_id, $slug['term_id'], 'module', true );

		// Handle request then generate response using WP_Ajax_Response
		wp_send_json_success(
			array(
				'termId'   => $slug['term_id'],
				'termName' => $term_name,
			)
		);

	}

	/**
	 * Get course modules
	 *
	 * @deprecated 3.15.0
	 */
	public function ajax_get_course_modules() {
		_deprecated_function( __METHOD__, '3.15.0', 'Sensei_Core_Modules::handle_get_lesson_module_metabox' );

		// Security check
		check_ajax_referer( 'get-course-modules', 'security' );

		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : null;
		if ( null === $course_id ) {
			wp_send_json_error( array( 'error' => 'invalid course id' ) );
		}

		$html_content = $this->render_lesson_module_select_for_course( $course_id );

		wp_send_json_success( array( 'content' => $html_content ) );
	}

	/**
	 * Handles the lesson module meta box ajax request by outputting the box content HTML.
	 *
	 * @since 3.15.0
	 */
	public function handle_get_lesson_module_metabox() {
		// Security check.
		check_ajax_referer( 'get_lesson_module_metabox_nonce', 'security' );

		if ( isset( $_GET['lesson_id'] ) && isset( $_GET['course_id'] ) ) {
			$this->output_lesson_module_metabox(
				get_post( (int) $_GET['lesson_id'] ),
				(int) $_GET['course_id']
			);
		}

		wp_die(); // This is required to terminate immediately and return a proper response.
	}

	/**
	 * Limit the course module metabox
	 * term list to only those on courses belonging to current teacher.
	 *
	 * Hooked into 'get_terms'
	 *
	 * @since 1.8.0
	 */
	public function filter_module_terms( $terms, $taxonomies, $args ) {

		// dont limit for admins and other taxonomies. This should also only apply to admin
		if ( current_user_can( 'manage_options' ) || ! $taxonomies || ! in_array( 'module', $taxonomies ) || ! is_admin() ) {
			return $terms;
		}

		// in certain cases the array is passed in as reference to the parent term_id => parent_id
		if ( isset( $args['fields'] ) ) {
			if ( in_array( $args['fields'], array( 'ids', 'tt_ids' ), true ) ) {
				return $terms;
			}

			// change only scrub the terms ids form the array keys
			if ( 'id=>parent' == $args['fields'] ) {
				$terms = array_keys( $terms );
			}
		}

		$teachers_terms = $this->filter_terms_by_owner_no_infinite_loop( $terms, get_current_user_id() );

		return $teachers_terms;
	}

	/**
	 * Call filter_terms_by_owner without infinite loops
	 *
	 * @param array $terms Terms.
	 * @param int   $user_id User Id.
	 * @return array
	 */
	private function filter_terms_by_owner_no_infinite_loop( $terms, $user_id ) {
		// avoid infinite call loop.
		remove_filter( 'get_terms', array( $this, 'filter_module_terms' ), 20 );
		$teachers_terms = $this->filter_terms_by_owner( $terms, $user_id );
		// add filter again as removed above.
		add_filter( 'get_terms', array( $this, 'filter_module_terms' ), 20, 3 );
		return $teachers_terms;
	}

	/**
	 * For the selected items on a course module only return those
	 * for the current user. This does not apply to admin and super admin users.
	 *
	 * hooked into get_object_terms
	 *
	 * @since 1.8.0
	 */
	public function filter_course_selected_terms( $terms, $course_ids_array, $taxonomies ) {

		// dont limit for admins and other taxonomies. This should also only apply to admin
		if ( current_user_can( 'manage_options' ) || ! is_admin() || empty( $terms )
			// only apply this to module only taxonomy queries so 1 taxonomy only:
			|| count( $taxonomies ) > 1 || ! in_array( 'module', $taxonomies ) ) {
			return $terms;
		}

		$term_objects = $this->filter_terms_by_owner( $terms, get_current_user_id() );

		// if term objects were passed in send back objects
		// if term id were passed in send that back
		if ( is_object( $terms[0] ) ) {
			return $term_objects;
		}

		$terms = array();
		foreach ( $term_objects as $term_object ) {
			$terms[] = $term_object->term_id;
		}

		return $terms;

	}

	/**
	 * Filter the given terms and only return the
	 * terms that belong to the given user id.
	 *
	 * @since 1.8.0
	 * @param $terms
	 * @param $user_id
	 * @return array
	 */
	public function filter_terms_by_owner( $terms, $user_id ) {

		$users_terms = array();

		foreach ( $terms as $index => $term ) {

			if ( is_numeric( $term ) ) {
				// the term id was given, get the term object
				$term = get_term( $term, 'module' );
			}

			$author = self::get_term_author( $term->slug );

			if ( $user_id == $author->ID ) {
				// add the term to the teachers terms
				$users_terms[] = $term;
			}
		}

		/**
		 * Filters the module terms when ownership is being checked for them.
		 *
		 * @hook   sensei_filter_module_terms_by_owner
		 * @since  4.9.0
		 *
		 * @param  {WP_Term[]} $user_terms The terms after applying the filter by owner.
		 * @param  {WP_Term[]|int[]} $terms The original terms before the filtering was applied.
		 * @param  {int} $user_id The user ID to check for ownership.
		 * @return {WP_Term[]} The final list of terms that must be considered as owner by the given user ID.
		 */
		return apply_filters( 'sensei_filter_module_terms_by_owner', $users_terms, $terms, $user_id );
	}

	/**
	 * Add the teacher name next to modules. Only works in Admin for Admin users.
	 * This will not add name to terms belonging to admin user.
	 *
	 * Hooked into 'get_terms'
	 *
	 * @since 1.8.0
	 */
	public function append_teacher_name_to_module( $terms, $taxonomies, $args ) {
		// only for admin users ont he module taxonomy
		if ( empty( $terms ) || ! is_array( $taxonomies ) || ! current_user_can( 'manage_options' ) || ! in_array( 'module', $taxonomies ) || ! is_admin() ) {
			return $terms;
		}

		// in certain cases the array is passed in as reference to the parent term_id => parent_id
		// In other cases we explicitly require ids (as in 'tt_ids' or 'ids')
		// simply return this as wp doesn't need an array of stdObject Term
		if ( isset( $args['fields'] ) && in_array( $args['fields'], array( 'id=>parent', 'tt_ids', 'ids' ) ) ) {

			return $terms;

		}

		$users_terms = [];

		// loop through and update all terms adding the author name
		foreach ( $terms as $index => $term ) {

			if ( is_numeric( $term ) ) {
				// the term id was given, get the term object
				$term = get_term( $term, 'module' );
			}

			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			if ( 'module' !== $term->taxonomy ) {
				$users_terms[] = $term;
				continue;
			}

			$author = self::get_term_author( $term->slug );

			if ( ! user_can( $author, 'manage_options' ) && isset( $term->name ) ) {
				$term->name = $term->name . ' (' . $author->display_name . ') ';
			}

			// add the term to the teachers terms
			$users_terms[] = $term;

		}

		return $users_terms;
	}

	/**
	 * Remove modules metabox that come by default
	 * with the modules taxonomy. We are removing this as
	 * we have created our own custom meta box.
	 */
	public static function remove_default_modules_box() {

		remove_meta_box( 'modulediv', 'course', 'side' );

	}

	/**
	 * When a course is save make sure to reset the transient set
	 * for it when determining the none module lessons.
	 *
	 * @since 1.9.0
	 * @deprecated 3.6.0
	 *
	 * @param int $post_id The post ID.
	 */
	public static function reset_none_modules_transient( $post_id ) {
		_deprecated_function( __METHOD__, '3.6.0' );
	}

	/**
	 * Setup the single course module loop.
	 *
	 * Setup the global $sensei_modules_loop
	 *
	 * @since 1.9.0
	 */
	public static function setup_single_course_module_loop() {

		global $sensei_modules_loop, $post;
		$course_id = $post->ID;

		$modules = Sensei()->modules->get_course_modules( $course_id );

		// initial setup
		$sensei_modules_loop['total']   = 0;
		$sensei_modules_loop['modules'] = array();
		$sensei_modules_loop['current'] = -1;

		// exit if this course doesn't have modules
		if ( ! $modules || empty( $modules ) ) {
			return;
		}

		$lessons_in_all_modules = array();
		foreach ( $modules as $term ) {

			$lessons_in_this_module = Sensei()->modules->get_lessons( $course_id, $term->term_id );
			$lessons_in_all_modules = array_merge( $lessons_in_all_modules, $lessons_in_this_module );

		}

		// setup all of the modules loop variables
		$sensei_modules_loop['total']     = count( $modules );
		$sensei_modules_loop['modules']   = $modules;
		$sensei_modules_loop['current']   = -1;
		$sensei_modules_loop['course_id'] = $course_id;

	}

	/**
	 * Tear down the course module loop.
	 *
	 * @since 1.9.0
	 */
	public static function teardown_single_course_module_loop() {

		global $sensei_modules_loop;

		// reset all of the modules loop variables
		$sensei_modules_loop['total']   = 0;
		$sensei_modules_loop['modules'] = array();
		$sensei_modules_loop['current'] = -1;

		// set the current course to be the global post again
		wp_reset_query();
	}

	/**
	 * Set teacher meta for module.
	 *
	 * @since 4.6.0
	 *
	 * @param int $module_id  Term ID.
	 * @param int $teacher_id ID of module teacher.
	 */
	public static function update_module_teacher_meta( $module_id, $teacher_id ) {
		if ( user_can( $teacher_id, 'manage_options' ) ) {
			delete_term_meta( $module_id, 'module_author' );
		} else {
			update_term_meta(
				$module_id,
				'module_author',
				$teacher_id
			);
		}
	}
}
