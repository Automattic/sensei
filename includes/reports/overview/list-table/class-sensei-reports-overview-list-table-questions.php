<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Questions class.
 *
 * @package sensei
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

/**
 * Students overview list table class.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Questions extends Sensei_Reports_Overview_List_Table_Abstract
{

	/**
	 * Sensei reports courses service.
	 *
	 * @var Sensei_Reports_Overview_Service_Questions
	 */
	private $reports_overview_service_students;

	/**
	 * Constructor
	 *
	 * @param Sensei_Reports_Overview_Data_Provider_Interface $data_provider Report data provider.
	 * @param Sensei_Reports_Overview_Service_Questions        $reports_overview_service_students reports students service.
	 */
	public function __construct(Sensei_Reports_Overview_Data_Provider_Interface $data_provider, Sensei_Reports_Overview_Service_Questions $reports_overview_service_students)
	{
		// Load Parent token into constructor.
		parent::__construct('questions', $data_provider);

		$this->reports_overview_service_students = $reports_overview_service_students;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array The array of columns to use with the table
	 */
	public function get_columns()
	{

		if ($this->columns) {
			return $this->columns;
		}

		$user_ids = $this->get_all_item_ids();

		$columns = array(
			'title'             => sprintf(__('Student (%d)', 'sensei-lms'), count($user_ids)),
			...array_map(fn ($data) => $data["question"]->post_title, $this->get_questions())
		);


		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters('sensei_analysis_overview_users_columns', $columns, $this);
		$columns = apply_filters('sensei_analysis_overview_columns', $columns, $this);

		$this->columns = $columns;

		return $this->columns;
	}

	public function get_questions()
	{

		// Get the lessons from the course selected in the filter
		$lessons = Sensei()->course->course_lessons($this->get_course_filter_value(), array('publish', 'private'), 'ids', []);

		$quizzes = [];

		foreach ($lessons as $lesson) {

			array_push($quizzes, array("lesson" => $lesson, "id" => Sensei()->lesson->lesson_quizzes($lesson, true)));
		}

		$quizzes = array_merge($quizzes);


		$questions_by_quiz = [];


		foreach ($quizzes as $quiz) {

			$questions = Sensei_Utils::sensei_get_quiz_questions($quiz["id"]);

			foreach ($questions as $question) {

				array_push(
					$questions_by_quiz,
					array(
						"lesson" => $quiz["lesson"],
						"quiz" => $quiz["id"],
						"question" => $question

					)
				);
			}
		}


		$questions = array_merge($questions_by_quiz);

		return $questions;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array The array of columns to use with the table
	 */
	public function get_sortable_columns()
	{
		$columns = [
			'title'           => array('display_name', false),
		];

		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters('sensei_analysis_overview_users_columns_sortable', $columns, $this);
		$columns = apply_filters('sensei_analysis_overview_columns_sortable', $columns, $this);

		return $columns;
	}

	/**
	 * Generates the overall array for a single item in the display
	 *
	 * @param object $item The current item.
	 *
	 * @return array Report row data.
	 * @throws Exception If date-time conversion fails.
	 */
	protected function get_row_data($item)
	{


		$column_data = apply_filters(
			'sensei_analysis_overview_column_data',
			array(
				'title'             => $this->format_user_name($item->ID, $this->csv_output),
				...array_map(fn ($question) => $this->question_report_get_users_answer($question["lesson"], $question["question"]->ID, $item->ID), $this->get_questions())
			),
			$item,
			$this
		);

		$escaped_column_data = array();

		foreach ($column_data as $key => $data) {
			$escaped_column_data[$key] = wp_kses_post($data);
		}

		return $escaped_column_data;
	}

	protected function question_report_get_users_answer($lesson, $question_id, $user_id)
	{
		$answer = Sensei()->quiz->get_user_question_answer($lesson, $question_id, $user_id);
		if (is_array($answer)) {
			return "<ul><li>" . implode('</li><li>', $answer) . "</li></ul>";
		}
		return $answer;
	}

	/**
	 * The text for the search button.
	 *
	 * @return string
	 */
	public function search_button()
	{
		return __('Search Students', 'sensei-lms');
	}

	/**
	 * Return additional filters for current report.
	 *
	 * @return array
	 */
	protected function get_additional_filters(): array
	{
		return [
			'last_activity_date_from' => $this->get_start_date_and_time(),
			'last_activity_date_to'   => $this->get_end_date_and_time(),
		];
	}


	/**
	 * Format user name wrapping or not with a link.
	 *
	 * @param int  $user_id user's id.
	 * @param bool $use_raw_name Indicate if it should return the wrap the name with the student link.
	 *
	 * @return string Return the student full name (first_name+last_name) optionally wrapped by a link
	 */
	private function format_user_name($user_id, $use_raw_name)
	{

		$user_name = Sensei_Learner::get_full_name($user_id);

		if ($use_raw_name) {
			return $user_name;
		}

		$url = add_query_arg(
			array(
				'page'    => $this->page_slug,
				'user_id' => $user_id,
			),
			admin_url('admin.php')
		);

		return '<strong><a class="row-title" href="' . esc_url($url) . '">' . esc_html($user_name) . '</a></strong>';
	}

	public function print_column_header_titles() {

		$lessons = Sensei()->course->course_lessons($this->get_course_filter_value(), array('publish', 'private'), '', []);


		$lessonIDs = (array_count_values(array_column($this->get_questions(), 'lesson')));


		foreach ($lessonIDs as $lessonID => $count) {

			$title = $lessons[array_search($lessonID, array_column($lessons, "ID"))]->post_title;

			echo '<th colSpan='.$count.'>'.$title.'</th>';
		}

	}

	public function display() {
		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
	<thead>

		<tr>
			<th></th>
			<? $this->print_column_header_titles() ?>
	</tr>
	<tr>
		<?php $this->print_column_headers(); ?>
	</tr>
	</thead>

	<tbody id="the-list"
		<?php
		if ( $singular ) {
			echo " data-wp-lists='list:$singular'";
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
		<?php
		$this->display_tablenav( 'bottom' );
	}
}
