<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Admin Grading Overview Data Table in Sensei.
 *
 * @package Assessment
 * @author Automattic
 * @since 3.1.1
 */
class Sensei_Grading_Answers extends Sensei_List_Table {

	public $course_id;
	public $lesson_id;
	public $quiz_id;
	public $user_ids        = false;
	public $view            = 'all';
	public $page_slug       = 'sensei_grading';
	public $questions       = array();

	/**
	 * Constructor
	 *
	 * @since  3.1.1
	 */
	public function __construct( $args = null ) {

		$defaults = array(
			'course_id' => 0,
			'lesson_id' => 0,
			'quiz_id'   => 0,
		);
		$args             = wp_parse_args( $args, $defaults );

		$this->course_id  = intval( $args['course_id'] );
		$this->lesson_id  = intval( $args['lesson_id'] );
		$this->quiz_id    = intval( $args['quiz_id'] );

		// Load Parent token into constructor
		parent::__construct( 'grading_answer' );

		// Prepare questions
		$this->questions  = Sensei_Utils::sensei_get_quiz_questions( $this->quiz_id );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
	} // End __construct()

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  3.1.1
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		$columns = array(
			'title'       => __( 'Learner', 'sensei-lms' ),
			'user_grade'  => __( 'Grade', 'sensei-lms' ),
		);
		$question_count = 0;
		foreach ($this->questions as $question) {
			++$question_count;
			// translators: Placeholder is the question number.
			$columns['question'.$question_count] = sprintf( __( 'Question %d: ', 'sensei-lms' ), $question_count ) . wp_kses_post( apply_filters( 'sensei_question_title', $question->post_title ) );
		}

