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
	 * Sensei_Learners_Admin_Main_View constructor.
	 *
	 * @param Sensei_Learners_Admin_Bulk_Actions_Controller $controller         The controller.
	 * @param Sensei_Learner_Management                     $learner_management The learner management.
	 */
	public function __construct( $controller, $learner_management ) {
		$this->controller         = $controller;
		$this->name               = $controller->get_name();
		$this->page_slug          = $controller->get_page_slug();
		$this->query_args         = $this->parse_query_args();
		$this->learner_management = $learner_management;
		$this->page_slug          = 'sensei_learner_admin';

		parent::__construct( $this->page_slug );

		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
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
			'cb'         => '<label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">',
			'learner'    => __( 'Learner', 'sensei-lms' ),
			'progress'   => __( 'Course Progress', 'sensei-lms' ),
			'enrolments' => __( 'Enrollments', 'sensei-lms' ),
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
				'cb'         => '',
				'learner'    => esc_html__( 'No results found', 'sensei-lms' ),
				'progress'   => '',
				'enrolments' => '',
			);
		} else {
			$learner  = $item;
			$courses  = $this->get_learner_courses_html( $item->course_statuses );
			$row_data = array(
				'cb'         => '<label class="screen-reader-text" for="cb-select-all-1">Select All</label><input type="checkbox" name="user_id" value="' . esc_attr( $learner->user_id ) . '" class="sensei_user_select_id">',
				'learner'    => $this->get_learner_html( $learner ),
				'progress'   => $courses,
				'enrolments' => get_term_field( 'count', Sensei_Learner::get_learner_term( $learner->user_id ) ),
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
							'class'          => array(),
							'data-course-id' => array(),
							'href'           => array(),
							'title'          => array(),
						),
						'input' => array(
							'class' => array(),
							'name'  => array(),
							'type'  => array(),
							'value' => array(),
						),
						// Explicitly allow label tag for WP.com.
						'label' => array(
							'class' => array(),
							'for'   => array(),
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
		$login = $learner->user_login;
		$title = Sensei_Learner::get_full_name( $learner->user_id );
		// translators: Placeholder is the full name of the learner.
		$a_title = sprintf( esc_html__( 'Edit &#8220;%s&#8221;', 'sensei-lms' ), esc_html( $title ) );
		$html    = '<strong><a class="row-title" href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $learner->user_id ) ) . '" title="' . esc_attr( $a_title ) . '">' . esc_html( $login ) . '</a></strong>';
		$html   .= ' <span>(<em>' . esc_html( $title ) . '</em>, ' . esc_html( $learner->user_email ) . ')</span>';

		return $html;
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
		$text = __( 'No learners found.', 'sensei-lms' );
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

		<select id="<?php echo esc_attr( $select_id ); ?>" data-placeholder="<?php echo esc_attr( $select_label ); ?>" name="<?php echo esc_attr( $name ); ?>" class="sensei-course-select" style="width: 50%" <?php echo $multiple ? 'multiple="true"' : ''; ?>>
			<option value="0"><?php echo esc_html( $select_label ); ?></option>
			<?php
			foreach ( $courses as $course ) {
				echo '<option value="' . esc_attr( $course->ID ) . '"' . selected( $course->ID, $selected_course, false ) . '>' . esc_html( $course->post_title ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 * Helper method to display the bulk action form in the modal.
	 *
	 * @param array $courses The courses options.
	 */
	private function render_bulk_actions_form( $courses ) {
		$label = __( 'Select Course(s)', 'sensei-lms' );

		?>
		<form id="bulk-learner-actions-form" action="" method="post">
		<label for="bulk-action-course-select" class="screen-reader-text"><?php echo esc_html( $label ); ?></label>
		<?php $this->courses_select( $courses, -1, 'bulk-action-course-select', 'course_id', $label, true ); ?>
		<input type="hidden" id="bulk-action-user-ids"  name="bulk_action_user_ids" value="">
		<input type="hidden" id="sensei-bulk-action"  name="sensei_bulk_action" value="">
		<input type="hidden" id="bulk-action-course-ids"  name="bulk_action_course_ids" value="">
		<?php wp_nonce_field( Sensei_Learners_Admin_Bulk_Actions_Controller::NONCE_SENSEI_BULK_LEARNER_ACTIONS, Sensei_Learners_Admin_Bulk_Actions_Controller::SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD ); ?>
		<button type="submit" id="bulk-learner-action-submit" class="button button-primary action sensei-stop-double-submission"><?php echo esc_html__( 'Apply', 'sensei-lms' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Helper method to display the bulk action selector.
	 */
	private function render_bulk_action_select_box() {
		$rendered     =
			'<select name="sensei_bulk_action_select" id="bulk-action-selector-top">' .
			'<option value="">' . esc_html__( 'Bulk Learner Actions', 'sensei-lms' ) . '</option>';
		$bulk_actions = $this->controller->get_known_bulk_actions();

		foreach ( $bulk_actions as $value => $translation ) {
			$rendered .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $translation ) . '</option>';
		}

		return $rendered . '</select>';
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
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<div id="sensei-bulk-learner-actions-modal" style="display:none;">
					<?php $this->render_bulk_actions_form( $courses ); ?>
				</div>
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
				<button type="submit" id="sensei-bulk-learner-actions-modal-toggle" class="button button-primary action"><?php echo esc_html__( 'Select Courses', 'sensei-lms' ); ?></button>
			</div>
			<div class="alignleft actions">
				<form action="" method="get">
					<?php
					foreach ( $this->query_args as $name => $value ) {
						if ( 'filter_by_course_id' === $name || 'filter_type' === $name ) {
							continue;
						}
						echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
					}
					$this->courses_select( $courses, $selected_course, 'courses-select-filter', 'filter_by_course_id', __( 'Filter By Course', 'sensei-lms' ) );
					?>
					<button type="submit" id="filt" class="button action"><?php echo esc_html__( 'Filter', 'sensei-lms' ); ?></button>
				</form>

			</div>
		</div>
		<?php
	}

	/**
	 * Returns the search button text.
	 */
	public function search_button() {
		return __( 'Search Learners', 'sensei-lms' );
	}

	/**
	 * Helper method to display the content of the progress column of the table.
	 *
	 * @param array $courses The courses to be displayed.
	 *
	 * @return string The HTML for the column.
	 */
	private function get_learner_courses_html( $courses ) {
		if ( empty( $courses ) ) {
			return '0 ' . esc_html__( 'Courses', 'sensei-lms' ) . ' ' . esc_html__( 'In Progress', 'sensei-lms' );
		} else {
			$courses           = explode( ',', $courses );
			$course_arr        = array();
			$courses_total     = count( $courses );
			$courses_completed = 0;
			foreach ( $courses as $course_id ) {
				$splitted      = explode( '|', $course_id );
				$course_id     = absint( $splitted[0] );
				$course_status = $splitted[1];

				if ( 'c' === $course_status ) {
					$courses_completed++;
				}

				$course       = get_post( $course_id );
				$span_style   = 'c' === $course_status ? ' button-primary action' : ' action';
				$course_arr[] = '<a href="' . esc_url( $this->controller->get_learner_management_course_url( $course_id ) ) . '" class="button' . esc_attr( $span_style ) . '" data-course-id="' . esc_attr( $course_id ) . '">' . esc_html( $course->post_title ) . '</a>';
			}

			$html = $courses_total - $courses_completed . ' ' . esc_html__( 'Courses', 'sensei-lms' ) . ' ' . esc_html__( 'In Progress', 'sensei-lms' );
			if ( $courses_completed > 0 ) {
				$html .= ', ' . $courses_completed . ' ' . esc_html__( 'Completed', 'sensei-lms' );
			}
			$html   .= ' <a href="#" class="learner-course-overview-detail-btn">...<span>' .
				esc_html__( 'more', 'sensei-lms' ) . '</span></a><br/>';
			$courses = implode( '<br />', $course_arr );

			return $html . '<div class="learner-course-overview-detail" style="display:none">' . $courses . '</div>';
		}
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
