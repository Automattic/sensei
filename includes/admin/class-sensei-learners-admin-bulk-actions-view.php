<?php
/**
 * File containing the class Sensei_Learners_Admin_Bulk_Actions_View.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * This class handles is responsible for displaying the learner bulk actions page in the admin screens.
 */
class Sensei_Learners_Admin_Bulk_Actions_View extends Sensei_List_Table {

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	private $page_slug;

	/**
	 * The page name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The learner query arguments for this table.
	 *
	 * @var string
	 */
	private $query_args;

	/**
	 * The class which handles the bulk learner actions.
	 *
	 * @var Sensei_Learners_Admin_Bulk_Actions_Controller
	 */
	private $controller;

	/**
	 * The Sensei_Learner_Management object.
	 *
	 * @var Sensei_Learner_Management
	 */
	private $learner_management;

	/**
	 * The Sensei_Learner object with utility functions.
	 *
	 * @var Sensei_Learner
	 */
	private $learner;

	/**
	 * Sensei_Learners_Admin_Main_View constructor.
	 *
	 * @param Sensei_Learners_Admin_Bulk_Actions_Controller $controller         The controller.
	 * @param Sensei_Learner_Management                     $learner_management The learner management.
	 * @param Sensei_Learner                                $learner                       The learner utility class.
	 */
	public function __construct( $controller, $learner_management, $learner ) {
		$this->controller         = $controller;
		$this->learner_management = $learner_management;
		$this->learner            = $learner;
		$this->name               = $controller->get_name();
		$this->page_slug          = $controller->get_page_slug();
		$this->query_args         = $this->parse_query_args();
		$this->page_slug          = 'sensei_learner_admin';

		parent::__construct( $this->page_slug );

		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		remove_action( 'sensei_before_list_table', array( $this, 'table_search_form' ), 5 );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			echo '<div class="alignleft actions">';
		}
		parent::extra_tablenav( $which );