		$columns = apply_filters( 'sensei_grading_default_columns', $columns, $this );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  3.1.1
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {
		$columns = array(
			'title'       => array( 'title', false ),
			'user_grade'  => array( 'user_grade', false ),
			/*'question'    => array( 'question', false ),
			'answer'      => array( 'answer', false ),*/
		);
		$columns = apply_filters( 'sensei_grading_default_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare a user answer for display
	 *
	 * @since  3.1.1
	 * @return void
	 */
	public function get_answer($user_id, $question) {
		if ( ! isset( $user_id ) || ! isset( $question ) ) {
			return false;
		} else {
			$graded_count          = 0;
			$quiz_grade            = 0;
			$lesson_id             = $this->lesson_id;

			$question_id = $question->ID;

			$type      = false;
			$type_name = '';

			$type = Sensei()->question->get_question_type( $question_id );

			$question_answer_notes = Sensei()->quiz->get_user_question_feedback( $lesson_id, $question_id, $user_id );

			$question_grade_total = Sensei()->question->get_question_grade( $question_id );

			$right_answer        = get_post_meta( $question_id, '_question_right_answer', true );
			$user_answer_content = Sensei()->quiz->get_user_question_answer( $lesson_id, $question_id, $user_id );
			$type_name           = __( 'Multiple Choice', 'sensei-lms' );

			switch ( $type ) {
				case 'boolean':
					$type_name           = __( 'True/False', 'sensei-lms' );
					$right_answer        = ucfirst( $right_answer );
					$user_answer_content = ucfirst( $user_answer_content );
					$grade_type          = 'auto-grade';
					break;
				case 'multiple-choice':
					$type_name  = __( 'Multiple Choice', 'sensei-lms' );
					$grade_type = 'auto-grade';
					break;
				case 'gap-fill':
					$type_name = __( 'Gap Fill', 'sensei-lms' );

					$right_answer_array = explode( '||', $right_answer );
					if ( isset( $right_answer_array[0] ) ) {
						$gapfill_pre = $right_answer_array[0];
					} else {
						$gapfill_pre = ''; }
					if ( isset( $right_answer_array[1] ) ) {
						$gapfill_gap = $right_answer_array[1];
					} else {
						$gapfill_gap = ''; }
					if ( isset( $right_answer_array[2] ) ) {
						$gapfill_post = $right_answer_array[2];
					} else {
						$gapfill_post = ''; }

					if ( ! $user_answer_content ) {
						$user_answer_content = '______';
					}

					$right_answer        = $gapfill_pre . ' <span class="highlight">' . $gapfill_gap . '</span> ' . $gapfill_post;
					$user_answer_content = $gapfill_pre . ' <span class="highlight">' . $user_answer_content . '</span> ' . $gapfill_post;
					$grade_type          = 'auto-grade';

					break;
				case 'multi-line':
					$type_name  = __( 'Multi Line', 'sensei-lms' );
					$grade_type = 'manual-grade';
					break;
				case 'single-line':
					$type_name  = __( 'Single Line', 'sensei-lms' );
					$grade_type = 'manual-grade';
					break;
				case 'file-upload':
					$type_name  = __( 'File Upload', 'sensei-lms' );
					$grade_type = 'manual-grade';

					// Get uploaded file
					if ( $user_answer_content ) {
						$attachment_id    = $user_answer_content;
						$answer_media_url = $answer_media_filename = '';
						if ( 0 < intval( $attachment_id ) ) {
							$answer_media_url      = wp_get_attachment_url( $attachment_id );
							$answer_media_filename = basename( $answer_media_url );
							if ( $answer_media_url && $answer_media_filename ) {
								// translators: Placeholder %1$s is a link to the submitted file.
								$user_answer_content = sprintf( __( 'Submitted file: %1$s', 'sensei-lms' ), '<a href="' . esc_url( $answer_media_url ) . '" target="_blank">' . esc_html( $answer_media_filename ) . '</a>' );
							}
						}
					} else {
						$user_answer_content = '';
					}
					break;
				default:
					// Nothing
					break;
			}

			$question_answer = '';
			$user_answer_content = (array) $user_answer_content;
			foreach ( $user_answer_content as $_user_answer ) {

				if ( 'multi-line' === Sensei()->question->get_question_type( $question->ID ) ) {
					$is_plaintext = sanitize_text_field( $_user_answer ) == $_user_answer;
					if ( $is_plaintext ) {
						$_user_answer = nl2br( $_user_answer );
					}

					$_user_answer = htmlspecialchars_decode( $_user_answer );
				}

				$question_answer .= wp_kses_post( apply_filters( 'sensei_answer_text', $_user_answer ) ) . '<br>';
			}

			$quiz_grade_type = get_post_meta( $this->quiz_id, '_quiz_grade_type', true );
			// Don't auto-grade if "Grade quiz automatically" isn't selected in Quiz Settings,
			// regardless of question type.
			if ( 'manual' === $quiz_grade_type ) {
				$grade_type = 'manual-grade';
			}
			$user_question_grade = Sensei()->quiz->get_user_question_grade( $lesson_id, $question_id, $user_id );
			$graded_class        = 'ungraded';

			// Question with no grade value associated with it.
			if ( 0 === $question_grade_total ) {
				$grade_type          = 'zero-graded';
				$graded_class        = '';
				$user_question_grade = 0;
			} else {
				$user_right = intval( $user_question_grade ) > 0;
				// The user's grade will be 0 if they answered incorrectly.
				// Don't set a grade for questions that are part of an auto-graded quiz, but that must be manually graded.
				$user_wrong =
					( 'manual' === $quiz_grade_type && 0 === intval( $user_question_grade ) )
					|| ( 'auto' === $quiz_grade_type && 'manual-grade' !== $grade_type && 0 === intval( $user_question_grade ) );

				if ( $user_right ) {
					$graded_class        = 'user_right';
				} elseif ( $user_wrong ) {
					$graded_class        = 'user_wrong';
					$user_question_grade = 0;
				}
			}

			return '<div class="' . $graded_class . '">' . $question_answer . '</div>';
		}
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since  3.1.1
	 * @return void
	 */
	public function prepare_items() {
		global $wp_version;

		// Handle orderby
		$orderby = '';
		if ( ! empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			} // End If Statement
		}

		// Handle order
		$order = 'DESC';
		if ( ! empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

		// Handle search
		$search = false;
		if ( ! empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		} // End If Statement
		$this->search = $search;

		// Searching users on statuses requires sub-selecting the statuses by user_ids
		if ( $this->search ) {
			$user_args = array(
				'search' => '*' . $this->search . '*',
				'fields' => 'ID',
			);
			// Filter for extending
			$user_args = apply_filters( 'sensei_grading_search_users', $user_args );
			if ( ! empty( $user_args ) ) {
				$learners_search = new WP_User_Query( $user_args );
				// Store for reuse on counts
				$this->user_ids = $learners_search->get_results();
			}
		} // End If Statement

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged  = $this->get_pagenum();
		$offset = 0;
		if ( ! empty( $paged ) ) {
			$offset = $per_page * ( $paged - 1 );
		} // End If Statement

		$activity_args = array(
			'type'    => 'sensei_lesson_status',
			'number'  => $per_page,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
			'status'  => 'any',
		);

		if ( $this->lesson_id ) {
			$activity_args['post_id'] = $this->lesson_id;
		} elseif ( $this->course_id ) {
			// Currently not possible to restrict to a single Course, as that requires WP_Comment to support multiple
			// post_ids (i.e. every lesson within the Course), WP 4.1 ( https://core.trac.wordpress.org/changeset/29808 )
			if ( version_compare( $wp_version, '4.1', '>=' ) ) {
				$activity_args['post__in'] = Sensei()->course->course_lessons( $this->course_id, 'any', 'ids' );
			}
		}
		// Sub select to group of learners
		if ( $this->user_ids ) {
			$activity_args['user_id'] = (array) $this->user_ids;
		}
		// Restrict to a single Learner
		if ( $this->user_id ) {
			$activity_args['user_id'] = $this->user_id;
		}

		switch ( $this->view ) {
			case 'in-progress':
				$activity_args['status'] = 'in-progress';
				break;

			case 'ungraded':
				$activity_args['status'] = 'ungraded';
				break;

			case 'graded':
				$activity_args['status'] = array( 'graded', 'passed', 'failed' );
				break;

			case 'all':
			default:
				$activity_args['status'] = 'any';
				break;
		} // End switch

		$activity_args = apply_filters( 'sensei_grading_filter_statuses', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
		$total_statuses = Sensei_Utils::sensei_check_for_activity(
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
		if ( $total_statuses < $activity_args['offset'] ) {
			$new_paged               = floor( $total_statuses / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];
		}
		$statuses = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		// Need to always return an array, even with only 1 item
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}
		$this->total_items = $total_statuses;
		$this->items       = $statuses;

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
	 * Generates content for a single row of the table, overriding parent
	 *
	 * @since  3.1.1
	 * @param object $item The current item
	 */
	protected function get_row_data( $item ) {
		global $wp_version;

		$grade = '';
		if ( 'complete' == $item->comment_approved ) {
			$grade       = __( 'No Grade', 'sensei-lms' );
		} elseif ( 'graded' == $item->comment_approved ) {
			$grade       = get_comment_meta( $item->comment_ID, 'grade', true ) . '%';
		} elseif ( 'passed' == $item->comment_approved ) {
			$grade       = get_comment_meta( $item->comment_ID, 'grade', true ) . '%';
		} elseif ( 'failed' == $item->comment_approved ) {
			$grade       = get_comment_meta( $item->comment_ID, 'grade', true ) . '%';
		} elseif ( 'ungraded' == $item->comment_approved ) {
			$grade       = __( 'N/A', 'sensei-lms' );
		} else {
			$grade       = __( 'N/A', 'sensei-lms' );
		}

		$title = Sensei_Learner::get_full_name( $item->user_id );

		// QuizID to be deprecated
		$quiz_id   = get_post_meta( $item->comment_post_ID, '_lesson_quiz', true );
		$quiz_link = add_query_arg(
			array(
				'page'    => $this->page_slug,
				'user'    => $item->user_id,
				'quiz_id' => $quiz_id,
			),
			admin_url( 'admin.php' )
		);

		$course_id    = get_post_meta( $item->comment_post_ID, '_lesson_course', true );

		$column_filter  = array(
			'title'       => '<strong><a class="row-title" href="' . esc_url(
				add_query_arg(
					array(
						'page'    => $this->page_slug,
						'user_id' => $item->user_id,
					),
					admin_url( 'admin.php' )
				)
			) . '">' . esc_html( $title ) . '</a></strong>',
		);
		$question_count = 0;
		foreach ($this->questions as $question) {
			++$question_count;
			// translators: Placeholder is the question number.
			$column_filter['question'.$question_count]  = $this->get_answer( $item->user_id, $question );
		}

		$column_filter['user_grade']    = $grade;

		$column_data  = apply_filters(
			'sensei_grading_main_column_data',
			$column_filter,
			$item,
			$course_id
		);

		$escaped_column_data = array();

		foreach ( $column_data as $key => $data ) {
			$escaped_column_data[ $key ] = wp_kses_post( $data );
		}

		return $escaped_column_data;
	}

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function no_items() {

		esc_html_e( 'No submissions found.', 'sensei-lms' );

	} // End no_items()

	/**
	 * Output for table heading
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_header() {
		global  $wp_version;

		echo '<div class="grading-selects">';
		do_action( 'sensei_grading_before_dropdown_filters' );

		echo '<div class="select-box">' . "\n";

			echo '<select id="grading-course-options" name="grading_course" class="chosen_select widefat">' . "\n";

				echo wp_kses(
					Sensei()->grading->courses_drop_down_html( $this->course_id ),
					array(
						'option' => array(
							'selected' => array(),
							'value'    => array(),
						),
					)
				);

			echo '</select>' . "\n";

		echo '</div>' . "\n";

		echo '<div class="select-box">' . "\n";

			echo '<select id="grading-lesson-options" data-placeholder="&larr; ' . esc_attr__( 'Select a course', 'sensei-lms' ) . '" name="grading_lesson" class="chosen_select widefat">' . "\n";

				echo wp_kses(
					Sensei()->grading->lessons_drop_down_html( $this->course_id, $this->lesson_id ),
					array(
						'option' => array(
							'selected' => array(),
							'value'    => array(),
						),
					)
				);

			echo '</select>' . "\n";

		echo '</div>' . "\n";

		if ( $this->course_id && $this->lesson_id ) {
			$quiz_id          = get_post_meta( $this->lesson_id, '_lesson_quiz', true );
			$query_all_grades = add_query_arg( array( 'quiz_id' => $quiz_id, 'answers' => true ) );

			echo '<div class="select-box reset-filter">' . "\n";

				echo '<a class="button-secondary" href="' . esc_url( remove_query_arg( array( 'lesson_id', 'course_id' ) ) ) . '">' . esc_html__( 'Reset filter', 'sensei-lms' ) . '</a>' . "\n";
				echo '<a class="button-secondary" href="' . esc_url( $query_all_grades ) . '">' . esc_html__( 'Show All Grades', 'sensei-lms' ) . '</a>' . "\n";

			echo '</div>' . "\n";

		}

		echo '</div><!-- /.grading-selects -->';

	} // End data_table_header()

	/**
	 * Output for table footer
	 *
	 * @since  3.1.1
	 * @return void
	 */
	public function data_table_footer() {
		// Nothing right now
	} // End data_table_footer()


	/**
	 * Displays the table.
	 *
	 * @since 3.1.1
	 */
	public function display() {
		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
<div class="wp_list_table_grading_answers_wrapper">
	<table class="wp-list-table <?php echo esc_attr__( implode( ' ', $this->get_table_classes() ) ); ?>">
		<thead>
		<tr>
			<?php $this->print_column_headers(); ?>
		</tr>
		</thead>

		<tbody id="the-list"
			<?php
			if ( $singular ) {
				echo wp_kses_one_attr( " data-wp-lists='list:$singular'", 'tbody' );
			}
			?>
			>
			<?php $this->display_rows_or_placeholder(); ?>
		</tbody>

		<tfoot>
		<tr>
			<?php $this->print_column_headers( false ); ?>
		</tr>
		</tfoot>

	</table>
</div>
		<?php
		$this->display_tablenav( 'bottom' );
	}

} // End Class

/**
 * Class WooThems_Sensei_Grading_Main
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Grading_Answers extends Sensei_Grading_Main{}
