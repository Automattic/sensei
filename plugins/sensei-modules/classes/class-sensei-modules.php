<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Sensei_Modules {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $order_page_slug;
	public $taxonomy;

	public function __construct( $file ) {
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->taxonomy = 'module';
		$this->order_page_slug = 'module-order';

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Register 'module' taxonomy
		add_action( 'init', array( $this, 'register_taxonomy' ), 0 );

		// Manage lesson meta boxes for taxonomy
		add_action( 'add_meta_boxes', array( $this, 'lesson_metaboxes' ), 25 );

		// Save lesson meta box
		add_action( 'save_post', array( $this, 'save_lesson_module' ), 10, 1 );

		// Frontend styling
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		// Admin styling
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );

		// Handle module completion record
		add_action( 'sensei_user_lesson_end', array( $this, 'save_lesson_module_progress' ), 10, 2 );
		add_action( 'sensei_user_lesson_start', array( $this, 'save_lesson_module_progress' ), 10, 2 );
		add_action( 'wp', array( $this, 'save_module_progress' ), 10 );

		// Handle module ordering
		add_action( 'admin_menu', array( $this, 'register_module_order_screen' ), 10 );
		add_filter( 'manage_edit-course_columns', array( $this, 'course_columns' ), 11, 1 );
		add_action( 'manage_posts_custom_column', array( $this, 'course_column_content' ), 11, 2 );

		// Add course field to taxonomy
		add_action( $this->taxonomy . '_add_form_fields', array( $this, 'add_module_fields' ), 50, 1 );
        add_action( $this->taxonomy . '_edit_form_fields', array( $this, 'edit_module_fields' ), 1, 1 );
        add_action( 'edited_' . $this->taxonomy, array( $this, 'save_module_course' ), 10, 2 );
        add_action( 'created_' . $this->taxonomy, array( $this, 'save_module_course' ), 10, 2 );
        add_action( 'wp_ajax_sensei_json_search_courses', array( $this, 'search_courses_json' ) );

        // Manage module taxonomy archive page
        add_filter( 'template_include', array( $this, 'module_archive_template' ), 10 );
        add_action( 'pre_get_posts', array( $this, 'module_archive_filter' ), 10, 1 );
        add_filter( 'sensei_lessons_archive_text', array( $this, 'module_archive_title' ) );
        add_action( 'sensei_lesson_archive_header', array( $this, 'module_archive_description' ), 11 );
        add_action( 'sensei_lesson_archive_main_content', array( $this, 'module_back_to_course_link' ), 50);
        add_action( 'sensei_pagination', array( $this, 'module_navigation_links' ), 11 );
        add_filter( 'body_class', array( $this, 'module_archive_body_class' ) );

        // Set up display on single course page
        add_action( 'sensei_single_main_content', array( $this, 'single_course_remove_lessons' ) );
        add_action( 'sensei_course_single_lessons', array( $this, 'single_course_modules' ) );

        // Set up display on single lesson page
        add_action( 'sensei_lesson_back_link', array( $this, 'back_to_module_link' ), 9, 1 );

        // Add 'Modules' columns to Analysis tables
        add_filter( 'sensei_analysis_overview_lessons_columns', array( $this, 'analysis_overview_column_title' ), 10, 1 );
        add_filter( 'sensei_analysis_overview_lessons_column_data', array( $this, 'analysis_overview_column_data' ), 10, 2 );
        add_filter( 'sensei_analysis_course_lesson_columns', array( $this, 'analysis_course_column_title' ), 10, 1 );
        add_filter( 'sensei_analysis_course_lesson_column_data', array( $this, 'analysis_course_column_data' ), 10, 3 );

        // Manage module taxonomy columns
        add_filter( 'manage_edit-' . $this->taxonomy . '_columns', array( $this, 'taxonomy_column_headings' ), 1, 1 );
        add_filter( 'manage_' . $this->taxonomy . '_custom_column', array( $this, 'taxonomy_column_content' ), 1, 3 );
        add_filter( 'sensei_module_lesson_list_title', array( $this, 'sensei_course_preview_titles' ), 10, 2 );

		// Register activation hook to refresh permalinks
		register_activation_hook( $this->file, array( $this, 'activation' ) );
	}

	/**
	 * Runs on plugin activation - refreshes permalinks
	 * @return void
	 */
	public function activation() {
		$this->register_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Register 'module' taxonomy
	 * @return void
	 */
	public function register_taxonomy() {

		$labels = array(
            'name' => __( 'Modules', 'sensei_modules' ),
            'singular_name' => __( 'Module', 'sensei_modules' ),
            'search_items' =>  __( 'Search Modules', 'sensei_modules' ),
            'all_items' => __( 'All Modules', 'sensei_modules' ),
            'parent_item' => __( 'Parent Module', 'sensei_modules' ),
            'parent_item_colon' => __( 'Parent Module:', 'sensei_modules' ),
            'edit_item' => __( 'Edit Module', 'sensei_modules' ),
            'update_item' => __( 'Update Module', 'sensei_modules' ),
            'add_new_item' => __( 'Add New Module', 'sensei_modules' ),
            'new_item_name' => __( 'New Module Name', 'sensei_modules' ),
            'menu_name' => __( 'Modules', 'sensei_modules' ),
        );

        $args = array(
            'public' => true,
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_ui' => true,
            'rewrite' => array( 'slug' => apply_filters( 'sensei_module_slug', 'modules' ) ),
            'labels' => $labels
        );

		register_taxonomy( $this->taxonomy, array( 'course', 'lesson' ), $args );
	}

	/**
	 * Manage taoxnomy meta boxes on lesson edit screen
	 * @return void
	 */
	public function lesson_metaboxes() {
		global $post;

		if( 'lesson' == $post->post_type ) {

			// Remove default taxonomy meta box from Lesson edit screen
			remove_meta_box( $this->taxonomy . 'div', 'lesson', 'side' );

			// Add custom meta box to limit module selection to one per lesson
			add_meta_box( $this->taxonomy . '_select', __( 'Lesson Module', 'sensei_modules' ), array( $this, 'lesson_module_metabox' ), 'lesson', 'side', 'default' );
		}
	}

	/**
	 * Build content for custom module meta box
	 * @param  object $post Current post object
	 * @return void
	 */
	public function lesson_module_metabox( $post ) {

		// Get lesson course
		$lesson_course = get_post_meta( $post->ID, '_lesson_course', true );

		$html = '';

		// Only show module selection if this lesson is part of a course
		if( $lesson_course && $lesson_course > 0 ) {

			// Get existing lesson module
			$lesson_module = 0;
			$lesson_module_list = wp_get_post_terms( $post->ID, $this->taxonomy );
			if( is_array( $lesson_module_list ) && count( $lesson_module_list ) > 0 ) {
				foreach( $lesson_module_list as $single_module ) {
					$lesson_module = $single_module->term_id;
					break;
				}
			}

			// Get the available modules for this lesson's course
			$modules = $this->get_course_modules( $lesson_course );

			// Build the HTML to output
			$html .= '<input type="hidden" name="' . esc_attr( 'woo_lesson_' . $this->taxonomy . '_nonce' ) . '" id="' . esc_attr( 'woo_lesson_' . $this->taxonomy . '_nonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename( $this->file ) ) ) . '" />';
			if( is_array( $modules ) && count( $modules ) > 0 ) {
				$html .= '<select id="lesson-module-options" name="lesson_module" class="widefat">' . "\n";
				$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
					foreach( $modules as $module ) {
						$html .= '<option value="' . esc_attr( absint( $module->term_id ) ) . '"' . selected( $module->term_id, $lesson_module, false ) . '>' . esc_html( $module->name ) . '</option>' . "\n";
					}
				$html .= '</select>' . "\n";

				$html .= '<script type="text/javascript">' . "\n";
				$html .= 'jQuery( \'#lesson-module-options\' ).chosen();' . "\n";
				$html .= '</script>' . "\n";
			} else {
				$course_url = admin_url( 'post.php?post=' . urlencode( $lesson_course ) . '&action=edit' );
				$html .= '<p>' . sprintf( __( 'No modules are available for this lesson yet. %1$sPlease add some to %3$sthe course%4$s.%2$s', 'sensei_modules' ), '<em>', '</em>', '<a href="' . esc_url( $course_url ) . '">', '</a>' ) . '</p>';
			} // End If Statement

		} else {
			$html .= '<p>' . sprintf( __( 'No modules are available for this lesson yet. %1$sPlease select a course first.%2$s', 'sensei_modules' ), '<em>', '</em>' ) . '</p>';
		} // End If Statement

		// Output the HTML
		echo $html;
	}

	/**
	 * Save module to lesson
	 * @param  integer $post_id ID of post
	 * @return mixed 			Post ID on permissions failure, boolean true on success
	 */
	public function save_lesson_module( $post_id ) {
		global $post;

		// Verify post type and nonce
		if ( ( get_post_type() != 'lesson' ) || ! wp_verify_nonce( $_POST[ 'woo_lesson_' . $this->taxonomy . '_nonce' ], plugin_basename( $this->file ) ) ) {
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

		// Cast module ID as an integer if selected, otherwise leave as empty string
		$module_id = $_POST['lesson_module'];
		if( $module_id != '' ) {
			$module_id = intval( $module_id );
		}

		// Assign lesson to selected module
		wp_set_object_terms( $post_id, $module_id, $this->taxonomy, false );

		// Set default order for lesson inside module
		if( ! get_post_meta( $post_id, '_order_module_' . $module_id, true ) ) {
			update_post_meta( $post_id, '_order_module_' . $module_id, 0 );
		}

		return true;
	}

	/**
	 * Display course field on new module screeen
	 * @param object $taxonomy Taxonomy object
	 * @return void
	 */
	public function add_module_fields( $taxonomy ) {
        ?>
        <div class="form-field">
            <label for="module_courses"><?php _e( 'Course(s)', 'sensei_modules' ); ?></label>
            <select id="module_courses" name="module_courses[]" class="ajax_chosen_select_courses" placeholder="<?php esc_attr_e( 'Search for courses...', 'sensei_modules' ); ?>" multiple="multiple"></select>
            <span class="description"><?php _e( 'Search for and select the courses that this module will belong to.', 'sensei_modules' ); ?></span>
            <script type="text/javascript">
	            jQuery('select.ajax_chosen_select_courses').ajaxChosen({
				    method: 		'GET',
				    url: 			'<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>',
				    dataType: 		'json',
				    afterTypeDelay: 100,
				    minTermLength: 	1,
				    data:		{
				    	action: 	'sensei_json_search_courses',
						security: 	'<?php echo esc_js( wp_create_nonce( "search-courses" ) ); ?>',
						default: 	''
				    }
				}, function (data) {

					var courses = {};

				    jQuery.each(data, function (i, val) {
				        courses[i] = val;
				    });

				    return courses;
				});
			</script>
        </div>
        <?php
    }

    /**
     * Dispay course field on module edit screen
     * @param  object $module Module term object
     * @return void
     */
    public function edit_module_fields( $module ) {

        $module_id = $module->term_id;

        // Get module's existing courses
        $args = array(
        	'post_type' => 'course',
        	'post_status' => array('publish', 'draft', 'future', 'private'),
        	'posts_per_page' => -1,
        	'tax_query' => array(
        		array(
        			'taxonomy' => $this->taxonomy,
        			'field' => 'id',
        			'terms' => $module_id
    			)
    		)
    	);
    	$courses = get_posts( $args );

    	// Add existing courses as selected options
    	$module_courses = '';
    	if( isset( $courses ) && is_array( $courses ) ) {
        	foreach( $courses as $course ) {
        		$module_courses .= '<option value="' . esc_attr( $course->ID ) . '" selected="selected">' . $course->post_title . '</option>';
        	}
        }

        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="module_courses"><?php _e( 'Course(s)', 'sensei_modules' ); ?></label></th>
            <td>
            	<select id="module_courses" name="module_courses[]" class="ajax_chosen_select_courses" placeholder="<?php esc_attr_e( 'Search for courses...', 'sensei_modules' ); ?>" multiple="multiple"><?php echo $module_courses; ?></select>
            	<span class="description"><?php _e( 'Search for and select the courses that this module will belong to.', 'sensei_modules' ); ?></span>
	            <script type="text/javascript">
		            jQuery('select.ajax_chosen_select_courses').ajaxChosen({
					    method: 		'GET',
					    url: 			'<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>',
					    dataType: 		'json',
					    afterTypeDelay: 100,
					    minTermLength: 	1,
					    data:		{
					    	action: 	'sensei_json_search_courses',
							security: 	'<?php echo esc_js( wp_create_nonce( "search-courses" ) ); ?>',
							default: 	''
					    }
					}, function (data) {

						var courses = {};

					    jQuery.each(data, function (i, val) {
					        courses[i] = val;
					    });

					    return courses;
					});
				</script>
            </td>
        </tr>
        <?php
    }

    /**
     * Save module course on add/edit
     * @param  integer $module_id ID of module
     * @return void
     */
    public function save_module_course( $module_id ) {

    	// Get module's existing courses
        $args = array(
        	'post_type' => 'course',
        	'post_status' => array('publish', 'draft', 'future', 'private'),
        	'posts_per_page' => -1,
        	'tax_query' => array(
        		array(
        			'taxonomy' => $this->taxonomy,
        			'field' => 'id',
        			'terms' => $module_id
    			)
    		)
    	);
    	$courses = get_posts( $args );

        // Remove module from existing courses
        if( isset( $courses ) && is_array( $courses ) ) {
        	foreach( $courses as $course ) {
        		wp_remove_object_terms( $course->ID, $module_id, $this->taxonomy );
        	}
        }

        // Add module to selected courses
        if( isset( $_POST['module_courses'] ) && is_array( $_POST['module_courses'] ) && count( $_POST['module_courses'] ) > 0 ) {
        	foreach( $_POST['module_courses'] as $k => $course_id ) {
        		wp_set_object_terms( $course_id, $module_id, $this->taxonomy, true );
        	}
        }
    }

    /**
     * Ajax function to search for courses matching term
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
		if ( empty( $term ) )
			die();

		// Set a default if none is given
		$default = isset( $_GET['default'] ) ? $_GET['default'] : __( 'No course', 'sensei_modules' );

		// Set up array of results
		$found_courses = array( '' => $default );

		// Fetch results
		$args = array(
			'post_type'   => 'course',
			'post_status' => array('publish', 'draft', 'future', 'private'),
			'posts_per_page' => -1,
			'orderby'	  => 'title',
			's'			  => $term
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

    /**
     * Remove default lesson display from courses containing modules
     * @return void
     */
    public function single_course_remove_lessons() {
    	global $post;

    	if( has_term( '', $this->taxonomy, $post->ID ) ) {
    		remove_action( 'sensei_course_single_lessons', 'course_single_lessons', 10 );
    	}
    }

    /**
     * display modules on single course pages
     * @return void
     */
    public function single_course_modules() {
    	global $post, $current_user, $woothemes_sensei;

    	$course_id = $post->ID;

    	$html = '';

    	if( has_term( '', $this->taxonomy, $course_id ) ) {

    		do_action( 'sensei_modules_page_before' );

    		// Get user data
    		get_currentuserinfo();

    		// Check if user is taking this course
    		$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => intval( $course_id ), 'user_id' => intval( $current_user->ID ), 'type' => 'sensei_course_start' ) );

    		// Get all modules
    		$modules = $this->get_course_modules( intval( $course_id ) );

    		$lessons_completed = 0;

    		// Start building HTML
    		$html .= '<section class="course-lessons">';

    			// Display course progress for users who are taking the course
	    		if ( is_user_logged_in() && $is_user_taking_course ) {

	    			$course_lessons = $woothemes_sensei->frontend->course->course_lessons( intval( $post->ID ) );
					$total_lessons = count( $course_lessons );

					$html .= '<span class="course-completion-rate">' . sprintf( __( 'Currently completed %1$s lesson(s) of %2$s in total', 'woothemes-sensei' ), '######', $total_lessons ) . '</span>';
					$html .= '<div class="meter+++++"><span style="width: @@@@@%">@@@@@%</span></div>';

				}

	    		$html .= '<header><h2>' . __( 'Modules', 'sensei_modules' ) . '</h2></header>';

				// Display each module
	    		foreach( $modules as $module ) {

	    			$module_url = esc_url( add_query_arg( 'course_id', $course_id, get_term_link( $module, $this->taxonomy ) ) );

	    			$html .= '<article class="post module">';

	    				$html .= '<header><h2><a href="' . esc_url( $module_url ) . '">' . $module->name . '</a></h2></header>';

		    			$html .= '<section class="entry">';

		    				$module_progress = false;
							if( is_user_logged_in() ) {
								global $current_user;
								wp_get_current_user();
								$module_progress = $this->get_user_module_progress( $module->term_id, $course_id, $current_user->ID );
							}

							if( $module_progress && $module_progress > 0 ) {
								$status = __( 'Completed', 'sensei_modules' );
								$class = 'completed';
								if( $module_progress < 100 ) {
									$status = __( 'In progress', 'sensei_modules' );
									$class = 'in-progress';
								}
								$html .= '<p class="status module-status ' . esc_attr( $class ) . '">' . $status . '</p>';
							}

							$description = $module->description;

							if( '' != $description ) {
								$html .= '<p class="module-description">' . $description . '</p>';
							}

			    			$args = array(
			    				'post_type' => 'lesson',
			    				'post_status' => 'publish',
			    				'posts_per_page' => -1,
			    				'meta_query' => array(
			    					array(
			    						'key' => '_lesson_course',
			    						'value' => intval( $course_id ),
			    						'compare' => '='
									)
								),
								'tax_query' => array(
									array(
										'taxonomy' => $this->taxonomy,
										'field' => 'id',
										'terms' => intval( $module->term_id )
									)
								),
								'orderby' => 'menu_order',
								'order' => 'ASC',
								'suppress_filters' => 0
							);

							if( version_compare( $woothemes_sensei->version, '1.6.0', '>=' ) ) {
								$args['meta_key'] = '_order_module_' . intval( $module->term_id );
								$args['orderby'] = 'meta_value_num date';
							}

							$lessons = get_posts( $args );

							if( count( $lessons ) > 0 ) {

								$html .= '<section class="module-lessons">';

									$html .= '<header><h3>' . __( 'Lessons', 'sensei_modules' ) . '</h3></header>';

									$html .= '<ul>';

										foreach( $lessons as $lesson ) {
											$status = '';
											$lesson_completed = $this->user_completed_lesson( $lesson, $current_user );
											$title = esc_attr( get_the_title( intval( $lesson->ID ) ) );

											if( $lesson_completed ) {
												// Increment completed lesson counter
												++$lessons_completed;
												$status = 'completed';
											}

											$html .= '<li class="' . $status . '"><a href="' . esc_url( get_permalink( intval( $lesson->ID ) ) ) . '" title="' . esc_attr( get_the_title( intval( $lesson->ID ) ) ) . '">' . apply_filters( 'sensei_module_lesson_list_title', $title, $lesson->ID ) . '</a></li>';

											// Build array of displayed lesson for exclusion later
											$displayed_lessons[] = $lesson->ID;
										}

									$html .= '</ul>';

								$html .= '</section>';

							}

						$html .= '</section>';

					$html .= '</article>';

	    		}

	    		// Display any lessons that have not already been displayed
	    		$args = array(
    				'post_type' => 'lesson',
    				'post_status' => 'publish',
    				'posts_per_page' => -1,
    				'meta_query' => array(
    					array(
    						'key' => '_lesson_course',
    						'value' => intval( $course_id ),
    						'compare' => '='
						)
					),
					'post__not_in' => $displayed_lessons,
					'orderby' => 'menu_order',
					'order' => 'ASC',
					'suppress_filters' => 0
				);

				if( version_compare( $woothemes_sensei->version, '1.6.0', '>=' ) ) {
					$args['meta_key'] = '_order_' . intval( $course_id );
					$args['orderby'] = 'meta_value_num date';
				}

				$lessons = get_posts( $args );

				if( 0 < count( $lessons ) ) {

		    		$html .= '<section class="course-lessons">';

		    			$html .= '<header><h2>' . __( 'Other Lessons', 'sensei_modules' ) . '</h2></header>';

		    			$lesson_count = 1;

		    			foreach( $lessons as $lesson ) {

		    				// Increment completed lessons counter and note if current lesson has been completed
		    				$single_lesson_complete = false;
				            if( $this->user_completed_lesson( $lesson, $current_user ) ) {
								++$lessons_completed;
								$single_lesson_complete = true;
							}

				    	    // Get Lesson data
				    	    $complexity_array = $woothemes_sensei->frontend->lesson->lesson_complexities();
				    	    $lesson_length = get_post_meta( $lesson->ID, '_lesson_length', true );
				    	    $lesson_complexity = get_post_meta( $lesson->ID, '_lesson_complexity', true );
				    	    if ( '' != $lesson_complexity ) { $lesson_complexity = $complexity_array[$lesson_complexity]; }
				    	    $user_info = get_userdata( absint( $lesson->post_author ) );
				    	    if ( '' != $lesson->post_excerpt ) { $lesson_excerpt = $lesson->post_excerpt; } else { $lesson_excerpt = $lesson->post_content; }
				    	    $title = esc_html( sprintf( __( '%s', 'woothemes-sensei' ), $lesson->post_title ) );

				    	    // Display lesson data
				    	    $html .= '<article class="' . esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $lesson->ID ) ) ) . '">';

				    			$html .= '<header>';

				    	    		$html .= '<h2><a href="' . esc_url( get_permalink( $lesson->ID ) ) . '" title="' . esc_attr( sprintf( __( 'Start %s', 'woothemes-sensei' ), $lesson->post_title ) ) . '">' . apply_filters( 'sensei_module_lesson_list_title', $title, $lesson->ID ) . '</a></h2>';

				    	    		$html .= '<p class="lesson-meta">';

				    	   		 		if ( '' != $lesson_length ) { $html .= '<span class="lesson-length">' . apply_filters( 'sensei_length_text', __( 'Length: ', 'woothemes-sensei' ) ) . $lesson_length . __( ' minutes', 'woothemes-sensei' ) . '</span>'; }
				    	   		 		if ( isset( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) && ( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) ) {
				    	   		 			$html .= '<span class="lesson-author">' . apply_filters( 'sensei_author_text', __( 'Author: ', 'woothemes-sensei' ) ) . '<a href="' . get_author_posts_url( absint( $lesson->post_author ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
				    	   		 		}
				    	   		 		if ( '' != $lesson_complexity ) { $html .= '<span class="lesson-complexity">' . apply_filters( 'sensei_complexity_text', __( 'Complexity: ', 'woothemes-sensei' ) ) . $lesson_complexity .'</span>'; }
				    	   		 	    if ( $single_lesson_complete ) {
				                            $html .= '<span class="lesson-status complete">' . apply_filters( 'sensei_complete_text', __( 'Complete', 'woothemes-sensei' ) ) .'</span>';
				                        } else {
				                            // Get Lesson Status
				                            $lesson_quiz_id = $woothemes_sensei->frontend->lesson->lesson_quizzes( $lesson->ID );
				                            if ( $lesson_quiz_id )  {
				                                // Check if user has started the lesson and has saved answers
				                                $user_lesson_start =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => intval( $lesson->ID ), 'user_id' => intval( $current_user->ID ), 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );
				                                if ( '' != $user_lesson_start ) {
				                                    $html .= '<span class="lesson-status in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) .'</span>';
				                                }
				                            } // End If Statement
				                        }

				    	   		 	$html .= '</p>';

				    			$html .= '</header>';

				    			$html .=  $woothemes_sensei->post_types->lesson->lesson_image( $lesson->ID );

				    			$html .= '<section class="entry">';

				    	   		 	$html .= '<p class="lesson-excerpt">';

				    	   		 		$html .= '<span>' . $lesson_excerpt . '</span>';

				    	   		 	$html .= '</p>';

				    	   		$html .= '</section>';

				    	    $html .= '</article>';

				    	    $lesson_count++;
		    			}

		    		$html .= '</section>';
		    	}

    		$html .= '</section>';

    		// Replace place holders in course progress widget
    		if ( is_user_logged_in() && $is_user_taking_course ) {

	    		// Add dynamic data to the output
	    		$html = str_replace( '######', $lessons_completed, $html );
	    		$progress_percentage = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) );

	    		$html = str_replace( '@@@@@', $progress_percentage, $html );
	    		if ( 50 < $progress_percentage ) { $class = ' green'; } elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) { $class = ' orange'; } else { $class = ' red'; }

	    		$html = str_replace( '+++++', $class, $html );
	    	}
    	}

    	// Display output
    	echo $html;

    	if( has_term( '', $this->taxonomy, $course_id ) ) {
    		do_action( 'sensei_modules_page_after' );
    	}
    }

    public function sensei_course_preview_titles( $title, $lesson_id ) {
    	global $post, $current_user, $woothemes_sensei;

    	$course_id = $post->ID;
    	$title_text = '';
    	if ( method_exists( 'WooThemes_Sensei_Frontend', 'sensei_lesson_preview_title_text' ) ) {
    		$title_text = $woothemes_sensei->frontend->sensei_lesson_preview_title_text( $course_id );
    		// Remove brackets for display here
    		$title_text = str_replace( '(', '', $title_text );
    		$title_text = str_replace( ')', '', $title_text );
	    	$title_text = '<span class="preview-label">' . $title_text . '</span>';
	    }
 
    	$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
		if( method_exists( 'WooThemes_Sensei_Utils', 'is_preview_lesson' ) && WooThemes_Sensei_Utils::is_preview_lesson( $lesson_id ) && !$is_user_taking_course ) {
			$title .= ' ' . $title_text;
		}
    	return $title;
    }

    /**
     * Check if a user has completed a lesson
     * @param  object  $lesson Lesson post object
     * @param  object  $user   User object
     * @return boolean         True if the user has completed the lesson
     */
    private function user_completed_lesson( $lesson, $user ) {
    	global $woothemes_sensei;

    	if ( is_user_logged_in() ) {
	    	// Check if Lesson is complete
	    	$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => intval( $lesson->ID ), 'user_id' => intval( $user->ID ), 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
			if ( '' != $user_lesson_end ) {
				//Check for Passed or Completed Setting
                $course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
                if ( 'passed' == $course_completion ) {
                    // If Setting is Passed -> Check for Quiz Grades
                    // Get Quiz ID
                    $lesson_quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson->ID );
                    if ( $lesson_quiz_id ) {
                        // Quiz Grade
                        $lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => intval( $lesson_quiz_id ), 'user_id' => intval( $user->ID ), 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) ); // Check for wrapper
                        // Check if Grade is bigger than pass percentage
                        $lesson_prerequisite = abs( round( doubleval( get_post_meta( intval( $lesson_quiz_id ), '_quiz_passmark', true ) ), 2 ) );
                        if ( $lesson_prerequisite <= intval( $lesson_grade ) ) {
                            return true;
                        }
                    } // End If Statement
                } else {
                    return true;
                }
			} // End If Statement
		}

		return false;
    }

    /**
	 * Display 'back to module' link on single lesson pages
	 * @param  integer $course_id ID of course
	 * @return void
	 */
	public function back_to_module_link( $course_id = 0 ) {
		global $post;
		if( has_term( '', $this->taxonomy, $post->ID ) ) {
			$module = $this->get_lesson_module( $post->ID );
			?><section class="lesson-course">
	    		<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( $module->url ); ?>" title="<?php echo esc_attr( apply_filters( 'sensei_back_to_module_text', __( 'Back to the module', 'sensei_modules' ) ) ); ?>"><?php echo $module->name; ?></a>
	    	</section><?php
	    }
	}

	/**
	 * Display 'back to course' link on module pages
	 * @param  integer $course_id ID of course
	 * @return void
	 */
	public function module_back_to_course_link() {
		if ( is_tax( $this->taxonomy ) ) {
			if ( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
    			$course_id = intval( $_GET['course_id'] );
    			?><section class="lesson-course">
		    		<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" title="<?php echo esc_attr( apply_filters( 'sensei_back_to_course_text', __( 'Back to the course', 'woothemes-sensei' ) ) ); ?>"><?php echo get_the_title( $course_id ); ?></a>
		    	</section><?php
    		}
		}
	}

	/**
     * Set lesson archive template to display on module taxonomy archive page
     * @param  string $template Default template
     * @return string           Modified template
     */
    public function module_archive_template( $template ) {
		global $woothemes_sensei, $post, $wp_query;

		$find = array( 'woothemes-sensei.php' );
		$file = '';

		if ( is_tax( $this->taxonomy ) ) {
		    $file 	= 'archive-lesson.php';
		    $find[] = $file;
		    $find[] = $woothemes_sensei->template_url . $file;
		}

		// Load the template file
		if ( $file ) {
			$template = locate_template( $find );
			if ( ! $template ) $template = $woothemes_sensei->plugin_path() . '/templates/' . $file;
		} // End If Statement

		return $template;
	}

    /**
     * Modify module taxonomy archive query
     * @param  object $query The query object passed by reference
     * @return void
     */
    public function module_archive_filter( $query ) {
    	if( is_tax( $this->taxonomy ) && $query->is_main_query() ) {
    		global $woothemes_sensei;

    		// Limit to lessons only
    		$query->set( 'post_type', 'lesson' );

    		// Set order of lessons
    		if( version_compare( $woothemes_sensei->version, '1.6.0', '>=' ) ) {
    			$module_id = $query->queried_object_id;
				$query->set( 'meta_key', '_order_module_' . $module_id );
				$query->set( 'orderby', 'meta_value_num date' );
			} else {
				$query->set( 'orderby', 'menu_order' );
			}
    		$query->set( 'order', 'ASC' );

    		// Limit to specific course if specified
    		if ( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
    			$course_id = intval( $_GET['course_id'] );
    			$meta_query[] = array(
    				'key' => '_lesson_course',
    				'value' => intval( $course_id )
				);
    			$query->set( 'meta_query', $meta_query );
    		}

    	}
    }

    /**
	 * Modify archive page title
	 * @param  string $title Default title
	 * @return string        Modified title
	 */
	public function module_archive_title( $title ) {
		if( is_tax( $this->taxonomy ) ) {
			$title = apply_filters( 'sensei_module_archive_title', get_queried_object()->name );
		}
		return $title;
	}

	/**
	 * Display module description on taxonomy archive page
	 * @return void
	 */
	public function module_archive_description() {
		if( is_tax( $this->taxonomy ) ) {

			$module = get_queried_object();

			$module_progress = false;
			if( is_user_logged_in() && isset( $_GET['course_id'] ) && intval( $_GET['course_id'] ) > 0 ) {
				global $current_user;
				wp_get_current_user();
				$module_progress = $this->get_user_module_progress( $module->term_id, $_GET['course_id'], $current_user->ID );
			}

			if( $module_progress && $module_progress > 0 ) {
				$status = __( 'Completed', 'sensei_modules' );
				$class = 'completed';
				if( $module_progress < 100 ) {
					$status = __( 'In progress', 'sensei_modules' );
					$class = 'in-progress';
				}
				echo '<p class="status ' . esc_attr( $class ) . '">' . $status . '</p>';
			}

			echo '<p class="archive-description module-description">' . apply_filters( 'sensei_module_archive_description', nl2br( $module->description ), $module->term_id ) . '</p>';
		}
	}

	public function module_archive_body_class( $classes ) {
		if( is_tax( $this->taxonomy ) ) {
			$classes[] = 'module-archive';
		}
		return $classes;
	}

	/**
	 * Display module navigation links on module taxonomy archive page
	 * @return void
	 */
	public function module_navigation_links() {
		if( is_tax( $this->taxonomy ) && isset( $_GET['course_id'] ) ) {

			$queried_module = get_queried_object();
			$course_modules = $this->get_course_modules( $_GET['course_id'] );

			$prev_module = false;
			$next_module = false;
			$on_current = false;
			foreach( $course_modules as $module ) {
				$this_module = $module;
				if( $on_current ) {
					$next_module = $this_module;
					break;
				}
				if( $this_module == $queried_module ) {
					$on_current = true;
				} else {
					$prev_module = $module;
				}
			}

			?>
			<div id="post-entries" class="post-entries module-navigation fix">
				<?php if( $next_module ) {
					$module_link = add_query_arg( 'course_id', intval( $_GET['course_id'] ), get_term_link( $next_module, $this->taxonomy ) );
					?>
					<div class="nav-next fr"><a href="<?php echo esc_url( $module_link ); ?>" title="<?php esc_attr_e( 'Next module', 'sensei_modules' ); ?>"><?php echo $next_module->name; ?> <span class="meta-nav"></span></a></div>
				<?php } ?>
				<?php if( $prev_module ) {
					$module_link = add_query_arg( 'course_id', intval( $_GET['course_id'] ), get_term_link( $prev_module, $this->taxonomy ) );
					?>
					<div class="nav-prev fl"><a href="<?php echo esc_url( $module_link ); ?>" title="<?php _e( 'Previous module', 'sensei_modules' ); ?>"><span class="meta-nav"></span> <?php echo $prev_module->name; ?></a></div>
				<?php } ?>
			</div>
			<?php
		}
	}

	/**
	 * Save lesson's module progess for a specific user
	 * @param  integer $user_id   ID of user
	 * @param  integer $lesson_id ID of lesson
	 * @return void
	 */
	public function save_lesson_module_progress( $user_id = 0, $lesson_id = 0 ) {
		$module = $this->get_lesson_module( $lesson_id );
		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		if( $module && $course_id ) {
			$this->save_user_module_progress( intval( $module->term_id ), intval( $course_id ), intval( $user_id ) );
		}
	}

	/**
	 * Save progress of module for user
	 * @return void
	 */
	public function save_module_progress() {
		if( is_tax( $this->taxonomy ) && is_user_logged_in() && isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			global $current_user;
			wp_get_current_user();
			$user_id = $current_user->ID;

			$module = get_queried_object();

			$this->save_user_module_progress( intval( $module->term_id ), intval( $_GET['course_id'] ), intval( $user_id ) );
		}
	}

	/**
	 * Save module progess for user
	 * @param  integer $module_id ID of module
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 * @return void
	 */
	public function save_user_module_progress( $module_id = 0, $course_id = 0, $user_id = 0 ) {
		$module_progress = $this->calculate_user_module_progress( $user_id, $module_id, $course_id );
		update_user_meta( intval( $user_id ), '_module_progress_' . intval( $course_id ) . '_' . intval( $module_id ), intval( $module_progress ) );

		do_action( 'sensei_module_save_user_progress', $course_id, $module_id, $user_id, $module_progress );
	}

	/**
	 * Get module progress for a user
	 * @param  integer $module_id ID of module
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 * @return mixed              Module progress percentage on success, false on failure
	 */
	public function get_user_module_progress( $module_id = 0, $course_id = 0, $user_id = 0 ) {
		$module_progress = get_user_meta( intval( $user_id ), '_module_progress_' . intval( $course_id ) . '_' . intval( $module_id ), true );
		if( $module_progress ) {
			return (float) $module_progress;
		}
		return false;
	}

	/**
	 * Calculate module progess for user
	 * @param  integer $user_id   ID of user
	 * @param  integer $module_id ID of module
	 * @param  integer $course_id ID of course
	 * @return integer            Module progress percentage
	 */
	public function calculate_user_module_progress( $user_id = 0, $module_id = 0, $course_id = 0 ) {

		$args = array(
        	'post_type' => 'lesson',
        	'post_status' => 'publish',
        	'posts_per_page' => -1,
        	'tax_query' => array(
        		array(
        			'taxonomy' => $this->taxonomy,
        			'field' => 'id',
        			'terms' => $module_id
    			)
    		),
    		'meta_query' => array(
    			array(
    				'key' => '_lesson_course',
    				'value' => $course_id
				)
			)
    	);
    	$lessons = get_posts( $args );

    	if ( is_wp_error( $lessons ) || 0 >= count( $lessons ) ) return 0;

    	$completed = false;
    	$lesson_count = 0;
    	$completed_count = 0;
    	foreach( $lessons as $lesson ) {
    		$completed = WooThemes_Sensei_Utils::user_completed_lesson( $lesson->ID, $user_id );
    		++$lesson_count;
    		if( $completed ) {
    			++$completed_count;
    		}
    	}
    	$module_progress = ( $completed_count / $lesson_count ) * 100;

    	return (float) $module_progress;
	}

	/**
	 * Register admin screen for ordering modules
	 * @return void
	 */
	public function register_module_order_screen() {
		// Regsiter new admin page for module ordering
		$hook = add_submenu_page( 'edit.php?post_type=lesson', __( 'Order Modules', 'sensei_modules' ), __( 'Order Modules', 'sensei_modules' ), 'edit_lessons', $this->order_page_slug, array( $this, 'module_order_screen' ) );

		add_action( 'admin_print_scripts-' . $hook, array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_print_styles-' . $hook, array( $this, 'admin_enqueue_styles' ) );
	}

	/**
	 * Dsplay Module Order screen
	 * @return void
	 */
	public function module_order_screen() {
		?><div id="<?php echo esc_attr( $this->order_page_slug ); ?>" class="wrap <?php echo esc_attr( $this->order_page_slug ); ?>">
		<h2><?php _e( 'Order Modules', 'sensei_modules' ); ?></h2><?php

		$html = '';

		if( isset( $_POST['module-order'] ) && 0 < strlen( $_POST['module-order'] ) ) {
			$ordered = $this->save_course_module_order( esc_attr( $_POST['module-order'] ), esc_attr( $_POST['course_id'] ) );

			if( $ordered ) {
				$html .= '<div class="updated fade">' . "\n";
				$html .= '<p>' . __( 'The module order has been saved for this course.', 'sensei_modules' ) . '</p>' . "\n";
				$html .= '</div>' . "\n";
			}
		}

		$args = array(
			'post_type' => 'course',
			'post_status' => array('publish', 'draft', 'future', 'private'),
			'posts_per_page' => -1
		);
		$courses = get_posts( $args );

		$html .= '<form action="' . admin_url( 'edit.php' ) . '" method="get">' . "\n";
		$html .= '<input type="hidden" name="post_type" value="lesson" />' . "\n";
		$html .= '<input type="hidden" name="page" value="' . esc_attr( $this->order_page_slug ) . '" />' . "\n";
		$html .= '<select id="module-order-course" name="course_id">' . "\n";
		$html .= '<option value="">Select a course</option>' . "\n";

		foreach( $courses as $course ) {
			if( has_term( '', $this->taxonomy, $course->ID ) ) {
				$course_id = '';
				if( isset( $_GET['course_id'] ) ) {
					$course_id = intval( $_GET['course_id'] );
				}
				$html .= '<option value="' . esc_attr( intval( $course->ID ) ) . '" ' . selected( $course->ID, $course_id, false ) .'>' . get_the_title( $course->ID ) . '</option>' . "\n";
			}
		}

		$html .= '</select>' . "\n";
		$html .= '<input type="submit" class="button-primary module-order-select-course-submit" value="' . __( 'Select', 'sensei_modules' ) . '" />' . "\n";
		$html .= '</form>' . "\n";

		$html .= '<script type="text/javascript">' . "\n";
		$html .= 'jQuery( \'#module-order-course\' ).chosen();' . "\n";
		$html .= '</script>' . "\n";

		if( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			if( $course_id > 0 ) {
				$modules = $this->get_course_modules( $course_id );
				if( $modules ) {

					$order = $this->get_course_module_order( $course_id );
					if( $order ) {
						$order_string = implode( ',', $order );
					}

					$html .= '<form id="editgrouping" method="post" action="" class="validate">' . "\n";
					$html .= '<ul class="sortable-module-list">' . "\n";
					$count = 0;
					foreach ( $modules as $module ) {
						$count++;
						$class = $this->taxonomy;
						if ( $count == 1 ) { $class .= ' first'; }
						if ( $count == count( $module ) ) { $class .= ' last'; }
						if ( $count % 2 != 0 ) {
							$class .= ' alternate';
						}
						$html .= '<li class="' . esc_attr( $class ) . '"><span rel="' . esc_attr( $module->term_id ) . '" style="width: 100%;"> ' . $module->name . '</span></li>' . "\n";
					}
					$html .= '</ul>' . "\n";

					$html .= '<input type="hidden" name="module-order" value="' . $order_string . '" />' . "\n";
					$html .= '<input type="hidden" name="course_id" value="' . $course_id . '" />' . "\n";
					$html .= '<input type="submit" class="button-primary" value="' . __( 'Save module order', 'sensei_modules' ) . '" />' . "\n";
					$html .= '<a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" class="button-secondary">' . __( 'Edit course', 'sensei_modules' ) . '</a>' . "\n";
				}
			}
		}

		echo $html;

		?></div><?php
	}

	/**
	 * Add 'Module order' column to courses list table
	 * @param  array  $columns Existing columns
	 * @return array           Modifed columns
	 */
	public function course_columns( $columns = array() ) {
		$columns['module_order'] = __( 'Module order', 'sensei_modules' );
		return $columns;
	}

	/**
	 * Load content in 'Module order' column
	 * @param  string  $column    Current column name
	 * @param  integer $course_id ID of course
	 * @return void
	 */
	public function course_column_content( $column = '', $course_id = 0 ) {
		if( $column == 'module_order' ) {
			if( has_term( '', $this->taxonomy, $course_id ) ) {
				echo '<a class="button-secondary" href="' . admin_url( 'edit.php?post_type=lesson&page=module-order&course_id=' . urlencode( intval( $course_id ) ) ) . '">' . __( 'Order modules', 'sensei_modules' ) . '</a>';
			}
		}
	}

	/**
	 * Save module order for course
	 * @param  string  $order_string Comma-separated string of module IDs
	 * @param  integer $course_id    ID of course
	 * @return boolean				 True on success, false on failure
	 */
	private function save_course_module_order( $order_string = '', $course_id = 0 ) {
		if( $order_string && $course_id ) {
			$order = explode( ',', $order_string );
			update_post_meta( intval( $course_id ), '_module_order', $order );
			return true;
		}
		return false;
	}

	/**
	 * Get module order for course
	 * @param  integer $course_id ID of course
	 * @return mixed              Module order on success, false if no module order has been saved
	 */
	public function get_course_module_order( $course_id = 0 ) {
		if( $course_id ) {
			$order = get_post_meta( intval( $course_id ), '_module_order', true );
			return $order;
		}
		return false;
	}

    /**
	 * Modify module taxonomy columns
	 * @param  array $columns Default columns
	 * @return array          Modified columns
	 */
	public function taxonomy_column_headings( $columns ) {

        unset( $columns['posts'] );

        $columns['lessons'] = __( 'Lessons' , 'sensei_modules' );

        return $columns;
    }

    /**
     * Manage content in custom module taxonomy columns
     * @param  string  $column_data Default data for column
     * @param  string  $column_name Name of current column
     * @param  integer $term_id     ID of current term
     * @return string               Modified column data
     */
	public function taxonomy_column_content( $column_data, $column_name, $term_id ) {

		$args = array(
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => $this->taxonomy,
					'field' => 'id',
					'terms' => intval( $term_id )
				)
			)
		);

		$module = get_term( $term_id, $this->taxonomy );

		switch ( $column_name ) {

            case 'lessons':
            	$args['post_type'] = 'lesson';
            	$lessons = get_posts( $args );
            	$total_lessons = count( $lessons );
            	$column_data = '<a href="' . admin_url( 'edit.php?module=' . urlencode( $module->slug ) . '&post_type=lesson' ) . '">' . intval( $total_lessons ) . '</a>';
            break;
        }

        return $column_data;
	}

    /**
     * Add 'Module' columns to Analysis Lesson Overview table
     * @param  array $columns Default columns
     * @return array          Modified columns
     */
    public function analysis_overview_column_title( $columns ) {
    	$new_columns = array();
    	if( is_array( $columns ) && 0 < count( $columns ) ) {
    		foreach( $columns as $column => $title ) {
    			$new_columns[ $column ] = $title;
    			if( $column == 'lesson_course' ) {
    				$new_columns['lesson_module'] = __( 'Module', 'sensei_modules' );
    			}
    		}
    	}

    	if( 0 < count( $new_columns ) ) {
    		return $new_columns;
    	}

    	return $columns;
    }

    /**
     * Data for 'Module' column Analysis Lesson Overview table
     * @param  array   $columns   Table column data
     * @param  integer $lesson_id Lesson ID
     * @return array              Updated column data
     */
    public function analysis_overview_column_data( $columns, $lesson_id ) {

    	$lesson_module = '';
		$lesson_module_list = wp_get_post_terms( $lesson_id, $this->taxonomy );
		if( is_array( $lesson_module_list ) && count( $lesson_module_list ) > 0 ) {
			foreach( $lesson_module_list as $single_module ) {
				$lesson_module = '<a href="' . esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=' . urlencode( $this->taxonomy ) . '&tag_ID=' . urlencode( $single_module->term_id ) ) ) . '">' . $single_module->name . '</a>';
				break;
			}
		}

		$columns['lesson_module'] = $lesson_module;

    	return $columns;
    }

    /**
     * Add 'Module' columns to Analysis Course table
     * @param  array $columns Default columns
     * @return array          Modified columns
     */
    public function analysis_course_column_title( $columns ) {
		$columns['lesson_module'] = __( 'Module', 'sensei_modules' );
		return $columns;
    }

    /**
     * Data for 'Module' column in Analysis Course table
     * @param  array   $columns   Table column data
     * @param  integer $lesson_id Lesson ID
     * @param  integer $user_id   User ID
     * @return array              Updated columns data
     */
    public function analysis_course_column_data( $columns, $lesson_id, $user_id ) {
    	$lesson_module = '';
		$lesson_module_list = wp_get_post_terms( $lesson_id, $this->taxonomy );
		if( is_array( $lesson_module_list ) && count( $lesson_module_list ) > 0 ) {
			foreach( $lesson_module_list as $single_module ) {
				$lesson_module = '<a href="' . esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=' . urlencode( $this->taxonomy ) . '&tag_ID=' . urlencode( $single_module->term_id ) ) ) . '">' . $single_module->name . '</a>';
				break;
			}
		}

		$columns['lesson_module'] = $lesson_module;

    	return $columns;
    }

    /**
	 * Get module for lesson
	 * @param  integer $lesson_id ID of lesson
	 * @return object             Module taxonomy term object
	 */
	public function get_lesson_module( $lesson_id = 0 ) {
		$lesson_id = intval( $lesson_id );
		if( $lesson_id > 0 ) {
			$modules = wp_get_post_terms( $lesson_id, $this->taxonomy );
			foreach( $modules as $module ) {
				break;
			}
			if( isset( $module ) && is_object( $module ) && ! is_wp_error( $module ) ) {
				$module->url = get_term_link( $module, $this->taxonomy );
				$course_lesson = intval( get_post_meta( intval( $lesson_id ), '_lesson_course', true ) );
				if( isset( $course_lesson ) && 0 < $course_lesson ) {
					$module->url = esc_url( add_query_arg( 'course_id', intval( $course_lesson ), $module->url ) );
				}
				return $module;
			}
		}
		return false;
	}

	/**
	 * Get ordered array of all modules in course
	 * @param  integer $course_id ID of course
	 * @return array              Ordered array of module taxonomy term objects
	 */
	public function get_course_modules( $course_id = 0 ) {
		$course_id = intval( $course_id );
		if( 0 < $course_id ) {

			// Get modules for course
			$modules = wp_get_post_terms( $course_id, $this->taxonomy );

			// Get custom module order for course
			$order = $this->get_course_module_order( $course_id );

			// Sort by custom order if custom order exists
			if( $order ) {
				$ordered_modules = array();
				$unordered_modules = array();
				foreach( $modules as $module ) {
					$order_key = array_search( $module->term_id, $order );
					if( $order_key !== false ) {
						$ordered_modules[ $order_key ] = $module;
					} else {
						$unordered_modules[] = $module;
					}
				}

				// Order modules correctly
				ksort( $ordered_modules );

				// Append modules that have not yet been ordered
				if( count( $unordered_modules ) > 0 ) {
					$ordered_modules = array_merge( $ordered_modules, $unordered_modules );
				}

			} else {
				$ordered_modules = $modules;
			}

			return $ordered_modules;
		}
		return false;
	}

	/**
	 * Load frontend CSS
	 * @return void
	 */
	public function enqueue_styles() {
		global $woothemes_sensei;

		wp_register_style( $this->taxonomy . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', '1.0.0' );
		wp_enqueue_style( $this->taxonomy . '-frontend' );
	}

	/**
	 * Load admin Javascript
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		global $woothemes_sensei;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( $this->taxonomy . '-sortable', esc_url( $this->assets_url ) . 'js/admin' . $suffix . '.js', array( 'jquery', 'jquery-ui-sortable' ), '1.0.0', true );
		wp_enqueue_script( $this->taxonomy . '-sortable' );

		wp_register_script( 'sensei-chosen', esc_url( $woothemes_sensei->plugin_url ) . 'assets/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.3.0' );
		wp_enqueue_script( 'sensei-chosen' );
	}

	/**
	 * Load admin CSS
	 * @return void
	 */
	public function admin_enqueue_styles() {
		global $woothemes_sensei;

		wp_register_style( $this->taxonomy . '-sortable', esc_url( $this->assets_url ) . 'css/admin.css' );
		wp_enqueue_style( $this->taxonomy . '-sortable' );

		wp_register_style( $woothemes_sensei->token . '-chosen', esc_url( $woothemes_sensei->plugin_url ) . 'assets/chosen/chosen.css', '', '1.3.0', 'screen' );
		wp_enqueue_style( $woothemes_sensei->token . '-chosen' );
	}

	/**
	 * Load plugin localisation
	 * @return void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'sensei_modules' , false , dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Load plugin textdomain
	 * @return void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'sensei_modules';

	    $locale = apply_filters( 'plugin_locale' , get_locale() , $domain );

	    load_textdomain( $domain , WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain , FALSE , dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

}