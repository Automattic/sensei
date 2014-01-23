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

		// Duplicate lesson & courses
		add_filter( 'post_row_actions', array( $this, 'duplicate_action_link' ), 10, 2 );
		add_action( 'admin_action_duplicate_lesson', array( $this, 'duplicate_lesson_action' ) );
		add_action( 'admin_action_duplicate_course', array( $this, 'duplicate_course_action' ) );
		add_action( 'admin_action_duplicate_course_with_lessons', array( $this, 'duplicate_course_with_lessons_action' ) );

	} // End __construct()

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

	private function get_duplicate_link( $post_id = 0, $with_lessons = false ) {

		$post = get_post( $post_id );

		$action = 'duplicate_' . $post->post_type;

		if( 'course' == $post->post_type && $with_lessons ) {
			$action .= '_with_lessons';
		}

		return apply_filters( $action . '_link', admin_url( 'admin.php?action=' . $action . '&post=' . $post_id ), $post_id );
	}

	public function duplicate_lesson_action() {
		$this->duplicate_content( 'lesson' );
	}

	public function duplicate_course_action() {
		$this->duplicate_content( 'course' );
	}

	public function duplicate_course_with_lessons_action() {
		$this->duplicate_content( 'course', true );
	}

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
				'meta_key' => '_quiz_id',
				'meta_value' => $quiz->ID,
				'suppress_filters' => 0
			);
			$questions = get_posts( $question_args );

			$new_quiz = $this->duplicate_post( $quiz, '' );
			add_post_meta( $new_quiz->ID, '_quiz_lesson', $new_lesson_id );

			foreach( $questions as $question ) {
				$new_question = $this->duplicate_post( $question, '' );
				add_post_meta( $new_question->ID, '_quiz_id', $new_quiz->ID );
			}
		}
	}

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

	private function duplicate_post( $post, $suffix = ' (Duplicate)', $ignore_course = false ) {

		$new_post = array();

		foreach( $post as $k => $v ) {
			if( ! in_array( $k, array( 'ID', 'post_date', 'post_date_gmt', 'post_name', 'post_modified', 'post_modified_gmt', 'guid', 'comment_count' ) ) ) {
				$new_post[ $k ] = $v;
			}
		}

		$new_post['post_title'] .= __( $suffix, 'woothemes-sensei' );

		$new_post['post_date'] = date( 'Y-m-d H:i:s' );
		$new_post['post_date_gmt'] = get_gmt_from_date( $new_post['post_date'] );
		$new_post['post_modified'] = $new_post['post_date'];
		$new_post['post_modified_gmt'] = $new_post['post_date_gmt'];

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
				foreach( $terms as $term ) {
					wp_set_object_terms( $new_post_id, $term->term_id, $slug, true );
				}
			}

			$new_post = get_post( $new_post_id );

			return $new_post;
		}

		return false;
	}

	/**
	 * Add items to admin menu
	 * @since  1.4.0
	 * @return void
	 */
	public function admin_menu() {
		global $woothemes_sensei, $menu;
		if( current_user_can( 'manage_options' ) ) {
			$menu[] = array( '', 'read', 'separator-sensei', '', 'wp-menu-separator sensei' );
			$main_page = add_menu_page( __( 'Sensei', 'woothemes-sensei' ), __( 'Sensei', 'woothemes-sensei' ), 'manage_options', 'sensei' , array( $woothemes_sensei->analysis, 'analysis_page' ) , '', '50' );
		}
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

		$allowed_post_types = apply_filters( 'sensei_scripts_allowed_post_types', array( 'lesson', 'course' ) );
		$allowed_post_type_pages = apply_filters( 'sensei_scripts_allowed_post_type_pages', array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php' ) );
		$allowed_hooks = apply_filters( 'sensei_scripts_allowed_hooks', array( 'sensei_page_sensei_grading', 'sensei_page_sensei_analysis', 'sensei_page_sensei_updates', 'sensei_page_woothemes-sensei-settings' ) );

		// Global Styles for icons and menu items
		if( version_compare( $wp_version, '3.8', '>=') ) {
			wp_register_style( $woothemes_sensei->token . '-global', $woothemes_sensei->plugin_url . 'assets/css/global.css', '', '1.4.6', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-global' );
		} else {
			wp_register_style( $woothemes_sensei->token . '-global-old', $woothemes_sensei->plugin_url . 'assets/css/global-old.css', '', '1.4.5', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-global-old' );
		}

		// Test for Write Panel Pages
		if ( ( ( isset( $post_type ) && in_array( $post_type, $allowed_post_types ) ) && ( isset( $hook ) && in_array( $hook, $allowed_post_type_pages ) ) ) || ( isset( $hook ) && in_array( $hook, $allowed_hooks ) ) ) {

			wp_register_style( $woothemes_sensei->token . '-admin-custom', $woothemes_sensei->plugin_url . 'assets/css/admin-custom.css', '', '1.4.5', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-admin-custom' );
			wp_register_style( $woothemes_sensei->token . '-chosen', $woothemes_sensei->plugin_url . 'assets/chosen/chosen.css', '', '1.3.0', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-chosen' );

		} else {

			return;

		} // End If Statement

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

} // End Class
?>