<?php
/**
 * This file contains Sensei_Learners_Main class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
class Sensei_Learners_Main extends Sensei_List_Table {

	/**
	 * The course id of the current view.
	 *
	 * @var integer
	 */
	private $course_id;

	/**
	 * The lesson id of the current view.
	 *
	 * @var integer
	 */
	private $lesson_id;

	/**
	 * The current view of learner management. Possible values are 'lessons', 'courses' and 'learners'.
	 *
	 * @var string
	 */
	private $view;

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	private $page_slug;

	/**
	 * The enrollment status of the learners.
	 *
	 * @var string
	 */
	private $enrolment_status;

	/**
	 * Constructor
	 *
	 * @since  1.6.0
	 */
	public function __construct() {

		// phpcs:disable WordPress.Security.NonceVerification -- No data are modified.
		if ( isset( $_GET['course_id'] ) ) {
			$this->course_id = (int) $_GET['course_id'];
		} else {
			$this->course_id = 0;
		}

		if (
			$this->course_id
			&& (
				'course' !== get_post_type( $this->course_id )
				|| ! current_user_can( get_post_type_object( 'course' )->cap->edit_post, $this->course_id )
			)
		) {
			wp_die( esc_html__( 'Invalid course', 'sensei-lms' ), 404 );
		}

		if ( isset( $_GET['lesson_id'] ) ) {
			$this->lesson_id = (int) $_GET['lesson_id'];
		} else {
			$this->lesson_id = 0;
		}

		if (
			$this->lesson_id
			&& (
				'lesson' !== get_post_type( $this->lesson_id )
				|| ! current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $this->lesson_id )
			)
		) {
			wp_die( esc_html__( 'Invalid lesson', 'sensei-lms' ), 404 );
		}

		if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'courses', 'lessons', 'learners' ), true ) ) {
			$this->view = sanitize_text_field( wp_unslash( $_GET['view'] ) );
		} else {
			$this->view = 'courses';
		}

		$this->enrolment_status = 'all';
		if ( isset( $_GET['enrolment_status'] ) ) {
			$this->enrolment_status = sanitize_text_field( wp_unslash( $_GET['enrolment_status'] ) );

			$valid_enrolment_statuses = [ 'all', 'enrolled', 'unenrolled' ];

			if ( $this->manual_filter_visible() ) {
				$valid_enrolment_statuses[] = 'manual';
			}

			if ( ! in_array( $this->enrolment_status, $valid_enrolment_statuses, true ) ) {
				$this->enrolment_status = 'all';
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification

		// Viewing a single lesson always sets the view to Learners.
		if ( $this->lesson_id ) {
			$this->view = 'learners';
		}

		$this->page_slug = 'sensei_learners';

		// Load Parent token into constructor.
		parent::__construct( 'learners_main' );

		// Actions.
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_learners_extra', array( $this, 'add_learners_box' ) );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	}

	/**
	 * Course id getter.
	 *
	 * @return int The course id
	 */
	public function get_course_id() {
		return $this->course_id;
	}

	/**
	 * Lesson id getter.
	 *
	 * @return int The lesson id
	 */
	public function get_lesson_id() {
		return $this->lesson_id;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	public function get_columns() {

		switch ( $this->view ) {
			case 'learners':
				$columns = array(
					'title'            => __( 'Learner', 'sensei-lms' ),
					'date_started'     => __( 'Date Started', 'sensei-lms' ),
					'date_completed'   => __( 'Date Completed', 'sensei-lms' ),
					'user_status'      => __( 'Status', 'sensei-lms' ),
					'enrolment_status' => __( 'Enrollment', 'sensei-lms' ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title'        => __( 'Lesson', 'sensei-lms' ),
					'num_learners' => __( '# Learners', 'sensei-lms' ),
					'updated'      => __( 'Last Updated', 'sensei-lms' ),
				);
				break;

			case 'courses':
			default:
				$columns = array(
					'title'        => __( 'Course', 'sensei-lms' ),
					'num_learners' => __( '# Learners', 'sensei-lms' ),
					'updated'      => __( 'Last Updated', 'sensei-lms' ),
				);
				break;
		}
		$columns['actions'] = '';

		// Backwards compatible.
		if ( 'learners' === $this->view ) {
			$columns = apply_filters_deprecated(
				'sensei_learners_learners_columns',
				[ $columns, $this ],
				'3.0.0',
				'sensei_learners_default_columns'
			);
		}

		$columns = apply_filters( 'sensei_learners_default_columns', $columns, $this );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	public function get_sortable_columns() {

		switch ( $this->view ) {
			case 'learners':
				$columns = array(
					'title' => array( 'title', false ),
				);
				break;
			case 'lessons':
			default:
				$columns = array(
					'title'   => array( 'title', false ),
					'updated' => array( 'post_modified', false ),
				);
				break;
		}
		// Backwards compatible.
		if ( 'learners' === $this->view ) {
			$columns = apply_filters_deprecated(
				'sensei_learners_learners_columns_sortable',
				[ $columns, $this ],
				'3.0.0',
				'sensei_learners_default_columns_sortable'
			);
		}

		$columns = apply_filters( 'sensei_learners_default_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {

		// phpcs:disable WordPress.Security.NonceVerification -- No data are modified.
		// Handle orderby.
		$orderby = '';
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby_arg = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			if ( array_key_exists( $orderby_arg, $this->get_sortable_columns() ) ) {
				$orderby = $orderby_arg;
			}
		}

		// Handle order.
		$order = 'DESC';
		if ( ! empty( $_GET['order'] ) ) {
			$order = 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';
		}

		// Handle category selection.
		$category = false;
		if ( ! empty( $_GET['course_cat'] ) ) {
			$category = (int) $_GET['course_cat'];
		}

		// Handle search.
		$search = false;
		if ( ! empty( $_GET['s'] ) ) {
			$search = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged  = $this->get_pagenum();
		$offset = 0;
		if ( ! empty( $paged ) ) {
			$offset = $per_page * ( $paged - 1 );
		}

		switch ( $this->view ) {
			case 'learners':
				if ( empty( $orderby ) ) {
					$orderby = '';
				}
				$this->items = $this->get_learners( compact( 'per_page', 'offset', 'orderby', 'order', 'search' ) );

				break;

			case 'lessons':
				if ( empty( $orderby ) ) {
					$orderby = 'post_modified';
				}
				$this->items = $this->get_lessons( compact( 'per_page', 'offset', 'orderby', 'order', 'search' ) );

				break;

			default:
				if ( empty( $orderby ) ) {
					$orderby = 'post_modified';
				}
				$this->items = $this->get_courses( compact( 'per_page', 'offset', 'orderby', 'order', 'category', 'search' ) );

				break;
		}

		$total_items = $this->total_items;
		$total_pages = ceil( $total_items / $per_page );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'total_pages' => $total_pages,
				'per_page'    => $per_page,
			)
		);

	}

	/**
	 * Generates content for a single row of the table in the user management
	 * screen.
	 *
	 * @since  1.7.0
	 *
	 * @param object $item The current item.
	 *
	 * @return array Escaped column data.
	 */
	protected function get_row_data( $item ) {
		global $wp_version;

		if ( ! $item ) {
			return array(
				'title'        => esc_html__( 'No results found', 'sensei-lms' ),
				'num_learners' => '',
				'updated'      => '',
				'actions'      => '',
			);
		}

		$escaped_column_data = array();

		switch ( $this->view ) {
			case 'learners':
				// in this case the item passed in is actually the users activity on course of lesson.
				$user_activity = $item;
				$post_id       = false;
				$post_type     = false;
				$object_type   = false;

				if ( $this->lesson_id ) {

					$post_id     = intval( $this->lesson_id );
					$object_type = __( 'lesson', 'sensei-lms' );
					$post_type   = 'lesson';

				} elseif ( $this->course_id ) {

					$post_id     = intval( $this->course_id );
					$object_type = __( 'course', 'sensei-lms' );
					$post_type   = 'course';

				}

				if ( 'complete' === $user_activity->comment_approved || 'graded' === $user_activity->comment_approved || 'passed' === $user_activity->comment_approved ) {

					$progress_status_html =
						'<span class="graded">' .
							esc_html__( 'Completed', 'sensei-lms' ) .
						'</span>';

				} else {

					$progress_status_html =
						'<span class="in-progress">' .
							esc_html__( 'In Progress', 'sensei-lms' ) .
						'</span>';

				}

				$is_user_enrolled  = Sensei_Course::is_user_enrolled( $this->course_id, $user_activity->user_id );
				$course_enrolment  = Sensei_Course_Enrolment::get_course_instance( $this->course_id );
				$enrolment_results = $course_enrolment->get_enrolment_check_results( $user_activity->user_id );
				$provider_results  = [];

				if ( $enrolment_results ) {
					$provider_results = $enrolment_results->get_provider_results();
				}

				$enrolment_tooltip_html = '';

				if ( Sensei()->feature_flags->is_enabled( 'enrolment_provider_tooltip' ) ) {
					if ( ! empty( $provider_results ) ) {
						$enrolment_tooltip_html   = [];
						$enrolment_tooltip_html[] = '<ul class="enrolment-helper">';

						foreach ( $provider_results as $id => $result ) {
							$name = Sensei_Course_Enrolment_Manager::instance()->get_enrolment_provider_name_by_id( $id );
							if ( ! $name ) {
								$name = $id;
							}

							$item_class = 'does-not-provide-enrolment';
							if ( $result ) {
								$item_class = 'provides-enrolment';
							}

							$enrolment_tooltip_html[] =
								'<li class="' . esc_attr( $item_class ) . '">' .
									esc_html( $name ) .
								'</li>';
						}

						$enrolment_tooltip_html[] = '</ul>';
						$enrolment_tooltip_html   = implode( '', $enrolment_tooltip_html );
					} else {
						$enrolment_tooltip_html = esc_html__( 'No enrollment data was found.', 'sensei-lms' );
					}
				}

				if ( $is_user_enrolled ) {
					$enrolment_label             = __( 'Enrolled', 'sensei-lms' );
					$enrolment_label_extra_class = 'enrolled';
				} else {
					$enrolment_label             = __( 'Not Enrolled', 'sensei-lms' );
					$enrolment_label_extra_class = 'not-enrolled';
				}

				$enrolment_status_html =
					'<span class="sensei-tooltip ' . esc_attr( $enrolment_label_extra_class ) . '" data-tooltip="' . esc_attr( htmlentities( $enrolment_tooltip_html ) ) . '">' .
						esc_html( $enrolment_label ) .
					'</span>';

				$title = Sensei_Learner::get_full_name( $user_activity->user_id );
				// translators: Placeholder is the full name of the learner.
				$a_title              = sprintf( esc_html__( 'Edit &#8220;%s&#8221;', 'sensei-lms' ), esc_html( $title ) );
				$edit_start_date_form = $this->get_edit_start_date_form( $user_activity, $post_id, $post_type, $object_type );

				$actions     = [];
				$row_actions = [];

				$provider_ids_with_enrollment = implode( ',', array_keys( $provider_results, true, true ) );
				$providers_attr               = ! empty( $provider_ids_with_enrollment )
					? 'data-provider="' . $provider_ids_with_enrollment . '"'
					: '';

				if ( 'course' === $post_type ) {
					// Courses.
					if ( $is_user_enrolled ) {
						// Enrolled.
						$withdraw_action_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'             => 'sensei_learners',
									'view'             => 'learners',
									'learner_action'   => 'withdraw',
									'course_id'        => $this->course_id,
									'user_id'          => $user_activity->user_id,
									'enrolment_status' => $this->enrolment_status,
								),
								admin_url( 'admin.php' )
							),
							'sensei-learner-action-withdraw'
						);

						$row_actions[] =
							'<span class="delete">' .
								'<a class="learner-action delete" data-user-id="' . esc_attr( $user_activity->user_id ) . '" data-action="withdraw" ' . $providers_attr . ' href="' . esc_url( $withdraw_action_url ) . '">' .
									esc_html__( 'Remove Enrollment', 'sensei-lms' ) .
								'</a>' .
							'</span>';

						$row_actions[] =
							'<span class="delete">' .
								'<a class="learner-async-action delete" data-user-id="' . esc_attr( $user_activity->user_id ) . '" data-action="reset_progress" data-post-id="' . esc_attr( $post_id ) . '" data-post-type="' . esc_attr( $post_type ) . '">' .
									esc_html__( 'Reset Progress', 'sensei-lms' ) .
								'</a>' .
							'</span>';
					} else {
						// Not enrolled.
						$enrol_label            = esc_html__( 'Enroll', 'sensei-lms' );
						$enrol_data_action      = 'enrol';
						$restore_providers_attr = '';

						// Check if it's enrolled by some provider.
						if ( ! empty( $provider_ids_with_enrollment ) ) {
							$enrol_label            = esc_html__( 'Restore Enrollment', 'sensei-lms' );
							$enrol_data_action      = 'restore_enrollment';
							$restore_providers_attr = $providers_attr;
						}

						$enrol_action_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'             => 'sensei_learners',
									'view'             => 'learners',
									'learner_action'   => $enrol_data_action,
									'course_id'        => $this->course_id,
									'user_id'          => $user_activity->user_id,
									'enrolment_status' => $this->enrolment_status,
								),
								admin_url( 'admin.php' )
							),
							'sensei-learner-action-' . $enrol_data_action
						);

						$row_actions[] =
							'<span>' .
								'<a class="learner-action" data-user-id="' . esc_attr( $user_activity->user_id ) . '" data-action="' . $enrol_data_action . '" ' . $restore_providers_attr . ' href="' . esc_url( $enrol_action_url ) . '">' .
									$enrol_label .
								'</a>' .
							'</span>';

						$row_actions[] =
							'<span class="delete">' .
								'<a class="learner-async-action delete" data-user-id="' . esc_attr( $user_activity->user_id ) . '" data-action="remove_progress" data-post-id="' . esc_attr( $post_id ) . '" data-post-type="' . esc_attr( $post_type ) . '">' .
									esc_html__( 'Remove Progress', 'sensei-lms' ) .
								'</a>' .
							'</span>';
					}
				} else {
					// Lessons.
					$row_actions[] =
						'<span class="delete">' .
							'<a class="learner-async-action delete" data-user-id="' . esc_attr( $user_activity->user_id ) . '" data-action="reset_progress" data-post-id="' . esc_attr( $post_id ) . '" data-post-type="' . esc_attr( $post_type ) . '">' .
								esc_html__( 'Reset Progress', 'sensei-lms' ) .
							'</a>' .
						'</span>';

					$row_actions[] =
						'<span class="delete">' .
							'<a class="learner-async-action delete" data-user-id="' . esc_attr( $user_activity->user_id ) . '" data-action="remove_progress" data-post-id="' . esc_attr( $post_id ) . '" data-post-type="' . esc_attr( $post_type ) . '">' .
								esc_html__( 'Remove Progress', 'sensei-lms' ) .
							'</a>' .
						'</span>';
				}

				if ( $edit_start_date_form ) {
					$actions[] = $edit_start_date_form;
				}

				/**
				 * Filter sensei_learners_main_column_data
				 *
				 * This filter runs on the learner management screen for a specific course.
				 * It provides the learner row column details.
				 *
				 * @param array   $columns {
				 *   @type string   $title             Learner name.
				 *   @type string   $date_started      Course start date.
				 *   @type string   $date_completed    Course completion date (if completed).
				 *   @type string   $course_status     Course status (e.g. completed, started etc).
				 *   @type string   $enrolment_status  Enrolment status.
				 *   @type html     $action_buttons    Actions that can be taken for this learner.
				 * }
				 * @param object  $item       Current WP_Comment item.
				 * @param int     $post_id    Course ID.
				 * @param string  $post_type  Post type.
				 */
				$column_data = apply_filters(
					'sensei_learners_main_column_data',
					array(
						'title'            =>
							'<strong>' .
								'<a class="row-title" href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $user_activity->user_id ) ) . '" title="' . esc_attr( $a_title ) . '">' .
									esc_html( $title ) .
								'</a>' .
							'</strong>' .
							'<div class="row-actions">' .
								implode( ' | ', $row_actions ) .
							'</div>',
						'date_started'     => get_comment_meta( $user_activity->comment_ID, 'start', true ),
						'date_completed'   => ( 'complete' === $user_activity->comment_approved ) ? $user_activity->comment_date : '',
						'user_status'      => $progress_status_html,
						'enrolment_status' => $enrolment_status_html,
						'actions'          => implode( ' ', $actions ),
					),
					$item,
					$post_id,
					$post_type
				);

				$escaped_column_data = Sensei_Wp_Kses::wp_kses_array(
					$column_data,
					array(
						'a'     => array(
							'class'           => array(),
							'href'            => array(),
							'title'           => array(),
							'data-comment-id' => array(),
							'data-post-id'    => array(),
							'data-post-type'  => array(),
							'data-user-id'    => array(),
							'data-action'     => array(),
							'data-provider'   => array(),
						),
						// Explicitly allow form tag for WP.com.
						'form'  => array(
							'class' => array(),
						),
						'input' => array(
							'class' => array(),
							'type'  => array(),
							'value' => array(),
						),
					)
				);

				break;
			case 'lessons':
				$lesson_learners = Sensei_Utils::sensei_check_for_activity(
					apply_filters(
						'sensei_learners_lesson_learners',
						array(
							'post_id' => $item->ID,
							'type'    => 'sensei_lesson_status',
							'status'  => 'any',
						)
					)
				);
				$title           = get_the_title( $item );
				// translators: Placeholder is the item title.
				$a_title = sprintf( esc_html__( 'Edit &#8220;%s&#8221;', 'sensei-lms' ), esc_html( $title ) );

				$grading_action = '';
				if ( Sensei_Lesson::lesson_quiz_has_questions( $item->ID ) ) {
					$grading_action = ' <a class="button" href="' . esc_url(
						add_query_arg(
							array(
								'page'      => 'sensei_grading',
								'lesson_id' => $item->ID,
								'course_id' => $this->course_id,
							),
							admin_url( 'admin.php' )
						)
					) . '">' . esc_html__( 'Grading', 'sensei-lms' ) . '</a>';
				}

				$column_data = apply_filters(
					'sensei_learners_main_column_data',
					array(
						'title'        =>
							'<strong>' .
								'<a class="row-title" href="' . esc_url( admin_url( 'post.php?action=edit&post=' . $item->ID ) ) . '" title="' . esc_attr( $a_title ) . '">' .
									esc_html( $title ) .
								'</a>' .
							'</strong>',
						'num_learners' => esc_html( $lesson_learners ),
						'updated'      => esc_html( $item->post_modified ),
						'actions'      => '<a class="button" href="' . esc_url(
							add_query_arg(
								array(
									'page'      => $this->page_slug,
									'lesson_id' => $item->ID,
									'course_id' => $this->course_id,
									'view'      => 'learners',
								),
								admin_url( 'admin.php' )
							)
						) . '">' . esc_html__( 'Manage learners', 'sensei-lms' ) . '</a> ' . $grading_action,
					),
					$item,
					$this->course_id
				);

				$escaped_column_data = Sensei_Wp_Kses::wp_kses_array( $column_data );

				break;
			case 'courses':
			default:
				$course_learners = Sensei_Utils::sensei_check_for_activity(
					apply_filters(
						'sensei_learners_course_learners',
						array(
							'post_id' => $item->ID,
							'type'    => 'sensei_course_status',
							'status'  => 'any',
						)
					)
				);
				$title           = get_the_title( $item );
				// translators: Placeholder is the item title.
				$a_title = sprintf( esc_html__( 'Edit &#8220;%s&#8221;', 'sensei-lms' ), esc_html( $title ) );

				$grading_action = '';
				if ( version_compare( $wp_version, '4.1', '>=' ) ) {
					$grading_action = ' <a class="button" href="' . esc_url(
						add_query_arg(
							array(
								'page'      => 'sensei_grading',
								'course_id' => $item->ID,
							),
							admin_url( 'admin.php' )
						)
					) . '">' . esc_html__( 'Grading', 'sensei-lms' ) . '</a>';
				}

				$column_data = apply_filters(
					'sensei_learners_main_column_data',
					array(
						'title'        =>
							'<strong>' .
								'<a class="row-title" href="' . esc_url(
									add_query_arg(
										array(
											'page'      => 'sensei_learners',
											'course_id' => $item->ID,
											'view'      => 'learners',
										),
										admin_url( 'admin.php' )
									)
								) . '" title="' . esc_attr( $a_title ) . '">' .
									esc_html( $title ) .
								'</a>' .
							'</strong>',
						'num_learners' => esc_html( $course_learners ),
						'updated'      => esc_html( $item->post_modified ),
						'actions'      => '<a class="button" href="' . esc_url(
							add_query_arg(
								array(
									'page'      => $this->page_slug,
									'course_id' => $item->ID,
									'view'      => 'learners',
								),
								admin_url( 'admin.php' )
							)
						) . '">' . esc_html__( 'Manage learners', 'sensei-lms' ) . '</a> ' . $grading_action,
					),
					$item
				);

				$escaped_column_data = Sensei_Wp_Kses::wp_kses_array( $column_data );

				break;
		} // switch

		return $escaped_column_data;
	}

	/**
	 * Generates the edit start date form.
	 *
	 * @param WP_Comment $user_activity The sensei user activity.
	 * @param integer    $post_id       The post id.
	 * @param string     $post_type     The post type (lesson or course).
	 * @param string     $object_type   The object type.
	 *
	 * @return string The form.
	 */
	private function get_edit_start_date_form( $user_activity, $post_id, $post_type, $object_type ) {
		$comment_id   = $user_activity->comment_ID;
		$date_started = get_comment_meta( $comment_id, 'start', true );
		$form         = '<form class="edit-start-date">';
		$form        .= '<input class="edit-start-date-date-picker" type="text" value="' . esc_attr( $date_started ) . '">';
		$form        .= '<a class="edit-start-date-submit button" data-user-id="' . esc_attr( $user_activity->user_id ) . '" data-post-id="' . esc_attr( $post_id ) . '" data-post-type="' . esc_attr( $post_type ) . '" data-comment-id="' . esc_attr( $comment_id ) . '">' . sprintf( esc_html__( 'Edit Start Date', 'sensei-lms' ), esc_html( $object_type ) ) . '</a>';
		$form        .= '</form>';

		return $form;
	}

	/**
	 * Return array of course
	 *
	 * @since  1.7.0
	 *
	 * @param array $args Arguments to WP_Query.
	 *
	 * @return array courses
	 */
	private function get_courses( $args ) {
		$course_args = array(
			'post_type'      => 'course',
			'post_status'    => 'publish',
			'posts_per_page' => $args['per_page'],
			'offset'         => $args['offset'],
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
		);

		if ( $args['category'] ) {
			$course_args['tax_query'][] = array(
				'taxonomy' => 'course-category',
				'field'    => 'id',
				'terms'    => $args['category'],
			);
		}

		if ( $args['search'] ) {
			$course_args['s'] = $args['search'];
		}

		$courses_query = new WP_Query( apply_filters( 'sensei_learners_filter_courses', $course_args ) );

		$this->total_items = $courses_query->found_posts;
		return $courses_query->posts;
	}

	/**
	 * Return array of lessons.
	 *
	 * @since  1.7.0
	 *
	 * @param array $args Arguments to WP_Query.
	 *
	 * @return array lessons
	 */
	private function get_lessons( $args ) {
		$lesson_args = array(
			'post_type'      => 'lesson',
			'post_status'    => 'publish',
			'posts_per_page' => $args['per_page'],
			'offset'         => $args['offset'],
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
		);

		if ( $this->course_id ) {
			$lesson_args['meta_query'][] = array(
				'key'   => '_lesson_course',
				'value' => $this->course_id,
			);
		}

		if ( $args['search'] ) {
			$lesson_args['s'] = $args['search'];
		}

		$lessons_query = new WP_Query( apply_filters( 'sensei_learners_filter_lessons', $lesson_args ) );

		$this->total_items = $lessons_query->found_posts;
		return $lessons_query->posts;
	}

	/**
	 * Return array of learners
	 *
	 * @since  1.7.0
	 *
	 * @param array $args Arguments to comment query.
	 *
	 * @return array learners
	 */
	private function get_learners( $args ) {
		$post_id  = 0;
		$activity = '';

		if ( $this->lesson_id ) {
			$post_id  = intval( $this->lesson_id );
			$activity = 'sensei_lesson_status';
		} elseif ( $this->course_id ) {
			$post_id  = intval( $this->course_id );
			$activity = 'sensei_course_status';
		}

		if ( ! $post_id || ! $activity ) {
			$this->total_items = 0;
			return array();
		}

		$activity_args = array(
			'post_id' => $post_id,
			'type'    => $activity,
			'status'  => 'any',
			'number'  => $args['per_page'],
			'offset'  => $args['offset'],
			'orderby' => $args['orderby'],
			'order'   => $args['order'],
		);

		$user_ids = $this->filter_activities_by_users( $args['search'] );

		// No users where found.
		if ( is_array( $user_ids ) && empty( $user_ids ) ) {
			return [];
		}

		if ( false !== $user_ids ) {
			$activity_args['user_id'] = $user_ids;
		}

		$activity_args = apply_filters( 'sensei_learners_filter_users', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice.
		$total_learners = Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$activity_args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);
		// Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
		if ( $total_learners < $activity_args['offset'] ) {
			$new_paged               = floor( $total_learners / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];
		}
		$learners = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		// Need to always return an array, even with only 1 item.
		if ( ! is_array( $learners ) ) {
			$learners = array( $learners );
		}
		$this->total_items = $total_learners;
		return $learners;
	}

	/**
	 * Returns a list of user ids to filter sensei activities. If no filtering is required, false is returned.
	 *
	 * @param string $search The search string.
	 *
	 * @return array|bool An array of user ids or false if no filtering is required. If no users are found an empty
	 *                    array will be returned.
	 */
	private function filter_activities_by_users( $search ) {
		$user_args = [];

		if ( $search ) {
			$user_args = [ 'search' => '*' . $search . '*' ];
		}

		/**
		 * Allows user search arguments modification in learner management.
		 *
		 * @param array $user_args {
		 *     @type string 'search' The search argument as used in WP_User_Query.
		 * }
		 */
		$user_args = apply_filters( 'sensei_learners_search_users', $user_args );

		if ( in_array( $this->enrolment_status, [ 'enrolled', 'unenrolled', 'manual' ], true ) ) {
			$enrolled_users = Sensei_Course_Enrolment::get_course_instance( $this->course_id )->get_enrolled_user_ids();

			if ( 'manual' === $this->enrolment_status ) {
				$enrolled_users = array_filter( $enrolled_users, [ $this, 'is_manually_enrolled' ] );
			}

			if ( in_array( $this->enrolment_status, [ 'enrolled', 'manual' ], true ) ) {
				if ( empty( $enrolled_users ) ) {
					$enrolled_users = [ -1 ];
				}

				$user_args['include'] = $enrolled_users;
			} else {
				$user_args['exclude'] = $enrolled_users;
			}
		}

		if ( ! empty( $user_args ) ) {
			$user_args['fields'] = 'ID';

			return ( new WP_User_Query( $user_args ) )->get_results();
		}

		return false;
	}

	/**
	 * Check if the user's enrollment is provided by the manual provider.
	 *
	 * @param integer $user_id The user id.
	 * @return bool The manual enrollment status.
	 */
	private function is_manually_enrolled( $user_id ) {
		$enrolment_manager         = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment_provider = $enrolment_manager->get_manual_enrolment_provider();

		return $manual_enrolment_provider->is_enrolled( $user_id, $this->course_id );
	}

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function no_items() {
		switch ( $this->view ) {
			case 'learners':
				$text = __( 'No learners found.', 'sensei-lms' );
				break;

			case 'lessons':
				$text = __( 'No lessons found.', 'sensei-lms' );
				break;

			case 'courses':
			case 'default':
			default:
				$text = __( 'No courses found.', 'sensei-lms' );
				break;
		}
		echo wp_kses_post( apply_filters( 'sensei_learners_no_items_text', $text ) );
	}

	/**
	 * Output for table heading
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function data_table_header() {

		echo '<div class="learners-selects">';
		do_action( 'sensei_learners_before_dropdown_filters' );

		// Display Course Categories only on default view.
		if ( 'courses' === $this->view ) {

			$selected_cat = 0;
			// phpcs:disable WordPress.Security.NonceVerification -- No data are modified.
			if ( isset( $_GET['course_cat'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['course_cat'] ) ) ) {
				$selected_cat = (int) $_GET['course_cat'];
			}
			// phpcs:enable

			$cats = get_terms( 'course-category', array( 'hide_empty' => false ) );

			echo '<div class="select-box">' . "\n";

				echo '<select id="course-category-options" data-placeholder="' . esc_attr__( 'Course Category', 'sensei-lms' ) . '" name="learners_course_cat" class="chosen_select widefat">' . "\n";

					echo '<option value="0">' . esc_html__( 'All Course Categories', 'sensei-lms' ) . '</option>' . "\n";

			foreach ( $cats as $cat ) {
				echo '<option value="' . esc_attr( $cat->term_id ) . '"' . selected( $cat->term_id, $selected_cat, false ) . '>' . esc_html( $cat->name ) . '</option>' . "\n";
			}

				echo '</select>' . "\n";

			echo '</div>' . "\n";
		}
		echo '</div><!-- /.learners-selects -->';

		$menu = array();

		if ( $this->course_id && ! $this->lesson_id ) {

			$menu['learners']            = $this->learners_link( 'all' );
			$menu['enrolled-learners']   = $this->learners_link( 'enrolled' );
			$menu['unenrolled-learners'] = $this->learners_link( 'unenrolled' );

			if ( $this->manual_filter_visible() ) {
				$menu['manually-enrolled-learners'] = $this->learners_link( 'manual' );
			}

			$menu['lessons'] = $this->lessons_link();

		} elseif ( $this->course_id && $this->lesson_id ) {

			$query_args = array(
				'page'      => $this->page_slug,
				'course_id' => $this->course_id,
				'view'      => 'lessons',
			);

			$course = get_the_title( $this->course_id );

			$menu['back'] = '<a href="'
				. esc_url( add_query_arg( $query_args, admin_url( 'admin.php' ) ) )
				. '"><em>&larr; '
				// translators: Placeholder is the Course title.
				. esc_html( sprintf( __( 'Back to %s', 'sensei-lms' ), $course ) )
				. '</em></a>';
		}

		$menu = apply_filters( 'sensei_learners_sub_menu', $menu );

		if ( ! empty( $menu ) ) {
			echo '<ul class="subsubsub">' . "\n";
			foreach ( $menu as $class => $item ) {
				$menu[ $class ] = "\t<li class='$class'>$item";
			}
			echo wp_kses_post( implode( " |</li>\n", $menu ) ) . "</li>\n";
			echo '</ul>' . "\n";
		}
	}

	/**
	 * Constructs the learner anchor elements in learner management.
	 *
	 * @param string $enrolment_status The enrolment status.
	 *
	 * @return string The element
	 */
	private function learners_link( $enrolment_status ) {
		$query_args = array(
			'page'             => $this->page_slug,
			'course_id'        => $this->course_id,
			'view'             => 'learners',
			'enrolment_status' => $enrolment_status,
		);

		$is_selected = 'learners' === $this->view && $enrolment_status === $this->enrolment_status;
		$url         = add_query_arg( $query_args, admin_url( 'admin.php' ) );
		$link_title  = false;

		switch ( $enrolment_status ) {
			case 'enrolled':
				$link_title = esc_html__( 'Enrolled Learners', 'sensei-lms' );
				break;
			case 'unenrolled':
				$link_title = esc_html__( 'Unenrolled Learners', 'sensei-lms' );
				break;
			case 'manual':
				$link_title = esc_html__( 'Manually Enrolled Learners', 'sensei-lms' );
				break;
			case 'all':
				$link_title = esc_html__( 'All Learners', 'sensei-lms' );
				break;
		}

		if ( ! $link_title ) {
			return '';
		}

		return '<a ' . ( $is_selected ? 'class="current"' : '' ) . ' href="' . esc_url( $url ) . '">' . $link_title . '</a>';
	}

	/**
	 * Constructs the 'Lessons' anchor element in learner management.
	 *
	 * @return string The element
	 */
	private function lessons_link() {
		$query_args = array(
			'page'      => $this->page_slug,
			'course_id' => $this->course_id,
			'view'      => 'lessons',
		);

		$url = add_query_arg( $query_args, admin_url( 'admin.php' ) );

		return '<a ' . ( 'lessons' === $this->view ? 'class="current"' : '' ) . ' href="' . esc_url( $url ) . '">' . esc_html__( 'Lessons', 'sensei-lms' ) . '</a>';
	}

	/**
	 * Output for table footer
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function data_table_footer() {
		_deprecated_function( __METHOD__, '3.0.0' );
	}

	/**
	 * Add learners (to Course or Lesson) box to bottom of table display
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function add_learners_box() {
		$manual_provider = Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();
		if ( ! $manual_provider ) {
			return;
		}

		$post_type      = '';
		$post_title     = '';
		$form_post_type = '';
		$form_course_id = 0;
		$form_lesson_id = 0;
		if ( $this->course_id && ! $this->lesson_id ) {
			$post_title     = get_the_title( $this->course_id );
			$post_type      = __( 'Course', 'sensei-lms' );
			$form_post_type = 'course';
			$form_course_id = $this->course_id;
		} elseif ( $this->course_id && $this->lesson_id ) {
			$post_title     = get_the_title( $this->lesson_id );
			$post_type      = __( 'Lesson', 'sensei-lms' );
			$form_post_type = 'lesson';
			$form_course_id = $this->course_id;
			$form_lesson_id = $this->lesson_id;
			$course_title   = get_the_title( $this->course_id );
		}
		if ( empty( $form_post_type ) ) {
			return;
		}
		?>
		<div class="postbox">
			<h2 class="postbox-title">
				<?php
				// translators: Placeholder is the post type.
				printf( esc_html__( 'Add Learner to %1$s', 'sensei-lms' ), esc_html( $post_type ) );
				?>
			</h2>
			<div class="inside">
				<form name="add_learner" action="" method="post">
					<p>
						<select name="add_user_id[]" id="add_learner_search" multiple="multiple" style="min-width:300px;">
						</select>
						<?php if ( 'lesson' === $form_post_type ) { ?>
							<label for="add_complete_lesson"><input type="checkbox" id="add_complete_lesson" name="add_complete_lesson"  value="yes" /> <?php esc_html_e( 'Complete lesson for learner', 'sensei-lms' ); ?></label>
						<?php } elseif ( 'course' === $form_post_type ) { ?>
							<label for="add_complete_course"><input type="checkbox" id="add_complete_course" name="add_complete_course"  value="yes" /> <?php esc_html_e( 'Complete course for learner', 'sensei-lms' ); ?></label>
						<?php } ?>
						<br/>
						<span class="description"><?php esc_html_e( 'Search for a user by typing their name or username.', 'sensei-lms' ); ?></span>
					</p>
					<p>
						<?php
						// translators: Placeholder is the post title.
						submit_button( sprintf( __( 'Add to \'%1$s\'', 'sensei-lms' ), $post_title ), 'primary', 'add_learner_submit', false, array() );
						?>
					</p>
					<?php if ( 'lesson' === $form_post_type && isset( $course_title ) ) { ?>
						<p><span class="description">
							<?php
							// translators: Placeholder is the course title.
							printf( esc_html__( 'Learner will also be added to the course \'%1$s\' if they are not already taking it.', 'sensei-lms' ), esc_html( $course_title ) );
							?>
						</span></p>
					<?php } ?>

					<input type="hidden" name="add_post_type" value="<?php echo esc_attr( $form_post_type ); ?>" />
					<input type="hidden" name="add_course_id" value="<?php echo esc_attr( $form_course_id ); ?>" />
					<input type="hidden" name="add_lesson_id" value="<?php echo esc_attr( $form_lesson_id ); ?>" />
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
	 *
	 * @since  1.7.0
	 * @return string $text
	 */
	public function search_button() {

		switch ( $this->view ) {
			case 'learners':
				$text = __( 'Search Learners', 'sensei-lms' );
				break;

			case 'lessons':
				$text = __( 'Search Lessons', 'sensei-lms' );
				break;

			default:
				$text = __( 'Search Courses', 'sensei-lms' );
				break;
		}

		return $text;
	}

	/**
	 * Helper method which calculates if the 'Manually Enrolled Learners' filter should be displayed.
	 *
	 * @return bool
	 * @throws Exception If the providers weren't initialized yet.
	 */
	private function manual_filter_visible() {
		$manual_provider = Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();
		$all_providers   = Sensei_Course_Enrolment_Manager::instance()->get_all_enrolment_providers();

		return $manual_provider instanceof Sensei_Course_Manual_Enrolment_Provider && count( $all_providers ) > 1;
	}
}

/**
 * Class WooThemes_Sensei_Learners_Main
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Learners_Main extends Sensei_Learners_Main {} //phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