		if ( 'top' === $which ) {
			echo '</div>';
		}
	}

	/**
	 * Output search form for table.
	 */
	public function table_search_form() {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$this->search_box( apply_filters( 'sensei_list_table_search_button_text', __( 'Search Users', 'sensei-lms' ) ), 'search_id' );
	}

	/**
	 * Outputs the HTML before the main table.
	 */
	public function output_headers() {
		$link_back_to_lm = '<a href="' . esc_url( $this->learner_management->get_url() ) . '">' . esc_html( $this->learner_management->get_name() ) . '</a>';
		$subtitle        = '';

		if ( isset( $this->query_args['filter_by_course_id'] ) ) {
			$course = get_post( absint( $this->query_args['filter_by_course_id'] ) );
			if ( ! empty( $course ) ) {
				$subtitle .= '<h2>' . esc_html( $course->post_title ) . '</h2>';
			}
		}
		echo '<h1>' . wp_kses_post( $link_back_to_lm ) . ' | ' . esc_html( $this->name ) . '</h1>' . wp_kses_post( $subtitle );
	}

	/**
	 * Get the table columns.
	 *
	 * @see Sensei_List_Table,WP_List_Table
	 */
	public function get_columns() {
		$columns = array(
			'cb'                 => '<label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">',
			'learner'            => sprintf(
				// translators: placeholder is the total number of students.
				__( 'Students (%d)', 'sensei-lms' ),
				esc_html( $this->total_items )
			),
			'email'              => __( 'Email', 'sensei-lms' ),
			'progress'           => __( 'Enrolled Courses', 'sensei-lms' ),
			'last_activity_date' => __( 'Last Activity', 'sensei-lms' ),
			'actions'            => '',
		);

		return apply_filters( 'sensei_learners_admin_default_columns', $columns, $this );
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @see WP_List_Table
	 */
	public function get_sortable_columns() {
		$columns = array(
			'learner' => array( 'learner', false ),
		);
		return apply_filters( 'sensei_learner_admin_default_columns_sortable', $columns, $this );
	}

	/**
	 * Prepare the table items.
	 *
	 * @see WP_List_Table
	 */
	public function prepare_items() {
		$this->items = $this->get_learners( $this->query_args );

		$total_items = $this->total_items;
		$total_pages = ceil( $total_items / $this->query_args['per_page'] );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'total_pages' => $total_pages,
				'per_page'    => $this->query_args['per_page'],
			)
		);

	}

	/**
	 * Get the data for a table row.
	 *
	 * @param array $item The row's item.
	 *
	 * @see WP_List_Table
	 *
	 * @return array The row's data
	 * @throws Exception When learner term could not be retrieved.
	 */
	protected function get_row_data( $item ) {
		if ( ! $item ) {
			$row_data = array(
				'cb'                 => '',
				'learner'            => esc_html__( 'No results found', 'sensei-lms' ),
				'email'              => '',
				'progress'           => '',
				'last_activity_date' => '',
				'actions'            => '',
			);
		} else {
			$learner            = $item;
			$courses            = $this->get_learner_courses_html( $learner->user_id );
			$full_name          = esc_html( Sensei_Learner::get_full_name( $learner->user_id ) );
			$last_activity_date = __( 'N/A', 'sensei-lms' );
			if ( $item->last_activity_date ) {
				$last_activity_date = Sensei_Utils::format_last_activity_date( $item->last_activity_date );
			}
			$row_data = array(
				'cb'                 => '<label class="screen-reader-text">Select All</label><input type="checkbox" name="user_id" value="' . esc_attr( $learner->user_id ) . '" class="sensei_user_select_id">',
				'learner'            => $this->get_learner_html( $learner ),
				'email'              => $learner->user_email,
				'progress'           => $courses,
				'last_activity_date' => $last_activity_date,
				'actions'            => '<div class="student-action-menu" data-user-id="' . esc_attr( $learner->user_id ) .
					'" data-user-name="' . esc_attr( $learner->user_login ) . '" data-user-display-name="' . esc_attr( $full_name ) . '"></div>',
			);
		}

		/**
		 * Filter sensei_learner_admin_get_row_data, for adding/removing row data.
		 *
		 * @param array                                   $row_data The Row Data.
		 * @param mixed|object                            $item The Item (learner query row).
		 * @param Sensei_Learners_Admin_Bulk_Actions_View $view The View.
		 *
		 * @return array
		 */
		$row_data         = apply_filters( 'sensei_learner_admin_get_row_data', $row_data, $item, $this );
		$escaped_row_data = array();

		add_filter( 'safe_style_css', array( $this, 'get_allowed_css' ) );

		foreach ( $row_data as $key => $data ) {
			$escaped_row_data[ $key ] = wp_kses(
				$data,
				array_merge(
					wp_kses_allowed_html( 'post' ),
					array(
						'a'     => array(
							'class'          => true,
							'data-course-id' => true,
							'data-user-id'   => true,
							'data-nonce'     => true,
							'href'           => true,
							'title'          => true,
						),
						'input' => array(
							'class' => true,
							'name'  => true,
							'type'  => true,
							'value' => true,
						),
						// Explicitly allow label tag for WP.com.
						'label' => array(
							'class' => true,
							'for'   => true,
						),
						'div'   => array(
							'data-*' => true,
							'class'  => true,
						),
					)
				)
			);
		}

		remove_filter( 'safe_style_css', array( $this, 'get_allowed_css' ) );

		return $escaped_row_data;
	}

	/**
	 * Get the HTML for a learner that is displayed on each row.
	 *
	 * @param array $learner A learner as returned by prepare_items().
	 *
	 * @return string The HTML.
	 */
	private function get_learner_html( $learner ) {
		$full_name = Sensei_Learner::get_full_name( $learner->user_id );
		// translators: Placeholder is the item title/name.
		$a_title = sprintf( __( 'Edit &#8220;%s&#8221;', 'sensei-lms' ), $full_name );

		return '<strong><a class="row-title" href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $learner->user_id ) ) . '" title="' . esc_attr( $a_title ) . '">' . esc_html( $full_name ) . '</a></strong>';
	}

	/**
	 * Helper method to retrieve the learners from the DB.
	 *
	 * @param array $args The query args.
	 *
	 * @return array The learners.
	 */
	private function get_learners( $args ) {
		$query             = new Sensei_Db_Query_Learners( $args );
		$learners          = $query->get_all();
		$this->total_items = $query->total_items;
		return $learners;
	}


	/**
	 * Displays the message when no items are found.
	 *
	 * @see WP_List_Table
	 */
	public function no_items() {
		$course_id = (int) ( $this->query_args['filter_by_course_id'] ?? 0 );
		if ( 0 === $course_id ) {
			$text = __( 'No students found.', 'sensei-lms' );
		} else {
			$add_students_args = [
				'page'      => 'sensei_learners',
				'course_id' => $course_id,
				'view'      => 'learners',
			];

			$message = __( 'This course doesn\'t have any students yet, you can add them below.', 'sensei-lms' );
			$button  = '<a class="button button-primary" href="' . esc_url( add_query_arg( $add_students_args, admin_url( 'admin.php' ) ) ) . '">' . __( 'Add Students', 'sensei-lms' ) . '</a>';
			$text    = '<div class="sensei-students__call-to-action"><div>' . $message . '</div><div>' . $button . '</div></div>';
		}

		echo wp_kses_post( apply_filters( 'sensei_learners_no_items_text', $text ) );
	}

	/**
	 * Helper method to display a select element which contain courses.
	 *
	 * @param array   $courses         The courses options.
	 * @param integer $selected_course The selected course.
	 * @param string  $select_id       The id of the element.
	 * @param string  $name            The name of the element.
	 * @param string  $select_label    The label of the element.
	 * @param bool    $multiple        Whether multiple selections are allowed.
	 */
	private function courses_select( $courses, $selected_course, $select_id = 'course-select', $name = 'course_id', $select_label = null, $multiple = false ) {
		if ( null === $select_label ) {
			$select_label = __( 'Select Course', 'sensei-lms' );
		}
		?>

		<select id="<?php echo esc_attr( $select_id ); ?>" data-placeholder="<?php echo esc_attr( $select_label ); ?>" name="<?php echo esc_attr( $name ); ?>" class="sensei-student-bulk-actions__placeholder-dropdown sensei-course-select" <?php echo $multiple ? 'multiple="true"' : ''; ?>>
			<option value="0"><?php echo esc_html( $select_label ); ?></option>
			<?php
			foreach ( $courses as $course ) {
				$option_label = empty( $course->post_title )
					? __( '(no title)', 'sensei-lms' ) . ' ID: ' . $course->ID
					: $course->post_title;

				echo '<option value="' . esc_attr( $course->ID ) . '"' . selected( $course->ID, $selected_course, false ) . '>' . esc_html( $option_label ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 * Helper method to display the bulk action selector.
	 */
	private function render_bulk_action_select_box() {
		?>
		<select id="bulk-action-selector-top" name="sensei_bulk_action_select" class="sensei-student-bulk-actions__placeholder-dropdown sensei-bulk-action-select">
			<option value="0"><?php echo esc_html( __( 'Select Bulk Actions', 'sensei-lms' ) ); ?></option>
			<?php
			foreach ( $this->controller->get_known_bulk_actions() as $value => $translation ) {
				echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $translation ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 * Helper method to display the controls of bulk actions.
	 */
	public function data_table_header() {

		$courses         = Sensei_Course::get_all_courses();
		$selected_course = 0;

		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used to filter courses.
		if ( isset( $_GET['filter_by_course_id'] ) && '' !== esc_html( sanitize_text_field( wp_unslash( $_GET['filter_by_course_id'] ) ) ) ) {
			$selected_course = (int) $_GET['filter_by_course_id']; // phpcs:ignore WordPress.Security.NonceVerification
		}
		?>
		<div class="sensei-student-bulk-actions__wrapper">
			<div class="alignleft bulkactions sensei-student-bulk-actions__container">
				<div class="sensei-student-bulk-actions__filters">
					<div class="sensei-student-bulk-actions__bulk_actions_container">
						<?php
						echo wp_kses(
							$this->render_bulk_action_select_box(),
							array(
								'option' => array(
									'value' => array(),
								),
								'select' => array(
									'id'   => array(),
									'name' => array(),
								),
							)
						);
						?>
						<div class="sensei-student-bulk-actions__button">
							<button type="button" class="button components-button button-primary sensei-student-bulk-actions__button" disabled><?php echo esc_html__( 'Select Courses', 'sensei-lms' ); ?></button>
						</div>
					</div>
					<div class="alignleft actions">
						<?php
						$exclude_query_args = [ 'filter_by_course_id', 'filter_type', 'page', 'post_type' ];
						foreach ( $this->query_args as $name => $value ) {
							if ( in_array( $name, $exclude_query_args, true ) ) {
								continue;
							}
							echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
						}
						$this->courses_select( $courses, $selected_course, 'courses-select-filter', 'filter_by_course_id', __( 'Filter By Course', 'sensei-lms' ) );
						?>
						<button type="submit" id="filt" class="button action"><?php echo esc_html__( 'Filter', 'sensei-lms' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns the search button text.
	 */
	public function search_button() {
		return __( 'Search Students', 'sensei-lms' );
	}

	/**
	 * Helper method to display the content of the enrolled courses' column of the table.
	 *
	 * @param int $user_id  The user id to display their enrolled courses.
	 *
	 * @return string The HTML for the column.
	 */
	private function get_learner_courses_html( $user_id ) {
		$base_query_args = [ 'posts_per_page' => 3 ];
		$query           = $this->learner->get_enrolled_courses_query( $user_id, $base_query_args );
		$courses         = $query->posts;

		if ( empty( $courses ) ) {
			return __( 'N/A', 'sensei-lms' );
		}
		$courses_total = $this->learner->get_enrolled_courses_count_query( $user_id );
		$visible_count = 3;
		$html_items    = [];
		$more_button   = '';

		foreach ( $courses as $course ) {
			$html_items[] = '<a href="' . esc_url( $this->controller->get_learner_management_course_url( $course->ID ) ) .
				'" class="sensei-students__enrolled-course" data-course-id="' . esc_attr( $course->ID ) . '">' .
					esc_html( $course->post_title ) .
				'</a>';
		}

		if ( $courses_total > $visible_count ) {
			$more_button = '<a href="#" data-nonce="' . wp_create_nonce( 'get_course_list' ) . '" data-user-id="' . esc_attr( $user_id ) . '" class="sensei-students__enrolled-courses-more-link">' .
				sprintf(
					/* translators: %d: the number of links to be displayed */
					esc_html__( '+%d more', 'sensei-lms' ),
					intval( $courses_total - $visible_count )
				) .
			'</a>';
		}

		$visible_courses = implode( '', array_slice( $html_items, 0, $visible_count ) );

		return $visible_courses . '<div class="sensei-students__enrolled-courses-detail"></div>' . $more_button;
	}

	/**
	 * Allows us to add `display: none` to course list.
	 *
	 * @access private
	 *
	 * @param array $styles List of styles that are allowed and considered safe.
	 * @return array
	 */
	public function get_allowed_css( $styles ) {
		$styles[] = 'display';

		return $styles;
	}

	/**
	 * Generates the query args from GET arguments
	 *
	 * @return array The query args
	 */
	private function parse_query_args() {
		// Handle orderby.
		$orderby = '';

		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used to order columns.
		if ( ! empty( $_GET['orderby'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$orderby_input = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			if ( array_key_exists( $orderby_input, $this->get_sortable_columns() ) ) {
				$orderby = $orderby_input;
			}
		}

		// Handle order.
		$order = 'DESC';
		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used to order columns.
		if ( ! empty( $_GET['order'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$order = 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';
		}

		// Handle search.
		$search = false;
		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used for searching.
		if ( ! empty( $_GET['s'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$search = sanitize_text_field( wp_unslash( ( $_GET['s'] ) ) );
		}

		$screen   = get_current_screen();
		$per_page = 0;
		if ( ! empty( $screen ) ) {
			$screen_option = $screen->get_option( 'per_page', 'option' );
			$per_page      = absint( get_user_meta( get_current_user_id(), $screen_option, true ) );
			if ( empty( $per_page ) || $per_page < 1 ) {
				// Get the default value if none is set.
				$per_page = absint( $screen->get_option( 'per_page', 'default' ) );
			}
		} else {
			$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
			$per_page = absint( apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' ) );
		}

		$paged  = $this->get_pagenum();
		$offset = 0;
		if ( ! empty( $paged ) ) {
			$offset = $per_page * ( $paged - 1 );
		}
		if ( empty( $orderby ) ) {
			$orderby = '';
		}

		$filter_by_course_id = 0;
		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used for filtering.
		if ( ! empty( $_GET['filter_by_course_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$filter_by_course_id = absint( $_GET['filter_by_course_id'] );
		}

		$filter_type = 'inc';
		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used for filtering.
		if ( ! empty( $_GET['filter_type'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$filter_type_input = sanitize_text_field( wp_unslash( $_GET['filter_type'] ) );
			$filter_type       = in_array( $filter_type_input, array( 'inc', 'exc' ), true ) ? $filter_type_input : 'inc';
		}
		$page = $this->page_slug;
		$view = $this->controller->get_view();
		$args = compact( 'page', 'view', 'per_page', 'offset', 'orderby', 'order', 'search', 'filter_by_course_id', 'filter_type' );

		return $args;
	}
}
