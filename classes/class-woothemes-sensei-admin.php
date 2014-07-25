<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Administration Class
 *
 * All functionality pertaining to the administration sections of Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Administration
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - comments_admin_filter()
 * - install_page_output()
 * - create_page()
 * - create_pages()
 * - admin_styles_global()
 * - admin_install_notice()
 * - admin_notice_styles()
 *
 */
class WooThemes_Sensei_Admin {

	public $token;

	/**
	 * Constructor.
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct () {

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_global' ) );
		add_action( 'admin_print_styles', array( $this, 'admin_notices_styles' ) );
		add_action( 'settings_before_form', array( $this, 'install_pages_output' ) );
		add_filter( 'comments_clauses', array( $this, 'comments_admin_filter' ), 10, 1 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		add_action( 'menu_order', array( $this, 'admin_menu_order' ) );
		add_action( 'admin_head', array( $this, 'admin_menu_highlight' ) );
		add_action( 'admin_init', array( $this, 'page_redirect' ) );
		add_action( 'admin_init', array( $this, 'sensei_add_custom_menu_items' ) );

		// Duplicate lesson & courses
		add_filter( 'post_row_actions', array( $this, 'duplicate_action_link' ), 10, 2 );
		add_action( 'admin_action_duplicate_lesson', array( $this, 'duplicate_lesson_action' ) );
		add_action( 'admin_action_duplicate_course', array( $this, 'duplicate_course_action' ) );
		add_action( 'admin_action_duplicate_course_with_lessons', array( $this, 'duplicate_course_with_lessons_action' ) );

		// Handle lessons list table filtering
		add_action( 'restrict_manage_posts', array( $this, 'lesson_filter_options' ) );
		add_filter( 'request', array( $this, 'lesson_filter_actions' ) );

		// Add Sensei items to 'at a glance' widget
		add_filter( 'dashboard_glance_items', array( $this, 'glance_items' ), 10, 1 );

		// Handle course and lesson deletions
		add_action( 'trash_course', array( $this, 'delete_content' ), 10, 2 );
		add_action( 'trash_lesson', array( $this, 'delete_content' ), 10, 2 );

		// Delete user activity when user is deleted
		add_action( 'deleted_user', array( $this, 'delete_user_activity' ), 10, 1 );

		// Add notices to WP dashboard
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

	} // End __construct()

	/**
	 * Add items to admin menu
	 * @since  1.4.0
	 * @return void
	 */
	public function admin_menu() {
		global $woothemes_sensei, $menu;
		$menu_cap = '';
		if( current_user_can( 'manage_sensei' ) ) {
			$menu_cap = 'manage_sensei';
		} else {
			if( current_user_can( 'manage_sensei_grades' ) ) {
				$menu_cap = 'manage_sensei_grades';
			}
		}

		if( $menu_cap ) {
			$menu[] = array( '', 'read', 'separator-sensei', '', 'wp-menu-separator sensei' );
			$main_page = add_menu_page( __( 'Sensei', 'woothemes-sensei' ), __( 'Sensei', 'woothemes-sensei' ), $menu_cap, 'sensei' , array( $woothemes_sensei->analysis, 'analysis_page' ) , '', '50' );
		}

		add_submenu_page( 'edit.php?post_type=lesson', __( 'Order Courses', 'woothemes-sensei' ), __( 'Order Courses', 'woothemes-sensei' ), 'manage_sensei', 'course-order', array( $this, 'course_order_screen' ) );
		add_submenu_page( 'edit.php?post_type=lesson', __( 'Order Lessons', 'woothemes-sensei' ), __( 'Order Lessons', 'woothemes-sensei' ), 'manage_sensei', 'lesson-order', array( $this, 'lesson_order_screen' ) );
	}

	/**
	 * [admin_menu_order description]
	 * @since  1.4.0
	 * @param  array $menu_order Existing menu order
	 * @return array 			 Modified menu order for Sensei
	 */
	public function admin_menu_order( $menu_order ) {

		// Initialize our custom order array
		$sensei_menu_order = array();

		// Get the index of our custom separator
		$sensei_separator = array_search( 'separator-sensei', $menu_order );

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $index => $item ) :

			if ( ( ( 'sensei' ) == $item ) ) :
				$sensei_menu_order[] = 'separator-sensei';
				$sensei_menu_order[] = $item;
				unset( $menu_order[$sensei_separator] );
			elseif ( !in_array( $item, array( 'separator-sensei' ) ) ) :
				$sensei_menu_order[] = $item;
			endif;

		endforeach;

