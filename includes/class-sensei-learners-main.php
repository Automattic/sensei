<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Learners Overview List Table Class
 *
 * All functionality pertaining to the Admin Learners Overview Data Table in Sensei.
 *
 * @package Assessment
 * @author Automattic
 *
 * @since 1.3.0
 */
class Sensei_Learners_Main extends WooThemes_Sensei_List_Table {

	public $course_id = 0;
	public $lesson_id = 0;
	public $view = 'courses';
	public $page_slug = 'sensei_learners';

	/**
	 * Constructor
	 * @since  1.6.0
	 */
	public function __construct ( $course_id = 0, $lesson_id = 0 ) {
		$this->course_id = intval( $course_id );
		$this->lesson_id = intval( $lesson_id );

		if( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'courses', 'lessons', 'learners' ) ) ) {
			$this->view = $_GET['view'];
		}

		// Viewing a single lesson always sets the view to Learners
		if( $this->lesson_id ) {
			$this->view = 'learners';
		}

		// Load Parent token into constructor
		parent::__construct( 'learners_main' );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
		add_action( 'sensei_learners_extra', array( $this, 'add_learners_box' ) );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	} // End __construct()

	public function get_course_id() {
		return $this->course_id;
	}

	public function get_lesson_id() {
		return $this->lesson_id;
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		$columns = array();
		switch( $this->view ) {
			case 'learners':
				$columns = array(
					'title' => __( 'Learner', 'woothemes-sensei' ),
					'date_started' => __( 'Date Started', 'woothemes-sensei' ),
					'user_status' => __( 'Status', 'woothemes-sensei' ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title' => __( 'Lesson', 'woothemes-sensei' ),
					'num_learners' => __( '# Learners', 'woothemes-sensei' ),
					'updated' => __( 'Last Updated', 'woothemes-sensei' ),
				);
				break;

			case 'courses':
			default:
				$columns = array(
					'title' => __( 'Course', 'woothemes-sensei' ),
					'num_learners' => __( '# Learners', 'woothemes-sensei' ),
					'updated' => __( 'Last Updated', 'woothemes-sensei' ),
				);
				break;
		}
		$columns['actions'] = '';
		// Backwards compatible
		if ( 'learners' == $this->view ) {
			$columns = apply_filters( 'sensei_learners_learners_columns', $columns, $this );
		}
		$columns = apply_filters( 'sensei_learners_default_columns', $columns, $this );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {
		$columns = array();
		switch( $this->view ) {
			case 'learners':
				$columns = array(
					'title' => array( 'title', false ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title' => array( 'title', false ),
					'updated' => array( 'post_modified', false ),
				);
				break;

			default:
				$columns = array(
					'title' => array( 'title', false ),
					'updated' => array( 'post_modified', false ),
				);
				break;
		}
		// Backwards compatible
		if ( 'learners' == $this->view ) {
			$columns = apply_filters( 'sensei_learners_learners_columns_sortable', $columns, $this );
		}
		$columns = apply_filters( 'sensei_learners_default_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		global $avail_stati, $wpdb, $per_page;

		// Handle orderby
		$orderby = '';
		if ( !empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			} // End If Statement
		}

		// Handle order
		$order = 'DESC';
		if ( !empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper($_GET['order']) ) ? 'ASC' : 'DESC';
		}

		// Handle category selection
		$category = false;
		if ( !empty( $_GET['course_cat'] ) ) {
			$category = intval( $_GET['course_cat'] );
		} // End If Statement

		// Handle search
		$search = false;
		if ( !empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		} // End If Statement

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged = $this->get_pagenum();
		$offset = 0;
		if ( !empty($paged) ) {
			$offset = $per_page * ( $paged - 1 );
		} // End If Statement

		switch( $this->view ) {
			case 'learners':
				if ( empty($orderby) ) {
					$orderby = '';
				}
				$this->items = $this->get_learners( compact( 'per_page', 'offset', 'orderby', 'order', 'search' ) );

			break;

			case 'lessons':
				if ( empty($orderby) ) {
					$orderby = 'post_modified';
				}
				$this->items = $this->get_lessons( compact( 'per_page', 'offset', 'orderby', 'order', 'search' ) );

			break;

			default:
				if ( empty($orderby) ) {
					$orderby = 'post_modified';
				}
				$this->items = $this->get_courses( compact( 'per_page', 'offset', 'orderby', 'order', 'category', 'search' ) );

			break;
		}

		$total_items = $this->total_items;
		$total_pages = ceil( $total_items / $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );

	} // End prepare_items()

	/**
	 * Generates content for a single row of the table in the user management
     * screen.
     *
	 * @since  1.7.0
     *
	 * @param object $item The current item
     *
     * @return array $column_data
	 */
	protected function get_row_data( $item ) {
		global $wp_version;

		if( ! $item ) {
			return array(
				'title' => __( 'No results found', 'woothemes-sensei' ),
				'num_learners' => '',
				'updated' => '',
				'actions' => '',
			);
		}
		switch ( $this->view ) {
			case 'learners' :

                // in this case the item passed in is actually the users activity on course of lesson
                $user_activity = $item;
				$post_id = false;

				if( $this->lesson_id ) {

					$post_id = intval( $this->lesson_id );
					$object_type = __( 'lesson', 'woothemes-sensei' );
					$post_type = 'lesson';

				} elseif( $this->course_id ) {

					$post_id = intval( $this->course_id );
					$object_type = __( 'course', 'woothemes-sensei' );
					$post_type = 'course';

				}

				if( 'complete' == $user_activity->comment_approved || 'graded' == $user_activity->comment_approved || 'passed' == $user_activity->comment_approved ) {

                    $status_html = '<span class="graded">' .__( 'Completed', 'woothemes-sensei' ) . '</span>';

				} else {

                    $status_html = '<span class="in-progress">' . __( 'In Progress', 'woothemes-sensei' ) . '</span>';

				}

                $title = Sensei_Learner::get_full_name( $user_activity->user_id );
				$a_title = sprintf( __( 'Edit &#8220;%s&#8221;' ), $title );

                /**
                 * sensei_learners_main_column_data filter
                 *
                 * This filter runs on the learner management screen for a specific course.
                 * It provides the learner row column details.
                 *
                 * @param array $columns{
                 *   type string $title
                 *   type string $date_started
                 *   type string $course_status (completed, started etc)
                 *   type html $action_buttons
                 * }
                 */
				$column_data = apply_filters( 'sensei_learners_main_column_data', array(
						'title' => '<strong><a class="row-title" href="' . admin_url( 'user-edit.php?user_id=' . $user_activity->user_id ) . '" title="' . esc_attr( $a_title ) . '">' . $title . '</a></strong>',
						'date_started' => get_comment_meta( $user_activity->comment_ID, 'start', true),
						'user_status' => $status_html,
						'actions' => '<a class="remove-learner button" data-user_id="' . $user_activity->user_id . '" data-post_id="' . $post_id . '" data-post_type="' . $post_type . '">' . sprintf( __( 'Remove from %1$s', 'woothemes-sensei' ), $object_type ) . '</a>'
							. '<a class="reset-learner button" data-user_id="' . $user_activity->user_id . '" data-post_id="' . $post_id . '" data-post_type="' . $post_type . '">' . sprintf( __( 'Reset progress', 'woothemes-sensei' ), $object_type ) . '</a>',
					), $item, $post_id, $post_type );

				break;

			case 'lessons' :
				$lesson_learners = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_learners_lesson_learners', array( 'post_id' => $item->ID, 'type' => 'sensei_lesson_status', 'status' => 'any' ) ) );
				$title = get_the_title( $item );
				$a_title = sprintf( __( 'Edit &#8220;%s&#8221;' ), $title );

				$grading_action = '';
				if ( get_post_meta( $item->ID, '_quiz_has_questions', true ) ) {
					$grading_action = ' <a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'sensei_grading', 'lesson_id' => $item->ID, 'course_id' => $this->course_id ), admin_url( 'admin.php' ) ) ) . '">' . __( 'Grading', 'woothemes-sensei' ) . '</a>';
				}

				$column_data = apply_filters( 'sensei_learners_main_column_data', array(
						'title' => '<strong><a class="row-title" href="' . admin_url( 'post.php?action=edit&post=' . $item->ID ) . '" title="' . esc_attr( $a_title ) . '">' . $title . '</a></strong>',
						'num_learners' => $lesson_learners,
						'updated' => $item->post_modified,
						'actions' => '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $item->ID, 'course_id' => $this->course_id, 'view' => 'learners' ), admin_url( 'admin.php' ) ) ) . '">' . __( 'Manage learners', 'woothemes-sensei' ) . '</a> ' . $grading_action,
					), $item, $this->course_id );
				break;

			case 'courses' :
			default:
                $course_learners = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_learners_course_learners', array( 'post_id' => $item->ID, 'type' => 'sensei_course_status', 'status' => 'any' ) ) );
				$title = get_the_title( $item );
				$a_title = sprintf( __( 'Edit &#8220;%s&#8221;' ), $title );

				$grading_action = '';
				if ( version_compare($wp_version, '4.1', '>=') ) {
					$grading_action = ' <a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'sensei_grading', 'course_id' => $item->ID ), admin_url( 'admin.php' ) ) ) . '">' . __( 'Grading', 'woothemes-sensei' ) . '</a>';
				}

				$column_data = apply_filters( 'sensei_learners_main_column_data', array(
						'title' => '<strong><a class="row-title" href="' . esc_url( add_query_arg( array( 'page' => 'sensei_learners', 'course_id' => $item->ID, 'view' => 'learners' ), admin_url( 'admin.php') ) )  . '" title="' . esc_attr( $a_title ) . '">' . $title . '</a></strong>',
						'num_learners' => $course_learners,
						'updated' => $item->post_modified,
						'actions' => '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $item->ID, 'view' => 'learners' ), admin_url( 'admin.php' ) ) ) . '">' . __( 'Manage learners', 'woothemes-sensei' ) . '</a> ' . $grading_action,
					), $item );

				break;
		} // switch

		return $column_data;
	}

	/**
	 * Return array of course
	 * @since  1.7.0
	 * @return array courses
	 */
	private function get_courses( $args ) {
		$course_args = array(
			'post_type' => 'course',
			'post_status' => 'publish',
			'posts_per_page' => $args['per_page'],
			'offset' => $args['offset'],
			'orderby' => $args['orderby'],
			'order' => $args['order'],
		);

		if( $args['category'] ) {
			$course_args['tax_query'][] = array(
				'taxonomy' => 'course-category',
				'field' => 'id',
				'terms' => $args['category'],
			);
		}

		if( $args['search'] ) {
			$course_args['s'] = $args['search'];
		}

		$courses_query = new WP_Query( apply_filters( 'sensei_learners_filter_courses', $course_args ) );

		$this->total_items = $courses_query->found_posts;
		return $courses_query->posts;
	} // End get_courses()

	/**
	 * Return array of lessons
	 * @since  1.7.0
	 * @return array lessons
	 */
	private function get_lessons( $args ) {
		$lesson_args = array(
			'post_type' => 'lesson',
			'post_status' => 'publish',
			'posts_per_page' => $args['per_page'],
			'offset' => $args['offset'],
			'orderby' => $args['orderby'],
			'order' => $args['order'],
		);

		if( $this->course_id ) {
			$lesson_args['meta_query'][] = array(
				'key' => '_lesson_course',
				'value' => $this->course_id,
			);
		}

		if( $args['search'] ) {
			$lesson_args['s'] = $args['search'];
		}

		$lessons_query = new WP_Query( apply_filters( 'sensei_learners_filter_lessons', $lesson_args ) );

		$this->total_items = $lessons_query->found_posts;
		return $lessons_query->posts;
	} // End get_lessons()

	/**
	 * Return array of learners
	 * @since  1.7.0
	 * @return array learners
	 */
	private function get_learners( $args ) {

		$user_ids = false;
		$post_id = 0;
		$activity = '';

		if( $this->lesson_id ) {
			$post_id = intval( $this->lesson_id );
			$activity = 'sensei_lesson_status';
		}
		elseif( $this->course_id ) {
			$post_id = intval( $this->course_id );
			$activity = 'sensei_course_status';
		}

		if( ! $post_id || ! $activity ) {
			$this->total_items = 0;
			return array();
		}

		$activity_args = array(
			'post_id' => $post_id,
			'type' => $activity,
			'status' => 'any',
			'number' => $args['per_page'],
			'offset' => $args['offset'],
			'orderby' => $args['orderby'],
			'order' => $args['order'],
			);

		// Searching users on statuses requires sub-selecting the statuses by user_ids
		if ( $args['search'] ) {
			$user_args = array(
				'search' => '*' . $args['search'] . '*',
				'fields' => 'ID'
			);
			// Filter for extending
			$user_args = apply_filters( 'sensei_learners_search_users', $user_args );
			if ( !empty( $user_args ) ) {
				$learners_search = new WP_User_Query( $user_args );
				$activity_args['user_id'] = $learners_search->get_results();
			}
		}

		$activity_args = apply_filters( 'sensei_learners_filter_users', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
		$total_learners = Sensei_Utils::sensei_check_for_activity( array_merge( $activity_args, array('count' => true, 'offset' => 0, 'number' => 0) ) );
		// Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
		if ( $total_learners < $activity_args['offset'] ) {
			$new_paged = floor( $total_learners / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];
		}
		$learners = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		// Need to always return an array, even with only 1 item
		if ( !is_array($learners) ) {
			$learners = array( $learners );
		}
		$this->total_items = $total_learners;
		return $learners;
	} // End get_learners()

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 * @since  1.6.0
	 * @return void
	 */
	public function no_items() {
		switch( $this->view ) {
			case 'learners' :
				$text = __( 'No learners found.', 'woothemes-sensei' );
				break;

			case 'lessons' :
				$text = __( 'No lessons found.', 'woothemes-sensei' );
				break;

			case 'courses':
			case 'default':
			default:
				$text = __( 'No courses found.', 'woothemes-sensei' );
				break;
		}
		echo apply_filters( 'sensei_learners_no_items_text', $text );
	} // End no_items()

	/**
	 * Output for table heading
	 * @since  1.6.0
	 * @return void
	 */
	public function data_table_header() {

		echo '<div class="learners-selects">';
		do_action( 'sensei_learners_before_dropdown_filters' );

		// Display Course Categories only on default view
		if( 'courses' == $this->view ) {

			$selected_cat = 0;
			if ( isset( $_GET['course_cat'] ) && '' != esc_html( $_GET['course_cat'] ) ) {
				$selected_cat = intval( $_GET['course_cat'] );
			}

			$cats = get_terms( 'course-category', array( 'hide_empty' => false ) );

			echo '<div class="select-box">' . "\n";

				echo '<select id="course-category-options" data-placeholder="' . __( 'Course Category', 'woothemes-sensei' ) . '" name="learners_course_cat" class="chosen_select widefat">' . "\n";

					echo '<option value="0">' . __( 'All Course Categories', 'woothemes-sensei' ) . '</option>' . "\n";

					foreach( $cats as $cat ) {
						echo '<option value="' . $cat->term_id . '"' . selected( $cat->term_id, $selected_cat, false ) . '>' . $cat->name . '</option>' . "\n";
					}

				echo '</select>' . "\n";

			echo '</div>' . "\n";
		}
		echo '</div><!-- /.learners-selects -->';

		$menu = array();
		// Have Course no Lesson
		if( $this->course_id && ! $this->lesson_id ) {

			$learners_class = $lessons_class = '';
			switch( $this->view ) {
				case 'learners':
					$learners_class = 'current';
				break;

				case 'lessons':
					$lessons_class = 'current';
				break;
			}

			$query_args = array(
				'page' => $this->page_slug,
				'course_id' => $this->course_id,
			);

			$learner_args = $lesson_args = $query_args;
			$learner_args['view'] = 'learners';
			$lesson_args['view'] = 'lessons';

			$menu['learners'] = '<a class="' . $learners_class . '" href="' . esc_url( add_query_arg( $learner_args, admin_url( 'admin.php' ) ) ) . '">' . __( 'Learners', 'woothemes-sensei' ) . '</a>';
			$menu['lessons'] = '<a class="' . $lessons_class . '" href="' . esc_url( add_query_arg( $lesson_args, admin_url( 'admin.php' ) ) ) . '">' . __( 'Lessons', 'woothemes-sensei' ) . '</a>';

		} 
		// Have Course and Lesson
		elseif( $this->course_id && $this->lesson_id ) {

			$query_args = array(
				'page' => $this->page_slug,
				'course_id' => $this->course_id,
				'view' => 'lessons'
			);

			$course = get_the_title( $this->course_id );

			$menu['back'] = '<a href="' . esc_url( add_query_arg( $query_args, admin_url( 'admin.php' ) ) ) . '">' . sprintf( __( '%1$sBack to %2$s%3$s', 'woothemes-sensei' ), '<em>&larr; ', $course, '</em>' ) . '</a>';
		}
		$menu = apply_filters( 'sensei_learners_sub_menu', $menu );
		if ( !empty($menu) ) {
			echo '<ul class="subsubsub">' . "\n";
			foreach ( $menu as $class => $item ) {
				$menu[ $class ] = "\t<li class='$class'>$item";
			}
			echo implode( " |</li>\n", $menu ) . "</li>\n";
			echo '</ul>' . "\n";
		}

	} // End data_table_header()

	/**
	 * Output for table footer
	 * @since  1.6.0
	 * @return void
	 */
	public function data_table_footer() {
		// Nothing right now
	} // End data_table_footer()

	/**
	 * Add learners (to Course or Lesson) box to bottom of table display
	 * @since  1.6.0
	 * @return void
	 */
	public function add_learners_box() {
		$post_type = '';
		$post_title = '';
		$form_post_type = '';
		$form_course_id = 0;
		$form_lesson_id = 0;
		if( $this->course_id && ! $this->lesson_id ) {
			$post_title = get_the_title( $this->course_id );
			$post_type = __( 'Course', 'woothemes-sensei' );
			$form_post_type = 'course';
			$form_course_id = $this->course_id;
		}
		elseif( $this->course_id && $this->lesson_id ) {
			$post_title = get_the_title( $this->lesson_id );
			$post_type = __( 'Lesson', 'woothemes-sensei' );
			$form_post_type = 'lesson';
			$form_course_id = $this->course_id;
			$form_lesson_id = $this->lesson_id;
			$course_title = get_the_title( $this->course_id );
		}
		if ( empty($form_post_type) ) {
			return;
		}
		?>
		<div class="postbox">
			<h3><span><?php printf( __( 'Add Learner to %1$s', 'woothemes-sensei' ), $post_type ); ?></span></h3>
			<div class="inside">
				<form name="add_learner" action="" method="post">
					<p>
						<select name="add_user_id" id="add_learner_search" multiple="multiple" style="min-width:300px;>
							<option value="0" selected="selected"><?php _e( 'Find learner', 'woothemes-sensei' ) ;?></option>
						</select>
						<?php if( 'lesson' == $form_post_type ) { ?>
							<label for="add_complete_lesson"><input type="checkbox" id="add_complete_lesson" name="add_complete_lesson"  value="yes" /> <?php _e( 'Complete lesson for learner', 'woothemes-sensei' ); ?></label>
						<?php } elseif( 'course' == $form_post_type ) { ?>
							<label for="add_complete_course"><input type="checkbox" id="add_complete_course" name="add_complete_course"  value="yes" /> <?php _e( 'Complete course for learner', 'woothemes-sensei' ); ?></label>
						<?php } ?>
						<br/>
						<span class="description"><?php _e( 'Search for a user by typing their name or username.', 'woothemes-sensei' ); ?></span>
					</p>
					<p><?php submit_button( sprintf( __( 'Add to \'%1$s\'', 'woothemes-sensei' ), $post_title ), 'primary', 'add_learner_submit', false, array() ); ?></p>
					<?php if( 'lesson' == $form_post_type ) { ?>
						<p><span class="description"><?php printf( __( 'Learner will also be added to the course \'%1$s\' if they are not already taking it.', 'woothemes-sensei' ), $course_title ); ?></span></p>
					<?php } ?>

					<input type="hidden" name="add_post_type" value="<?php echo $form_post_type; ?>" />
					<input type="hidden" name="add_course_id" value="<?php echo $form_course_id; ?>" />
					<input type="hidden" name="add_lesson_id" value="<?php echo $form_lesson_id; ?>" />
					<?php
						do_action( 'sensei_learners_add_learner_form' );
					?>
					<?php wp_nonce_field( 'add_learner_to_sensei', 'add_learner_nonce' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * The text for the search button
	 * @since  1.7.0
	 * @return string $text
	 */
	public function search_button( $text = '' ) {

		switch( $this->view ) {
			case 'learners':
				$text = __( 'Search Learners', 'woothemes-sensei' );
			break;

			case 'lessons':
				$text = __( 'Search Lessons', 'woothemes-sensei' );
			break;

			default:
				$text = __( 'Search Courses', 'woothemes-sensei' );
			break;
		}

		return $text;
	}

} // End Class

/**
 * Class WooThemes_Sensei_Learners_Main
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Learners_Main extends Sensei_Learners_Main {}