		// Return order
		return $sensei_menu_order;
	}

	/**
	 * Handle highlighting of admin menu items
	 * @since 1.4.0
	 * @return void
	 */
	public function admin_menu_highlight() {
		global $menu, $submenu, $parent_file, $submenu_file, $self, $post_type, $taxonomy;

		$screen = get_current_screen();

		if ( $screen->base == 'post' && $post_type == 'course' ) {
			$submenu_file = 'edit.php?post_type=course';
			$parent_file  = 'edit.php?post_type=lesson';
		} elseif ( $screen->base == 'edit-tags' && $taxonomy == 'course-category' ) {
			$submenu_file = 'edit-tags.php?taxonomy=course-category&post_type=course';
			$parent_file  = 'edit.php?post_type=lesson';
		} elseif ( in_array( $screen->id, array( 'sensei_message', 'edit-sensei_message' ) ) ) {
			$submenu_file = 'edit.php?post_type=sensei_message';
			$parent_file  = 'sensei';
		}
	}

	/**
	 * Redirect Sensei menu item to Analysis page
	 * @since  1.4.0
	 * @return void
	 */
	public function page_redirect() {
		if( isset( $_GET['page'] ) && $_GET['page'] == 'sensei' ) {
			wp_safe_redirect( 'admin.php?page=sensei_analysis' );
			exit;
		}
	}

	/**
	 * comments_admin_filter function.
	 *
	 * Filters the backend commenting system to not include the sensei prefixed comments
	 *
	 * @access public
	 * @param mixed $pieces
	 * @return void
	 */
	function comments_admin_filter( $pieces ) {

		// Filter Admin Comments Area to not display Sensei's use of commenting system
		if( is_admin() && !( isset($_GET['page']) && 'sensei_analysis' == $_GET['page'] ) ) {
			$pieces['where'] .= " AND comment_type NOT LIKE 'sensei_%' ";
		} // End If Statement

		return $pieces;

	} // End comments_admin_filter()


	/**
	 * install_pages_output function.
	 *
	 * Handles installation of the 2 pages needs for courses and my courses
	 *
	 * @access public
	 * @return void
	 */
	function install_pages_output() {
		global $woothemes_sensei;

		// Install/page installer
	    $install_complete = false;

	    // Add pages button
	    if (isset($_GET['install_sensei_pages']) && $_GET['install_sensei_pages']) {

			$this->create_pages();
	    	update_option('skip_install_sensei_pages', 1);
	    	$install_complete = true;

		// Skip button
	    } elseif (isset($_GET['skip_install_sensei_pages']) && $_GET['skip_install_sensei_pages']) {

	    	update_option('skip_install_sensei_pages', 1);
	    	$install_complete = true;

	    }

		if ($install_complete) {
			?>
	    	<div id="message" class="updated sensei-message sensei-connect">
				<div class="squeezer">
					<h4><?php _e( '<strong>Congratulations!</strong> &#8211; Sensei has been installed and setup.', 'woothemes-sensei' ); ?></h4>
					<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/sensei/" data-text="A premium Learning Management plugin for #WordPress that helps you create courses. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="Sensei">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
				</div>
			</div>
			<?php

			// Flush rules after install
			flush_rewrite_rules( false );

			// Set installed option
			update_option('sensei_installed', 0);
		}

	} // End install_pages_output()


	/**
	 * create_page function.
	 *
	 * @access public
	 * @param mixed $slug
	 * @param mixed $option
	 * @param string $page_title (default: '')
	 * @param string $page_content (default: '')
	 * @param int $post_parent (default: 0)
	 * @return void
	 */
	function create_page( $slug, $option, $page_title = '', $page_content = '', $post_parent = 0 ) {
		global $wpdb;

		$option_value = get_option( $option );

		if ( $option_value > 0 && get_post( $option_value ) )
			return;

		$page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;", $slug ) );
		if ( $page_found ) :
			if ( ! $option_value )
				update_option( $option, $page_found );
			return;
		endif;

		$page_data = array(
	        'post_status' 		=> 'publish',
	        'post_type' 		=> 'page',
	        'post_author' 		=> 1,
	        'post_name' 		=> $slug,
	        'post_title' 		=> $page_title,
	        'post_content' 		=> $page_content,
	        'post_parent' 		=> $post_parent,
	        'comment_status' 	=> 'closed'
	    );
	    $page_id = wp_insert_post( $page_data );

	    update_option( $option, $page_id );
	} // End create_page()


	/**
	 * create_pages function.
	 *
	 * @access public
	 * @return void
	 */
	function create_pages() {

		// Courses page
	    $this->create_page( esc_sql( _x('courses-overview', 'page_slug', 'woothemes-sensei') ), $this->token . '_courses_page_id', __('Courses', 'woothemes-sensei'), '[newcourses][featuredcourses][freecourses][paidcourses]' );

		// User Dashboard page
	    $this->create_page( esc_sql( _x('my-courses', 'page_slug', 'woothemes-sensei') ), $this->token . '_user_dashboard_page_id', __('My Courses', 'woothemes-sensei'), '[usercourses]' );

	} // End create_pages()

	/**
	 * Load the global admin styles for the menu icon and the relevant page icon.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_styles_global ( $hook ) {
		global $woothemes_sensei, $post_type, $wp_version;

		$allowed_post_types = apply_filters( 'sensei_scripts_allowed_post_types', array( 'lesson', 'course', 'question' ) );
		$allowed_post_type_pages = apply_filters( 'sensei_scripts_allowed_post_type_pages', array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php' ) );
		$allowed_pages = apply_filters( 'sensei_scripts_allowed_pages', array( 'sensei_grading', 'sensei_analysis', 'sensei_learners', 'sensei_updates', 'woothemes-sensei-settings', 'lesson-order', 'course-order' ) );

		// Global Styles for icons and menu items
		wp_register_style( $woothemes_sensei->token . '-global', $woothemes_sensei->plugin_url . 'assets/css/global.css', '', '1.6.0', 'screen' );
		wp_enqueue_style( $woothemes_sensei->token . '-global' );

		// Test for Write Panel Pages
		if ( ( ( isset( $post_type ) && in_array( $post_type, $allowed_post_types ) ) && ( isset( $hook ) && in_array( $hook, $allowed_post_type_pages ) ) ) || ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages ) ) ) {

			wp_register_style( $woothemes_sensei->token . '-admin-custom', $woothemes_sensei->plugin_url . 'assets/css/admin-custom.css', '', '1.6.0', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-admin-custom' );
			wp_register_style( $woothemes_sensei->token . '-chosen', $woothemes_sensei->plugin_url . 'assets/chosen/chosen.css', '', '1.5.2', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-chosen' );

		}

	} // End admin_styles_global()


	/**
	 * admin_install_notice function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_install_notice() {
	    ?>
	    <div id="message" class="updated sensei-message sensei-connect">
	    	<div class="squeezer">
	    		<h4><?php _e( '<strong>Welcome to Sensei</strong> &#8211; You\'re almost ready to create some courses!', 'woothemes-sensei' ); ?></h4>
	    		<p class="submit"><a href="<?php echo add_query_arg('install_sensei_pages', 'true', admin_url('admin.php?page=woothemes-sensei-settings')); ?>" class="button-primary"><?php _e( 'Install Sensei Pages', 'woothemes-sensei' ); ?></a> <a class="skip button" href="<?php echo add_query_arg('skip_install_sensei_pages', 'true', admin_url('admin.php?page=woothemes-sensei-settings')); ?>"><?php _e('Skip setup', 'woothemes-sensei'); ?></a></p>
	    	</div>
	    </div>
	    <?php
	} // End admin_install_notice()


	/**
	 * admin_installed_notice function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_installed_notice() {
	    ?>
	    <div id="message" class="updated sensei-message sensei-connect">
	    	<div class="squeezer">
	    		<h4><?php _e( '<strong>Sensei has been installed</strong> &#8211; You\'re ready to start creating courses!', 'woothemes-sensei' ); ?></h4>

	    		<p class="submit"><a href="<?php echo admin_url('admin.php?page=woothemes-sensei-settings'); ?>" class="button-primary"><?php _e( 'Settings', 'woothemes-sensei' ); ?></a> <a class="docs button-primary" href="http://www.woothemes.com/sensei-docs/"><?php _e('Documentation', 'woothemes-sensei'); ?></a></p>

	    		<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/sensei/" data-text="A premium Learning Management plugin for #WordPress that helps you teach courses online. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="Sensei">Tweet</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
	    	</div>
	    </div>
	    <?php

	    // Set installed option
	    update_option('sensei_installed', 0);
	} // End admin_installed_notice()


	/**
	 * admin_notices_styles function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_notices_styles() {
		global $woothemes_sensei;
		// Installed notices
	    if ( get_option('sensei_installed')==1 ) {

	    	wp_enqueue_style( 'sensei-activation', plugins_url(  '/assets/css/activation.css', dirname( __FILE__ ) ) );

	    	if (get_option('skip_install_sensei_pages')!=1 && $woothemes_sensei->get_page_id('course')<1 && !isset($_GET['install_sensei_pages']) && !isset($_GET['skip_install_sensei_pages'])) {
	    		add_action( 'admin_notices', array( $this, 'admin_install_notice' ) );
	    	} elseif ( !isset($_GET['page']) || $_GET['page']!='woothemes-sensei-settings' ) {
	    		add_action( 'admin_notices', array( $this, 'admin_installed_notice' ) );
	    	} // End If Statement

	    } // End If Statement
	} // End admin_notices_styles()

	/**
	 * Add links for duplicating lessons & courses
	 * @param  array  $actions Default actions
	 * @param  object $post    Current post
	 * @return array           Modified actions
	 */
	public function duplicate_action_link( $actions, $post ) {
		switch( $post->post_type ) {
			case 'lesson':
				$confirm = __( 'This will duplicate the lesson quiz and all of its questions. Are you sure you want to do this?', 'woothemes-sensei' );
				$actions['duplicate'] = "<a onclick='return confirm(\"" . $confirm . "\");' href='" . $this->get_duplicate_link( $post->ID ) . "' title='" . esc_attr(__( 'Duplicate this lesson', 'woothemes-sensei' ) ) . "'>" .  __('Duplicate', 'woothemes-sensei' ) . "</a>";
			break;

			case 'course':
				$confirm = __( 'This will duplicate the course lessons along with all of their quizzes and questions. Are you sure you want to do this?', 'woothemes-sensei' );
				$actions['duplicate'] = '<a href="' . $this->get_duplicate_link( $post->ID ) . '" title="' . esc_attr(__( 'Duplicate this course', 'woothemes-sensei' ) ) . '">' .  __('Duplicate', 'woothemes-sensei' ) . '</a>';
				$actions['duplicate_with_lessons'] = '<a onclick="return confirm(\'' . $confirm . '\');" href="' . $this->get_duplicate_link( $post->ID, true ) . '" title="' . esc_attr(__( 'Duplicate this course with its lessons', 'woothemes-sensei' ) ) . '">' .  __('Duplicate (with lessons)', 'woothemes-sensei' ) . '</a>';
			break;
		}

		return $actions;
	}

	/**
	 * Generate duplicationlink
	 * @param  integer $post_id      Post ID
	 * @param  boolean $with_lessons Include lessons or not
	 * @return string                Duplication link
	 */
	private function get_duplicate_link( $post_id = 0, $with_lessons = false ) {

		$post = get_post( $post_id );

		$action = 'duplicate_' . $post->post_type;

		if( 'course' == $post->post_type && $with_lessons ) {
			$action .= '_with_lessons';
		}

		return apply_filters( $action . '_link', admin_url( 'admin.php?action=' . $action . '&post=' . $post_id ), $post_id );
	}

	/**
	 * Duplicate lesson
	 * @return void
	 */
	public function duplicate_lesson_action() {
		$this->duplicate_content( 'lesson' );
	}

	/**
	 * Duplicate course
	 * @return void
	 */
	public function duplicate_course_action() {
		$this->duplicate_content( 'course' );
	}

	/**
	 * Duplicate course with lessons
	 * @return void
	 */
	public function duplicate_course_with_lessons_action() {
		$this->duplicate_content( 'course', true );
	}

	/**
	 * Duplicate content
	 * @param  string  $post_type    Post type being duplicated
	 * @param  boolean $with_lessons Include lessons or not
	 * @return void
	 */
	private function duplicate_content( $post_type = 'lesson', $with_lessons = false ) {
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( sprintf( __( 'Please supply a %1$s ID.', 'woothemes-sensei' ) ), $post_type );
		}

		$post_id = $_GET['post'];
		$post = get_post( $post_id );

		if( ! is_wp_error( $post ) ) {

			$new_post = $this->duplicate_post( $post );

			if( $new_post && ! is_wp_error( $new_post ) ) {

				if( 'lesson' == $new_post->post_type ) {
					$this->duplicate_lesson_quizzes( $post_id, $new_post->ID );
				}

				if( 'course' == $new_post->post_type && $with_lessons ) {
					$this->duplicate_course_lessons( $post_id, $new_post->ID );
				}

				$redirect_url = admin_url( 'post.php?post=' . $new_post->ID . '&action=edit' );
			} else {
				$redirect_url = admin_url( 'edit.php?post_type=' . $post->post_type . '&message=duplicate_failed' );
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Duplicate quizzes inside lessons
	 * @param  integer $old_lesson_id ID of original lesson
	 * @param  integer $new_lesson_id ID of duplicate lesson
	 * @return void
	 */
	private function duplicate_lesson_quizzes( $old_lesson_id, $new_lesson_id ) {

		$quiz_args = array(
			'post_type' => 'quiz',
			'posts_per_page' => -1,
			'meta_key' => '_quiz_lesson',
			'meta_value' => $old_lesson_id,
			'suppress_filters' 	=> 0
		);
		$quizzes = get_posts( $quiz_args );

		foreach( $quizzes as $quiz ) {

			$question_args = array(
				'post_type'	=> 'question',
				'posts_per_page' => -1,
				'meta_query'		=> array(
					array(
						'key'       => '_quiz_id',
						'value'     => $quiz->ID,
					)
				),
				'suppress_filters' => 0
			);
			$questions = get_posts( $question_args );

			$new_quiz = $this->duplicate_post( $quiz, '' );
			add_post_meta( $new_quiz->ID, '_quiz_lesson', $new_lesson_id );

			foreach( $questions as $question ) {
				$new_question = $this->duplicate_post( $question, '' );
				add_post_meta( $new_question->ID, '_quiz_id', $new_quiz->ID, false );
			}
		}
	}

	/**
	 * Duplicate lessons inside a course
	 * @param  integer $old_course_id ID of original course
	 * @param  integer $new_course_id ID of duplicated course
	 * @return void
	 */
	private function duplicate_course_lessons( $old_course_id, $new_course_id ) {
		$lesson_args = array(
			'post_type' => 'lesson',
			'posts_per_page' => -1,
			'meta_key' => '_lesson_course',
			'meta_value' => $old_course_id,
			'suppress_filters' 	=> 0
		);
		$lessons = get_posts( $lesson_args );

		foreach( $lessons as $lesson ) {
			$new_lesson = $this->duplicate_post( $lesson, '', true );
			add_post_meta( $new_lesson->ID, '_lesson_course', $new_course_id );

			$this->duplicate_lesson_quizzes( $lesson->ID, $new_lesson->ID );
		}
	}

	/**
	 * Duplicate post
	 * @param  object  $post          Post to be duplicated
	 * @param  string  $suffix        Suffix for duplicated post title
	 * @param  boolean $ignore_course Ignore lesson course when dulicating
	 * @return object                 Duplicate post object
	 */
	private function duplicate_post( $post, $suffix = ' (Duplicate)', $ignore_course = false ) {

		$new_post = array();

		foreach( $post as $k => $v ) {
			if( ! in_array( $k, array( 'ID', 'post_status', 'post_date', 'post_date_gmt', 'post_name', 'post_modified', 'post_modified_gmt', 'guid', 'comment_count' ) ) ) {
				$new_post[ $k ] = $v;
			}
		}

		$new_post['post_title'] .= __( $suffix, 'woothemes-sensei' );

		$new_post['post_date'] = date( 'Y-m-d H:i:s' );
		$new_post['post_date_gmt'] = get_gmt_from_date( $new_post['post_date'] );
		$new_post['post_modified'] = $new_post['post_date'];
		$new_post['post_modified_gmt'] = $new_post['post_date_gmt'];

		switch( $post->post_type ) {
			case 'course': $new_post['post_status'] = 'draft'; break;
			case 'lesson': $new_post['post_status'] = 'draft'; break;
			case 'quiz': $new_post['post_status'] = 'publish'; break;
			case 'question': $new_post['post_status'] = 'publish'; break;
		}

		$new_post_id = wp_insert_post( $new_post );

		if( ! is_wp_error( $new_post_id ) ) {

			$post_meta = get_post_custom( $post->ID );
			if( $post_meta && count( $post_meta ) > 0 ) {

				$ignore_meta = array( '_quiz_lesson', '_quiz_id' );
				if( $ignore_course ) {
					$ignore_meta[] = '_lesson_course';
				}

				foreach( $post_meta as $key => $meta ) {
					foreach( $meta as $value ) {
						$value = maybe_unserialize( $value );
						if( ! in_array( $key, $ignore_meta ) ) {
							add_post_meta( $new_post_id, $key, $value );
						}
					}
				}
			}

			add_post_meta( $new_post_id, '_duplicate', $post->ID );

			$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

			foreach ( $taxonomies as $slug => $tax ) {
				$terms = get_the_terms( $post->ID, $slug );
				if( isset( $terms ) && is_array( $terms ) && 0 < count( $terms ) ) {
					foreach( $terms as $term ) {
						wp_set_object_terms( $new_post_id, $term->term_id, $slug, true );
					}
				}
			}

			$new_post = get_post( $new_post_id );

			return $new_post;
		}

		return false;
	}

	/**
	 * Add options to filter lessons
	 * @return void
	 */
	public function lesson_filter_options() {
		global $typenow;

		if( is_admin() && 'lesson' == $typenow ) {

			$args = array(
				'post_type' => 'course',
				'posts_per_page' => -1,
				'suppress_filters' => 0,
				'orderby' => 'menu_order date',
				'order' => 'ASC',
			);
			$courses = get_posts( $args );

			$selected = isset( $_GET['lesson_course'] ) ? $_GET['lesson_course'] : '';
			$course_options = '';
			foreach( $courses as $course ) {
				$course_options .= '<option value="' . esc_attr( $course->ID ) . '" ' . selected( $selected, $course->ID, false ) . '>' . get_the_title( $course->ID ) . '</option>';
			}

			$output = '<select name="lesson_course" id="dropdown_lesson_course">';
			$output .= '<option value="">'.__( 'Show all courses', 'woothemes-sensei' ).'</option>';
			$output .= $course_options;
			$output .= '</select>';

			echo $output;
		}
	}

	/**
	 * Filter lessons
	 * @param  array $request Current request
	 * @return array          Modified request
	 */
	public function lesson_filter_actions( $request ) {
		global $typenow;

		if( is_admin() && 'lesson' == $typenow ) {
			$lesson_course = isset( $_GET['lesson_course'] ) ? $_GET['lesson_course'] : '';

			if( $lesson_course ) {
				$request['meta_key'] = '_lesson_course';
				$request['meta_value'] = $lesson_course;
				$request['meta_compare'] = '=';
			}
		}

		return $request;
	}

	/**
	 * Adding Sensei items to 'At a glance' dashboard widget
	 * @param  array $items Existing items
	 * @return array        Updated items
	 */
	public function glance_items( $items = array() ) {
		global $woothemes_sensei;

		$types = array( 'course', 'lesson', 'question' );

		foreach( $types as $type ) {
			if( ! post_type_exists( $type ) ) continue;

			$num_posts = wp_count_posts( $type );

			if( $num_posts ) {

				$published = intval( $num_posts->publish );
				$post_type = get_post_type_object( $type );

				$text = _n( '%s ' . $post_type->labels->singular_name, '%s ' . $post_type->labels->name, $published, 'woothemes-sensei' );
				$text = sprintf( $text, number_format_i18n( $published ) );

				if ( current_user_can( $post_type->cap->edit_posts ) ) {
					$items[] = sprintf( '<a class="%1$s-count" href="edit.php?post_type=%1$s">%2$s</a>', $type, $text ) . "\n";
				} else {
					$items[] = sprintf( '<span class="%1$s-count">%2$s</span>', $type, $text ) . "\n";
				}
			}
		}

		return $items;
	}

	/**
	 * Process lesson and course deletion
	 * @param  integer $post_id Post ID
	 * @param  object  $post    Post object
	 * @return void
	 */
	public function delete_content( $post_id, $post ) {

		$type = $post->post_type;

		if( in_array( $type, array( 'lesson', 'course' ) ) ) {

			$meta_key = '_' . $type . '_prerequisite';

			$args = array(
				'post_type' => $type,
				'post_status' => 'any',
				'posts_per_page' => -1,
				'meta_key' => $meta_key,
				'meta_value' => $post_id
			);

			$posts = get_posts( $args );

			foreach( $posts as $post ) {
				delete_post_meta( $post->ID, $meta_key );
			}
		}
	}

	/**
	 * Delete all user activity when user is deleted
	 * @param  integer $user_id User ID
	 * @return void
	 */
	public function delete_user_activity( $user_id = 0 ) {
		if( $user_id ) {
			WooThemes_Sensei_Utils::delete_all_user_activity( $user_id );
		}
	}

	public function render_settings( $settings = array(), $post_id = 0, $group_id = '' ) {

		$html = '';

		if( 0 == count( $settings ) ) return $html;

		$html .= '<div class="sensei-options-panel">' . "\n";

			$html .= '<div class="options_group" id="' . esc_attr( $group_id ) . '">' . "\n";

				foreach( $settings as $field ) {

					$data = '';

					if( $post_id ) {
						$data = get_post_meta( $post_id, '_' . $field['id'], true );
						if( ! $data && isset( $field['default'] ) ) {
							$data = $field['default'];
						}
					} else {
						$option = get_option( $field['id'] );
						if( isset( $field['default'] ) ) {
							$data = $field['default'];
							if( $option ) {
								$data = $option;
							}
						}
					}

					$disabled = '';
					if( isset( $field['disabled'] ) && $field['disabled'] ) {
						$disabled = disabled( $field['disabled'], true, false );
					}

					if( 'hidden' != $field['type'] ) {

						$class_tail = '';

						if( isset( $field['class'] ) ) {
							$class_tail .= $field['class'];
						}

						if( isset( $field['disabled'] ) && $field['disabled'] ) {
							$class_tail .= ' disabled';
						}

						$html .= '<p class="form-field ' . esc_attr( $field['id'] ) . ' ' . esc_attr( $class_tail ) . '">' . "\n";
					}

						if( ! in_array( $field['type'], array( 'hidden', 'checkbox_multi', 'radio' ) ) ) {
							$html .= '<label for="' . esc_attr( $field['id'] ) . '">' . "\n";
						}

							if( $field['label'] ) {
								$html .= '<span class="label">' . esc_html( $field['label'] ) . '</span>';
							}

							switch( $field['type'] ) {
								case 'text':
								case 'password':
									$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '" ' . $disabled . ' />' . "\n";
								break;

								case 'number':

									$min = '';
									if( isset( $field['min'] ) ) {
										$min = 'min="' . esc_attr( $field['min'] ) . '"';
									}

									$max = '';
									if( isset( $field['max'] ) ) {
										$max = 'max="' . esc_attr( $field['max'] ) . '"';
									}

									$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '" ' . $min . '  ' . $max . ' class="small-text" ' . $disabled . ' />' . "\n";
								break;

								case 'textarea':
									$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . $disabled . '>' . $data . '</textarea><br/>'. "\n";
								break;

								case 'checkbox':
									$checked = checked( $field['checked'], $data, false );
									$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $field['id'] ) . '" ' . $checked . ' ' . $disabled . '/>' . "\n";
								break;

								case 'checkbox_multi':
									foreach( $field['options'] as $k => $v ) {
										$checked = false;
										if( in_array( $k, $data ) ) {
											$checked = true;
										}
										$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $field['id'] ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" ' . $disabled . ' /> ' . $v . '</label> ' . "\n";
									}
								break;

								case 'radio':
									foreach( $field['options'] as $k => $v ) {
										$checked = false;
										if( $k == $data ) {
											$checked = true;
										}
										$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" ' . $disabled . ' /> ' . $v . '</label> ' . "\n";
									}
								break;

								case 'select':
									$html .= '<select name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" ' . $disabled . '>' . "\n";
									foreach( $field['options'] as $k => $v ) {
										$selected = false;
										if( $k == $data ) {
											$selected = true;
										}
										$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>' . "\n";
									}
									$html .= '</select><br/>' . "\n";
								break;

								case 'select_multi':
									$html .= '<select name="' . esc_attr( $field['id'] ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple" ' . $disabled . '>' . "\n";
									foreach( $field['options'] as $k => $v ) {
										$selected = false;
										if( in_array( $k, $data ) ) {
											$selected = true;
										}
										$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '" />' . $v . '</option>' . "\n";
									}
									$html .= '</select> . "\n"';
								break;

								case 'hidden':
									$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $field['id'] ) . '" value="' . $data . '" ' . $disabled . '/>' . "\n";
								break;

							}

							if( $field['description'] ) {
								$html .= ' <span class="description">' . esc_html( $field['description'] ) . '</span>' . "\n";
							}

						if( ! in_array( $field['type'], array( 'hidden', 'checkbox_multi', 'radio' ) ) ) {
							$html .= '</label>' . "\n";
						}

					if( 'hidden' != $field['type'] ) {
						$html .= '</p>' . "\n";
					}

				}

			$html .= '</div>' . "\n";

		$html .= '</div>' . "\n";

		return $html;
	}

	/**
	 * Dsplay Course Order screen
	 * @return void
	 */
	public function course_order_screen() {
		global $woothemes_sensei;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'woothemes-sensei-settings', esc_url( $woothemes_sensei->plugin_url . 'assets/js/settings' . $suffix . '.js' ), array( 'jquery', 'jquery-ui-sortable' ), '1.6.0' );

		?><div id="course-order" class="wrap course-order">
		<h2><?php _e( 'Order Courses', 'woothemes-sensei' ); ?></h2><?php

		$html = '';

		if( isset( $_POST['course-order'] ) && 0 < strlen( $_POST['course-order'] ) ) {
			$ordered = $this->save_course_order( esc_attr( $_POST['course-order'] ) );

			if( $ordered ) {
				$html .= '<div class="updated fade">' . "\n";
				$html .= '<p>' . __( 'The course order has been saved.', 'woothemes-sensei' ) . '</p>' . "\n";
				$html .= '</div>' . "\n";
			}
		}

		$args = array(
			'post_type' => 'course',
			'posts_per_page' => -1,
			'suppress_filters' => 0,
			'orderby' => 'menu_order date',
			'order' => 'ASC',
		);

		$courses = get_posts( $args );
		if( 0 < count( $courses ) ) {

			$order_string = $this->get_course_order();

			$html .= '<form id="editgrouping" method="post" action="" class="validate">' . "\n";
			$html .= '<ul class="sortable-course-list">' . "\n";
			$count = 0;
			foreach ( $courses as $course ) {
				$count++;
				$class = 'course';
				if ( $count == 1 ) { $class .= ' first'; }
				if ( $count == count( $course ) ) { $class .= ' last'; }
				if ( $count % 2 != 0 ) {
					$class .= ' alternate';
				}
				$html .= '<li class="' . esc_attr( $class ) . '"><span rel="' . esc_attr( $course->ID ) . '" style="width: 100%;"> ' . $course->post_title . '</span></li>' . "\n";
			}
			$html .= '</ul>' . "\n";

			$html .= '<input type="hidden" name="course-order" value="' . esc_attr( $order_string ) . '" />' . "\n";
			$html .= '<input type="submit" class="button-primary" value="' . __( 'Save course order', 'woothemes-sensei' ) . '" />' . "\n";
		}

		echo $html;

		?></div><?php
	}

	public function get_course_order() {
		return get_option( 'sensei_course_order', '' );
	}

	public function save_course_order( $order_string = '' ) {
		$order = explode( ',', $order_string );

		update_option( 'sensei_course_order', $order_string );

		$i = 1;
		foreach( $order as $course_id ) {

			if( $course_id ) {

				$update_args = array(
					'ID' => $course_id,
					'menu_order' => $i,
				);

				wp_update_post( $update_args );

				++$i;
			}
		}

		return true;
	}

	/**
	 * Dsplay Lesson Order screen
	 * @return void
	 */
	public function lesson_order_screen() {
		global $woothemes_sensei;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'woothemes-sensei-settings', esc_url( $woothemes_sensei->plugin_url . 'assets/js/settings' . $suffix . '.js' ), array( 'jquery', 'jquery-ui-sortable' ), '1.6.0' );

		?><div id="lesson-order" class="wrap lesson-order">
		<h2><?php _e( 'Order Lessons', 'woothemes-sensei' ); ?></h2><?php

		$html = '';

		if( isset( $_POST['lesson-order'] ) ) {

			$ordered = $this->save_lesson_order( esc_attr( $_POST['lesson-order'] ), esc_attr( $_POST['course_id'] ) );

			if( $ordered ) {
				$html .= '<div class="updated fade">' . "\n";
				$html .= '<p>' . __( 'The lesson order has been saved.', 'woothemes-sensei' ) . '</p>' . "\n";
				$html .= '</div>' . "\n";
			}
		}

		$args = array(
			'post_type' => 'course',
			'post_status' => array('publish', 'draft', 'future', 'private'),
			'posts_per_page' => -1,
			'orderby' => 'name',
			'order' => 'ASC',
		);
		$courses = get_posts( $args );

		$html .= '<form action="' . admin_url( 'edit.php' ) . '" method="get">' . "\n";
		$html .= '<input type="hidden" name="post_type" value="lesson" />' . "\n";
		$html .= '<input type="hidden" name="page" value="lesson-order" />' . "\n";
		$html .= '<select id="lesson-order-course" name="course_id">' . "\n";
		$html .= '<option value="">' . __( 'Select a course', 'woothemes-sensei' ) . '</option>' . "\n";

		foreach( $courses as $course ) {
			$course_id = '';
			if( isset( $_GET['course_id'] ) ) {
				$course_id = intval( $_GET['course_id'] );
			}
			$html .= '<option value="' . esc_attr( intval( $course->ID ) ) . '" ' . selected( $course->ID, $course_id, false ) .'>' . get_the_title( $course->ID ) . '</option>' . "\n";
		}

		$html .= '</select>' . "\n";
		$html .= '<input type="submit" class="button-primary lesson-order-select-course-submit" value="' . __( 'Select', 'woothemes-sensei' ) . '" />' . "\n";
		$html .= '</form>' . "\n";

		$html .= '<script type="text/javascript">' . "\n";
		$html .= 'jQuery( \'#lesson-order-course\' ).chosen();' . "\n";
		$html .= '</script>' . "\n";

		if( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			if( $course_id > 0 ) {

				$order_string = $this->get_lesson_order( $course_id );

				$html .= '<form id="editgrouping" method="post" action="" class="validate">' . "\n";

				$displayed_lessons = array();

				if( class_exists( 'Sensei_Modules' ) ) {
					global $sensei_modules;

					$modules = $sensei_modules->get_course_modules( intval( $course_id ) );

					foreach( $modules as $module ) {

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
									'taxonomy' => $sensei_modules->taxonomy,
									'field' => 'id',
									'terms' => intval( $module->term_id )
								)
							),
							'meta_key' => '_order_module_' . $module->term_id,
							'orderby' => 'meta_value_num date',
							'order' => 'ASC',
							'suppress_filters' => 0
						);

						$lessons = get_posts( $args );

						if( count( $lessons ) > 0 ) {
							$html .= '<h3>' . $module->name . '</h3>' . "\n";
							$html .= '<ul class="sortable-lesson-list" data-module_id="' . $module->term_id . '">' . "\n";

							$count = 0;
							foreach( $lessons as $lesson ) {
								$count++;
								$class = 'lesson';
								if ( $count == 1 ) { $class .= ' first'; }
								if ( $count == count( $lesson ) ) { $class .= ' last'; }
								if ( $count % 2 != 0 ) {
									$class .= ' alternate';
								}

								$html .= '<li class="' . esc_attr( $class ) . '"><span rel="' . esc_attr( $lesson->ID ) . '" style="width: 100%;"> ' . $lesson->post_title . '</span></li>' . "\n";

								$displayed_lessons[] = $lesson->ID;
							}

							$html .= '</ul>' . "\n";

							$html .= '<input type="hidden" name="lesson-order-module-' . $module->term_id . '" value="" />' . "\n";
						}
					}
				}

				$args = array(
					'post_type' => 'lesson',
					'posts_per_page' => -1,
					'suppress_filters' => 0,
					'meta_key' => '_order_' . $course_id,
					'orderby' => 'meta_value_num date',
					'order' => 'ASC',
					'meta_query' => array(
						array(
							'key' => '_lesson_course',
							'value' => intval( $course_id ),
						),
					),
					'post__not_in' => $displayed_lessons,
				);

				$lessons = get_posts( $args );

				if( 0 < count( $lessons ) ) {

					if( 0 < count( $displayed_lessons ) && class_exists( 'Sensei_Modules' ) ) {
						$html .= '<h3>' . __( 'Other Lessons', 'woothemes-sensei' ) . '</h3>' . "\n";
					}

					$html .= '<ul class="sortable-lesson-list" data-module_id="0">' . "\n";
					$count = 0;
					foreach ( $lessons as $lesson ) {
						$count++;
						$class = 'lesson';
						if ( $count == 1 ) { $class .= ' first'; }
						if ( $count == count( $lesson ) ) { $class .= ' last'; }
						if ( $count % 2 != 0 ) {
							$class .= ' alternate';
						}
						$html .= '<li class="' . esc_attr( $class ) . '"><span rel="' . esc_attr( $lesson->ID ) . '" style="width: 100%;"> ' . $lesson->post_title . '</span></li>' . "\n";

						$displayed_lessons[] = $lesson->ID;
					}
					$html .= '</ul>' . "\n";
				} else {
					if( 0 == count( $displayed_lessons ) ) {
						$html .= '<p><em>' . __( 'There are no lessons in this course.', 'woothemes-sensei' ) . '</em></p>';
					}
				}

				if( 0 < count( $displayed_lessons ) ) {
					$html .= '<input type="hidden" name="lesson-order" value="' . esc_attr( $order_string ) . '" />' . "\n";
					$html .= '<input type="hidden" name="course_id" value="' . $course_id . '" />' . "\n";
					$html .= '<input type="submit" class="button-primary" value="' . __( 'Save lesson order', 'woothemes-sensei' ) . '" />' . "\n";
				}
			}
		}

		echo $html;

		?></div><?php
	}

	public function get_lesson_order( $course_id = 0 ) {
		$order_string = get_post_meta( $course_id, '_lesson_order', true );
		return $order_string;
	}

	public function save_lesson_order( $order_string = '', $course_id = 0 ) {

		if( $course_id ) {

			if( class_exists( 'Sensei_Modules' ) ) {
				global $sensei_modules;

				$modules = $sensei_modules->get_course_modules( intval( $course_id ) );

				foreach( $modules as $module ) {

					$module_order_string = $_POST[ 'lesson-order-module-' . $module->term_id ];

					if( $module_order_string ) {
						$order = explode( ',', $module_order_string );
						$i = 1;
						foreach( $order as $lesson_id ) {
							if( $lesson_id ) {
								update_post_meta( $lesson_id, '_order_module_' . $module->term_id, $i );
								++$i;
							}
						}
					}
				}
			}

			if( $order_string ) {
				update_post_meta( $course_id, '_lesson_order', $order_string );

				$order = explode( ',', $order_string );

				$i = 1;
				foreach( $order as $lesson_id ) {
					if( $lesson_id ) {
						update_post_meta( $lesson_id, '_order_' . $course_id, $i );
						++$i;
					}
				}
			}

			return true;
		}

		return false;
	}

	function sensei_add_custom_menu_items() {
		global $pagenow;

		if( 'nav-menus.php' == $pagenow ) {
			add_meta_box( 'add-sensei-links', __( 'Sensei', 'woothemes-sensei' ), array( $this, 'wp_nav_menu_item_sensei_links_meta_box' ), 'nav-menus', 'side', 'low' );
		}
	}

	function wp_nav_menu_item_sensei_links_meta_box( $object ) {
		global $nav_menu_selected_id, $woothemes_sensei;

		$menu_items = array(
						'#senseicourses' => __( 'Courses', 'woothemes_sensei' ),
						'#senseilessons' => __( 'Lessons', 'woothemes_sensei' ),
						'#senseimycourses' => __( 'My Courses', 'woothemes_sensei' ),
						'#senseilearnerprofile' => __( 'My Profile', 'woothemes_sensei' ),
						'#senseimymessages' => __( 'My Messages', 'woothemes_sensei' ),
						'#senseiloginlogout' => __( 'Login', 'woothemes_sensei' ) . '|' . __( 'Logout', 'woothemes_sensei' )
						 );

		$menu_items_obj = array();
		foreach ( $menu_items as $value => $title ) {
			$menu_items_obj[$title] = new stdClass;
			$menu_items_obj[$title]->object_id			= esc_attr( $value );
			$menu_items_obj[$title]->title				= esc_attr( $title );
			$menu_items_obj[$title]->url				= esc_attr( $value );
			$menu_items_obj[$title]->description 		= 'description';
			$menu_items_obj[$title]->db_id 				= 0;
			$menu_items_obj[$title]->object 			= 'sensei';
			$menu_items_obj[$title]->menu_item_parent 	= 0;
			$menu_items_obj[$title]->type 				= 'custom';
			$menu_items_obj[$title]->target 			= '';
			$menu_items_obj[$title]->attr_title 		= '';
			$menu_items_obj[$title]->classes 			= array();
			$menu_items_obj[$title]->xfn 				= '';
		}

		$walker = new Walker_Nav_Menu_Checklist( array() );
		?>

		<div id="sensei-links" class="senseidiv taxonomydiv">
			<div id="tabs-panel-sensei-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">

				<ul id="sensei-linkschecklist" class="list:sensei-links categorychecklist form-no-clear">
					<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $menu_items_obj ), 0, (object)array( 'walker' => $walker ) ); ?>
				</ul>

			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'woothemes-sensei' ); ?>" name="add-sensei-links-menu-item" id="submit-sensei-links" />
					<span class="spinner"></span>
				</span>
			</p>
		</div><!-- .senseidiv -->
		<?php
	}

	/**
	 * Adding admin notices
	 * @return void
	 */
	public function admin_notices() {
		global $current_user;
		wp_get_current_user();
        $user_id = $current_user->ID;

        if( isset( $_GET['sensei_hide_notice'] ) ) {
        	switch( esc_attr( $_GET['sensei_hide_notice'] ) ) {
				case 'menu_settings': add_user_meta( $user_id, 'sensei_hide_menu_settings_notice', true ); break;
			}
        }

        $screen = get_current_screen();

        if( 'sensei_page_woothemes-sensei-settings' == $screen->id ) {

	        $hide_menu_settings_notice = get_user_meta( $user_id, 'sensei_hide_menu_settings_notice', true );

	        if( ! $hide_menu_settings_notice ) {
	        	?>
				<div class="updated fade">
			        <p><?php printf( __( 'The settings for the Sensei menu items have been removed. Menu items can now be added individually via the %1$sWordPress menu editor%2$s.%3$s%4$sDismiss this notice%5$s', 'woothemes-sensei' ), '<a href="' . admin_url( 'nav-menus.php' ) . '">', '</a>', '<br/>', '<em><a href="' . add_query_arg( 'sensei_hide_notice', 'menu_settings' ) . '">', '</a></em>' ); ?></p>
			    </div>
			    <?php
	        }
	    }
	}

} // End Class

?>