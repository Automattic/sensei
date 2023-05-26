<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Quiz Class
 *
 * All functionality pertaining to the quiz post type in Sensei.
 *
 * @package Assessment
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Quiz {

	/**
	 * The CPT token.
	 *
	 * @var string
	 */
	public $token;

	/**
	 * The CPT meta fields.
	 *
	 * @var string[]
	 */
	public $meta_fields;

	/**
	 * The main plugin filename.
	 *
	 * @deprecated 4.9.0 This attribute was never meant to be used. Added by mistake in `1f529be` and later made useless in `4f25fe5`.
	 * @var string
	 */
	public $file;

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param string $file Main plugin filename. Not used.
	 */
	public function __construct( $file = __FILE__ ) {
		$this->file        = $file;
		$this->token       = 'quiz';
		$this->meta_fields = array(
			'quiz_passmark',
			'quiz_lesson',
			'quiz_type',
			'quiz_grade_type',
			'pass_required',
			'enable_quiz_reset',
			'show_questions',
			'random_question_order',
		);
		add_filter( 'wp_insert_post_data', [ $this, 'set_quiz_author_on_create' ], 10, 4 );
		add_action( 'save_post', array( $this, 'update_after_lesson_change' ) );

		// Redirect if the lesson is protected.
		add_action( 'template_redirect', array( $this, 'redirect_if_lesson_is_protected' ) );

		// Listen for a page change.
		add_action( 'template_redirect', array( $this, 'page_change_listener' ) );

		// Listen to the reset button click.
		add_action( 'template_redirect', array( $this, 'reset_button_click_listener' ) );

		// Fire the complete quiz button submit for grading action.
		add_action( 'template_redirect', array( $this, 'user_quiz_submit_listener' ) );

		// Fire the save user answers quiz button click responder.
		add_action( 'template_redirect', array( $this, 'user_save_quiz_answers_listener' ) );

		// Fire the load global data function.
		add_action( 'sensei_single_quiz_content_inside_before', array( $this, 'load_global_quiz_data' ), 80 );

		add_action( 'template_redirect', array( $this, 'quiz_has_no_questions' ) );

		// Remove post when lesson is permanently deleted.
		add_action( 'delete_post', array( $this, 'maybe_delete_quiz' ) );

		add_filter( 'body_class', [ $this, 'add_quiz_blocks_class' ] );
		add_filter( 'post_class', [ $this, 'add_quiz_blocks_class' ] );

		add_filter( 'sensei_quiz_enable_block_based_editor', [ $this, 'disable_block_editor_functions_when_question_types_are_registered' ], 2 ); // It has 2 as priority for better backward compabilitiby, since originally it was inside the method `is_block_based_editor_enabled`.
	}

	/**
	 * Check if the block based quiz editor is enabled. If not, fall back to the legacy metabox editor.
	 *
	 * @since 3.9.0
	 *
	 * @return bool
	 */
	public function is_block_based_editor_enabled() {

		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$is_block_editor = (
			! $current_screen || $current_screen->is_block_editor()
		) || (
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't touch the nonce.
			isset( $_GET['meta-box-loader-nonce'] ) && wp_verify_nonce( wp_unslash( $_GET['meta-box-loader-nonce'] ), 'meta-box-loader' )
		);

		/**
		 * Filter to change whether the block based editor should be used instead of the legacy
		 * metabox based editor. This is to allow sites to migrate over to the block based
		 * editor if necessary.
		 *
		 * @since 3.9.0
		 * @hook sensei_quiz_enable_block_based_editor
		 *
		 * @param {bool} $is_block_based_editor_enabled True if block based editor is enabled.
		 *
		 * @return {bool}
		 */
		return apply_filters( 'sensei_quiz_enable_block_based_editor', $is_block_editor );
	}

	/**
	 * Disable block based editor when custom question types have been registered.
	 *
	 * @since 4.11.0
	 *
	 * @param bool $is_block_based_editor_enabled Whether the block based editor is enabled.
	 *
	 * @return bool Whether block based editor should be enabled.
	 */
	public function disable_block_editor_functions_when_question_types_are_registered( $is_block_based_editor_enabled ) {
		return ! has_filter( 'sensei_question_types' ) && $is_block_based_editor_enabled;
	}

	/**
	 * Hooks into `wp_insert_post_data` and updates the quiz author to the lesson author on create.
	 *
	 * @param mixed     $data                The data to be saved.
	 * @param mixed     $postarr             The post data.
	 * @param mixed     $unsanitized_postarr Unsanitized post data.
	 * @param bool|null $update              Whether the action is for an existing post being updated or not.
	 * @return mixed
	 */
	public function set_quiz_author_on_create( $data, $postarr, $unsanitized_postarr, $update = null ) {
		// Compatibility for WP < 6.0.
		if ( null === $update ) {
			$update = ! empty( $postarr['ID'] );
		}

		// Only handle new posts.
		if ( $update ) {
			return $data;
		}

		// Only handle quizzes.
		if ( 'quiz' !== $data['post_type'] ) {
			return $data;
		}

		// Set author to lesson author.
		$lesson_id = $postarr['post_parent'] ?? null;
		if ( $lesson_id ) {
			$lesson = get_post( $lesson_id );
			if ( $lesson ) {
				$data['post_author'] = $lesson->post_author;
			}
		}

		return $data;
	}

	/**
	 * Update the quiz data when the lesson is changed
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function update_after_lesson_change( $post_id ) {

		// If this isn't a 'lesson' post, don't update it.
		// if this is a revision don't save it
		// We can ignore nonce verification because we don't make any changes using $_POST data.
		if ( ! isset( $_POST['post_type'] ) // phpcs:ignore WordPress.Security.NonceVerification
			|| 'lesson' !== $_POST['post_type'] // phpcs:ignore WordPress.Security.NonceVerification
			|| wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Get the lesson author id to be use late.
		$saved_lesson         = get_post( $post_id );
		$new_lesson_author_id = $saved_lesson->post_author;

		// Get the lessons quiz.
		$quiz_id = Sensei()->lesson->lesson_quizzes( $post_id );
		if ( ! $quiz_id ) {
			return;
		}

		// Setup the quiz items new author value.
		$my_post = array(
			'ID'          => $quiz_id,
			'post_author' => $new_lesson_author_id,
			'post_name'   => $saved_lesson->post_name,
			'post_title'  => $saved_lesson->post_title,
		);

		// Remove the action so that it doesn't fire again.
		remove_action( 'save_post', array( $this, 'update_after_lesson_change' ) );

		// Update the post into the database.
		wp_update_post( $my_post );
	}


	/**
	 * Get the lesson this quiz belongs to.
	 *
	 * @since 1.7.2
	 * @param int|null $quiz_id (Optional) The quiz post ID. Defaults to the current post ID.
	 * @return int|bool Lesson ID or false if not found.
	 */
	public function get_lesson_id( $quiz_id = null ) {

		if ( empty( $quiz_id ) || ! intval( $quiz_id ) > 0 ) {
			global $post;
			if ( 'quiz' === get_post_type( $post ) ) {
				$quiz_id = $post->ID;
			} else {
				return false;
			}
		}

		$quiz = get_post( $quiz_id );

		return $quiz ? $quiz->post_parent : false;

	}

	/**
	 * This function hooks into the quiz page and accepts the answer form save post.
	 *
	 * @since 1.7.3
	 */
	public function user_save_quiz_answers_listener() {

		if ( ! isset( $_POST['quiz_save'] )
			|| empty( $_POST['sensei_question'] )
			|| empty( $_POST['questions_asked'] )
			|| ! isset( $_POST['woothemes_sensei_save_quiz_nonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Do not change the nonce.
			|| ! wp_verify_nonce( wp_unslash( $_POST['woothemes_sensei_save_quiz_nonce'] ), 'woothemes_sensei_save_quiz_nonce' ) ) {
			return;
		}

		$quiz_id   = get_the_ID();
		$lesson_id = $this->get_lesson_id( $quiz_id );
		$user_id   = get_current_user_id();

		$answers = $this->parse_form_answers(
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The answers value can vary, so we do the sanitization on output.
			wp_unslash( $_POST['sensei_question'] ),
			array_map( 'intval', $_POST['questions_asked'] ),
			$lesson_id,
			$user_id
		);

		$success = self::save_user_answers( $answers, $_FILES, $lesson_id, $user_id );

		if ( $success ) {
			// Update the message shown to the user.
			Sensei()->frontend->messages = '<div class="sensei-message note">' . __( 'Quiz Saved Successfully.', 'sensei-lms' ) . '</div>';
		}

		// remove the hook as it should only fire once per click
		remove_action( 'sensei_single_quiz_content_inside_before', 'user_save_quiz_answers_listener' );

	}

	/**
	 * Parse the provided answers by filling in missing answers or removing answers not part of the quiz.
	 *
	 * @since 3.15.0
	 *
	 * @param array $answers         The submitted answers.
	 * @param array $questions_asked The ID's of all the asked quiz questions.
	 * @param int   $lesson_id       The lesson ID.
	 * @param int   $user_id         The user ID.
	 *
	 * @return array
	 */
	private function parse_form_answers( array $answers, array $questions_asked, int $lesson_id, int $user_id ): array {

		// If we have a fraction of the answers (e.g. pagination), include the previously saved answers.
		if ( count( $answers ) !== count( $questions_asked ) ) {
			$previous_answers = self::get_user_answers( $lesson_id, $user_id );

			if ( $previous_answers ) {
				// Merge and preserve the indexes.
				$answers = array_replace( $previous_answers, $answers );
			}
		}

		// Merge with the questions asked.
		return $this->merge_quiz_answers_with_questions_asked(
			$answers,
			$questions_asked
		);
	}

	/**
	 * Save the user answers for the given lesson's quiz
	 *
	 * For this function you must supply all three parameters. It will return false if one is left out.
	 *
	 * @since 1.7.4
	 * @access public
	 *
	 * @param array $quiz_answers
	 * @param array $files from global $_FILES
	 * @param int   $lesson_id
	 * @param int   $user_id
	 *
	 * @return false|int $answers_saved
	 */
	public static function save_user_answers( $quiz_answers, $files = array(), $lesson_id = 0, $user_id = 0 ) {

		if ( ! ( $user_id > 0 ) ) {
			$user_id = get_current_user_id();
		}

		// make sure the parameters are valid before continuing
		if ( empty( $lesson_id ) || empty( $user_id )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| ! get_userdata( $user_id )
			|| ! is_array( $quiz_answers ) ) {

			return false;

		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		// start the lesson before saving the data in case the user has not started the lesson
		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		// prepare the answers
		$prepared_answers = self::prepare_form_submitted_answers( $quiz_answers, $files );
		if ( ! $prepared_answers ) {
			return false;
		}

		// save the user data
		$submission = Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id );

		Sensei()->quiz_grade_repository->delete_all( $submission->get_id() );
		Sensei()->quiz_answer_repository->delete_all( $submission->get_id() );

		foreach ( $prepared_answers as $question_id => $answer ) {
			Sensei()->quiz_answer_repository->create( $submission->get_id(), $question_id, $answer );
		}

		// Save transient to make retrieval faster.
		$transient_key = 'sensei_answers_' . $user_id . '_' . $lesson_id;
		set_transient( $transient_key, $prepared_answers, 10 * DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Get the user answers for the given lesson's quiz.
	 *
	 * This function returns the data that is stored on the lesson as meta and is not compatible with
	 * retrieving data for quiz answer before sensei 1.7.4
	 *
	 * @since 1.7.4
	 * @access public
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 *
	 * @return array|false $answers or false
	 */
	public function get_user_answers( $lesson_id, $user_id ) {

		if ( ! intval( $lesson_id ) > 0 || 'lesson' !== get_post_type( $lesson_id )
		|| ! intval( $user_id ) > 0 || ! get_userdata( $user_id ) ) {
			return false;
		}

		// save some time and get the transient cached data
		$transient_key            = 'sensei_answers_' . $user_id . '_' . $lesson_id;
		$transient_cached_answers = get_transient( $transient_key );

		// return the transient or get the values get the values from the comment meta
		$encoded_answers_map = [];
		if ( ! empty( $transient_cached_answers ) ) {
			$encoded_answers_map = $transient_cached_answers;
		} else {
			$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
			if ( ! $quiz_id ) {
				return false;
			}

			$submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
			if ( ! $submission ) {
				return false;
			}

			$answers = Sensei()->quiz_answer_repository->get_all( $submission->get_id() );
			foreach ( $answers as $answer ) {
				$encoded_answers_map[ $answer->get_question_id() ] = $answer->get_value();
			}
		}

		if ( ! $encoded_answers_map ) {
			return false;
		}

		// set the transient with the new valid data for faster retrieval in future
		set_transient( $transient_key, $encoded_answers_map, 10 * DAY_IN_SECONDS );

		// Decode and unserialize all answers.
		$decoded_answers_map = [];
		foreach ( $encoded_answers_map as $question_id => $encoded_answer ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$decoded_answers_map[ $question_id ] = maybe_unserialize( base64_decode( $encoded_answer ) );
		}

		return $decoded_answers_map;

	}


	/**
	 *
	 * This function runs on the init hook and checks if the reset quiz button was clicked.
	 *
	 * @since 1.7.2
	 * @hooked init
	 *
	 * @return void;
	 */
	public function reset_button_click_listener() {

		if ( ! isset( $_POST['quiz_reset'] )
			|| ! isset( $_POST['woothemes_sensei_reset_quiz_nonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Do not change the nonce.
			|| ! wp_verify_nonce( wp_unslash( $_POST['woothemes_sensei_reset_quiz_nonce'] ), 'woothemes_sensei_reset_quiz_nonce' ) ) {

			return; // exit
		}

		global $post;
		$current_quiz_id = $post->ID;
		$lesson_id       = $this->get_lesson_id( $current_quiz_id );

		// reset all user data
		$this->reset_user_lesson_data( $lesson_id, get_current_user_id() );

		// Redirect to the start of the quiz.
		wp_safe_redirect(
			add_query_arg( [ 'bypass_server_cache' => uniqid() ], remove_query_arg( 'quiz-page' ) )
		);
		exit;

	}

	/**
	 * Complete/ submit  quiz hooked function
	 *
	 * This function listens to the complete button submit action and processes the users submitted answers
	 * not that this function submits the given users quiz answers for grading.
	 *
	 * @since  1.7.4
	 * @access public
	 *
	 * @since
	 * @return void
	 */
	public function user_quiz_submit_listener() {

		// only respond to valid quiz completion submissions
		if (
			! isset( $_POST['quiz_complete'] )
			|| empty( $_POST['questions_asked'] )
			|| ! isset( $_POST['woothemes_sensei_complete_quiz_nonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Do not change the nonce.
			|| ! wp_verify_nonce( wp_unslash( $_POST['woothemes_sensei_complete_quiz_nonce'] ), 'woothemes_sensei_complete_quiz_nonce' )
			|| ! self::is_quiz_available()
			|| self::is_quiz_completed()
		) {
			return;
		}

		$quiz_id   = get_the_ID();
		$lesson_id = $this->get_lesson_id( $quiz_id );
		$user_id   = get_current_user_id();

		$answers = $this->parse_form_answers(
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The answers value can vary, so we do the sanitization on output.
			wp_unslash( $_POST['sensei_question'] ?? [] ),
			array_map( 'intval', $_POST['questions_asked'] ),
			$lesson_id,
			$user_id
		);

		self::submit_answers_for_grading( $answers, $_FILES, $lesson_id, $user_id );

		// Redirect to the start of the quiz.
		wp_safe_redirect(
			add_query_arg( [ 'bypass_server_cache' => uniqid() ], remove_query_arg( 'quiz-page' ) )
		);
		exit;

	}

	/**
	 * Redirect back to the lesson if the lesson is password protected.
	 *
	 * @since  4.4.3
	 * @access private
	 */
	public function redirect_if_lesson_is_protected() {
		if ( ! is_singular( 'quiz' ) ) {
			return;
		}

		$lesson_id = $this->get_lesson_id();

		if ( post_password_required( $lesson_id ) ) {
			wp_safe_redirect( get_permalink( $lesson_id ) );
			exit;
		}
	}

	/**
	 * Handle the page change form submission and redirects to the target page.
	 *
	 * The quiz form is submitted on each page change.
	 * This is needed to save the answers for each page.
	 * Used when the quiz pagination is enabled.
	 *
	 * @since  3.15.0
	 * @access private
	 */
	public function page_change_listener() {

		if (
			! isset( $_POST['quiz_target_page'] )
			|| empty( $_POST['questions_asked'] )
			|| ! isset( $_POST['sensei_quiz_page_change_nonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Do not change the nonce.
			|| ! wp_verify_nonce( wp_unslash( $_POST['sensei_quiz_page_change_nonce'] ), 'sensei_quiz_page_change_nonce' )
		) {
			return;
		}

		if ( self::is_quiz_available() && ! self::is_quiz_completed() ) {
			$quiz_id   = get_the_ID();
			$user_id   = get_current_user_id();
			$lesson_id = $this->get_lesson_id( $quiz_id );

			$answers = $this->parse_form_answers(
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The answers value can vary, so we do the sanitization on output.
				wp_unslash( $_POST['sensei_question'] ?? [] ),
				array_map( 'intval', $_POST['questions_asked'] ),
				$lesson_id,
				$user_id
			);

			self::save_user_answers( $answers, $_FILES, $lesson_id, $user_id );
		}

		// Redirect to the target page.
		wp_safe_redirect(
			add_query_arg( [ 'bypass_server_cache' => uniqid() ], sanitize_text_field( wp_unslash( $_POST['quiz_target_page'] ) ) )
		);
		exit;

	}

	/**
	 * This function set's up the data need for the quiz page
	 *
	 * This function hooks into sensei_complete_quiz and load the global data for the
	 * current quiz.
	 *
	 * @since 1.7.4
	 * @access public
	 */
	public function load_global_quiz_data() {

		global  $post, $current_user;
		$this->data = new stdClass();

		// Get Quiz Questions.
		$lesson_quiz_questions = Sensei()->lesson->lesson_quiz_questions( $post->ID );

		// Get Quiz Lesson ID.
		$quiz_lesson_id = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );

		// Get quiz grade type.
		$quiz_grade_type = get_post_meta( $post->ID, '_quiz_grade_type', true );

		// Get quiz pass mark.
		$quiz_passmark = Sensei_Utils::as_absolute_rounded_number( get_post_meta( $post->ID, '_quiz_passmark', true ), 2 );

		// Get latest quiz answers and grades.
		$lesson_id          = Sensei()->quiz->get_lesson_id( $post->ID );
		$user_quizzes       = Sensei()->quiz->get_user_answers( $lesson_id, get_current_user_id() );
		$user_lesson_status = Sensei_Utils::user_lesson_status( $quiz_lesson_id, $current_user->ID );

		$user_quiz_grade = 0;
		$quiz_submission = Sensei()->quiz_submission_repository->get( $post->ID, $current_user->ID );
		if ( $quiz_submission ) {
			$user_quiz_grade = $quiz_submission->get_final_grade();
		}

		if ( ! is_array( $user_quizzes ) ) {
			$user_quizzes = array(); }

		// Check again that the lesson is complete.
		$user_lesson_end      = Sensei_Utils::user_completed_lesson( $user_lesson_status );
		$user_lesson_complete = false;
		if ( $user_lesson_end ) {
			$user_lesson_complete = true;
		}

		$reset_allowed = get_post_meta( $post->ID, '_enable_quiz_reset', true );
		// Backwards compatibility.
		if ( 'on' == $reset_allowed ) {
			$reset_allowed = 1;
		}

		// Build frontend data object for backwards compatibility
		// using this is no longer recommended.
		$this->data->user_quiz_grade       = $user_quiz_grade;
		$this->data->quiz_passmark         = $quiz_passmark;
		$this->data->quiz_lesson           = $quiz_lesson_id;
		$this->data->quiz_grade_type       = $quiz_grade_type;
		$this->data->user_lesson_end       = $user_lesson_end;
		$this->data->user_lesson_complete  = $user_lesson_complete;
		$this->data->lesson_quiz_questions = $lesson_quiz_questions;
		$this->data->reset_quiz_allowed    = $reset_allowed;

	}


	/**
	 * This function converts the submitted array and makes it ready for storage.
	 *
	 * Creating a single array of all question types including file id's to be stored
	 * as comment meta by the calling function.
	 *
	 * @since 1.7.4
	 * @access public
	 *
	 * @param array $unprepared_answers Submitted answers.
	 * @param array $files Uploaded files.
	 * @return array|false
	 */
	public static function prepare_form_submitted_answers( $unprepared_answers, $files ) {

		$prepared_answers = array();

		// validate incoming answers
		if ( empty( $unprepared_answers ) || ! is_array( $unprepared_answers ) ) {
			return false;
		}

		// Loop through submitted quiz answers and save them appropriately
		foreach ( $unprepared_answers as $question_id => $answer ) {

			// get the current questions question type
			$question_type = Sensei()->question->get_question_type( $question_id );

			$answer = wp_unslash( $answer );

			// compress the answer for saving
			if ( 'multi-line' === $question_type ) {
				$answer = wp_kses( $answer, wp_kses_allowed_html( 'post' ) );
			} elseif ( 'file-upload' === $question_type ) {
				$file_key = 'file_upload_' . $question_id;
				if (
					isset( $files[ $file_key ] )
					&& self::is_uploaded_file_valid( $files[ $file_key ]['tmp_name'], $files[ $file_key ]['name'], $question_id )
				) {
					$attachment_id = Sensei_Utils::upload_file( $files[ $file_key ] );
					if ( $attachment_id ) {
						$answer = $attachment_id;
					}
				}
			}

			$prepared_answers[ $question_id ] = base64_encode( maybe_serialize( $answer ) );

		}

		return $prepared_answers;
	}

	/**
	 * Validate the mime type of an uploaded file to a quiz question.
	 *
	 * @param string $file_path   Path to the uploaded file.
	 * @param string $file_name   File name.
	 * @param int    $question_id Question post ID.
	 *
	 * @return bool
	 */
	private static function is_uploaded_file_valid( $file_path, $file_name, $question_id ) {
		/**
		 * Filters allowed which mimetypes are allowed.
		 *
		 * @since 3.7.0
		 * @hook sensei_quiz_answer_file_upload_types
		 *
		 * @param {false|array} $allowed_mime_types Array of allowed mimetypes. Returns `false` to allow all file types.
		 * @param {int}         $question_id        Question post ID.
		 *
		 * @return {false|array} Allowed mime types or false to allow all types.
		 */
		$allowed_mime_types = apply_filters( 'sensei_quiz_answer_file_upload_types', false, $question_id );

		// If `$allowed_mime_types` is false, don't filter by mime type.
		if ( false === $allowed_mime_types ) {
			return true;
		}

		$file_type = wp_check_filetype_and_ext( $file_path, $file_name );

		return $file_type['type'] && in_array( $file_type['type'], $allowed_mime_types, true );
	}

	/**
	 * Reset user submitted questions
	 *
	 * This function resets the quiz data for a user that has been submitted fro grading already. It is different to
	 * the save_user_answers as currently the saved and submitted answers are stored differently.
	 *
	 * @since 1.7.4
	 * @access public
	 *
	 * @return bool $reset_success
	 * @param int $user_id
	 * @param int $lesson_id
	 */
	public function reset_user_lesson_data( $lesson_id, $user_id = 0 ) {

		// Make sure the parameters are valid.
		if ( empty( $lesson_id ) || empty( $user_id )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| ! get_userdata( $user_id ) ) {
			return false;
		}

		// Get the users lesson status to make.
		$user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		if ( ! isset( $user_lesson_status->comment_ID ) ) {
			// This user is not taking this lesson so this process is not needed.
			return false;
		}

		// Get the lesson quiz and course.
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		// Reset the transients.
		$answers_transient_key          = 'sensei_answers_' . $user_id . '_' . $lesson_id;
		$grades_transient_key           = 'quiz_grades_' . $user_id . '_' . $lesson_id;
		$answers_feedback_transient_key = 'sensei_answers_feedback_' . $user_id . '_' . $lesson_id;
		delete_transient( $answers_transient_key );
		delete_transient( $grades_transient_key );
		delete_transient( $answers_feedback_transient_key );

		$lesson_progress = Sensei()->lesson_progress_repository->get( $lesson_id, $user_id );
		if ( $lesson_progress ) {
			$lesson_progress->start();
			Sensei()->lesson_progress_repository->save( $lesson_progress );
		}

		if ( $quiz_id ) {
			// Delete quiz answers, this auto deletes the corresponding meta data, such as the question/answer grade.
			Sensei_Utils::sensei_delete_quiz_answers( $quiz_id, $user_id );

			$quiz_submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
			if ( $quiz_submission ) {
				$quiz_submission->set_final_grade( null );
				Sensei()->quiz_submission_repository->save( $quiz_submission );
				Sensei()->quiz_grade_repository->delete_all( $quiz_submission->get_id() );
				Sensei()->quiz_answer_repository->delete_all( $quiz_submission->get_id() );
			}
		}

		// Update course completion.
		$course_progress = Sensei()->course_progress_repository->get( $course_id, $user_id );
		if ( $course_progress ) {
			$course_progress->start();

			Sensei()->course_progress_repository->save( $course_progress );

			// Reset the course progress metadata.
			$course_progress_metadata = [
				'complete' => 0,
				'percent'  => 0,
			];
			foreach ( $course_progress_metadata as $key => $value ) {
				update_comment_meta( $course_progress->get_id(), $key, $value );
			}
		}

		// Run any action on quiz/lesson reset (previously this didn't occur on resetting a quiz, see resetting a lesson in sensei_complete_lesson().
		do_action( 'sensei_user_lesson_reset', $user_id, $lesson_id );
		if ( ! is_admin() ) {
			Sensei()->notices->add_notice( __( 'Lesson Reset Successfully.', 'sensei-lms' ), 'info' );
		}

		return true;
	}

	/**
	 * Submit the users quiz answers for grading
	 *
	 * This function accepts users answers and stores it but also initiates the grading
	 * if a quiz can be graded automatically it will, if not the answers can be graded by the teacher.
	 *
	 * @since 1.7.4
	 * @access public
	 *
	 * @param array $quiz_answers
	 * @param array $files from $_FILES
	 * @param int   $user_id
	 * @param int   $lesson_id
	 *
	 * @return bool $answers_submitted
	 */
	public static function submit_answers_for_grading( $quiz_answers, $files = array(), $lesson_id = 0, $user_id = 0 ) {

		// Get the user_id if none was passed in use the current logged in user.
		if ( 0 >= (int) $user_id ) {
			$user_id = get_current_user_id();
		}

		// Make sure the parameters are valid before continuing.
		if ( empty( $lesson_id ) || empty( $user_id ) || ! is_array( $quiz_answers )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| ! get_userdata( $user_id )
		) {
			return false;
		}

		// Get Quiz ID.
		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		// Get quiz grade type.
		$quiz_grade_type = get_post_meta( $quiz_id, '_quiz_grade_type', true );

		// Get quiz pass setting.
		$pass_required = get_post_meta( $quiz_id, '_pass_required', true );

		// Get the minimum percentage need to pass this quiz.
		$quiz_pass_percentage = Sensei_Utils::as_absolute_rounded_number( get_post_meta( $quiz_id, '_quiz_passmark', true ), 2 );

		// Handle Quiz Questions asked
		// This is to ensure we save the questions that we've asked this user and that this can't be change unless
		// the quiz is reset by admin or user( user: only if the setting is enabled ).
		// get the questions asked when when the quiz questions were generated for the user : Sensei_Lesson::lesson_quiz_questions.
		$has_quiz_progress = Sensei()->quiz_progress_repository->has( $quiz_id, $user_id );
		if ( ! $has_quiz_progress ) {
			Sensei_Utils::user_start_lesson( $user_id, $lesson_id );
		}

		$quiz_progress = Sensei()->quiz_progress_repository->get( $quiz_id, $user_id );
		if ( ! $quiz_progress ) {
			// Even after starting a lesson we can't find the progress. Leave immediately.
			return false;
		}

		// Save Quiz Answers for grading, the save function also calls the sensei_start_lesson.
		self::save_user_answers( $quiz_answers, $files, $lesson_id, $user_id );

		// Grade quiz.
		$grade = Sensei_Grading::grade_quiz_auto( $quiz_id, $quiz_answers, 0, $quiz_grade_type );

		// Get Lesson Grading Setting.
		$lesson_metadata = array();
		$lesson_status   = 'ungraded'; // Default when completing a quiz.
		$quiz_progress->ungrade();

		// At this point the answers have been submitted.
		$answers_submitted = true;

		// if this condition is false the quiz should manually be graded by admin.
		if ( 'auto' === $quiz_grade_type && ! is_wp_error( $grade ) ) {

			// Quiz has been automatically Graded.
			if ( 'on' === $pass_required ) {

				// Student has reached the pass mark and lesson is complete.
				if ( $quiz_pass_percentage <= $grade ) {
					$quiz_progress->pass();
					$lesson_status = 'passed';
				} else {
					$quiz_progress->fail();
					$lesson_status = 'failed';
				}
			} else {
				// Student only has to partake the quiz.
				$quiz_progress->grade();
				$lesson_status = 'graded';
			}
		}

		Sensei()->quiz_progress_repository->save( $quiz_progress );
		foreach ( $lesson_metadata as $key => $value ) {
			update_comment_meta( $quiz_progress->get_id(), $key, $value );
		}

		if ( 'passed' === $lesson_status || 'graded' === $lesson_status ) {

			/**
			 * Lesson end action hook
			 *
			 * This hook is fired after a lesson quiz has been graded and the lesson status is 'passed' OR 'graded'
			 *
			 * @param int $user_id
			 * @param int $lesson_id
			 */
			do_action( 'sensei_user_lesson_end', $user_id, $lesson_id );

		}

		/**
		 * User quiz has been submitted
		 *
		 * Fires the end of the submit_answers_for_grading function. It will fire irrespective of the submission
		 * results.
		 *
		 * @param int $user_id
		 * @param int $quiz_id
		 * @param string $grade
		 * @param string $quiz_pass_percentage
		 * @param string $quiz_grade_type
		 */
		do_action( 'sensei_user_quiz_submitted', $user_id, $quiz_id, $grade, $quiz_pass_percentage, $quiz_grade_type );

		return $answers_submitted;

	}

	/**
	 * Get the user question answer
	 *
	 * This function gets the users saved answer on given quiz for the given question parameter
	 * this function allows for a fallback to users still using the question saved data from before 1.7.4
	 *
	 * @since 1.7.4
	 *
	 * @param int $lesson_id
	 * @param int $question_id
	 * @param int $user_id ( optional )
	 *
	 * @return bool|null $answers_submitted
	 */
	public function get_user_question_answer( $lesson_id, $question_id, $user_id = 0 ) {

		// parameter validation
		if ( empty( $lesson_id ) || empty( $question_id )
			|| ! ( intval( $lesson_id ) > 0 )
			|| ! ( intval( $question_id ) > 0 )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| 'question' !== get_post_type( $question_id ) ) {

			return false;
		}

		if ( ! ( intval( $user_id ) > 0 ) ) {
			$user_id = get_current_user_id();
		}

		if ( 0 === $user_id ) {
			return null;
		}

		$users_answers = $this->get_user_answers( $lesson_id, $user_id );

		if ( ! $users_answers || empty( $users_answers )
		|| ! is_array( $users_answers ) || ! isset( $users_answers[ $question_id ] ) ) {

			// Fallback for pre 1.7.4 data
			$comment = Sensei_Utils::sensei_check_for_activity(
				array(
					'post_id' => $question_id,
					'user_id' => $user_id,
					'type'    => 'sensei_user_answer',
				),
				true
			);

			if ( ! isset( $comment->comment_content ) ) {
				return null;
			}

			return maybe_unserialize( base64_decode( $comment->comment_content ) );
		}

		return $users_answers[ $question_id ];

	}

	/**
	 * Saving the users quiz question grades
	 *
	 * This function save all the grades for all the question in a given quiz on the lesson
	 * comment meta. It makes use of transients to save the grades for easier access at a later stage
	 *
	 * @since 1.7.4
	 *
	 * @param array                                                        $quiz_grades{
	 *      @type int $question_id
	 *      @type int $question_grade
	 * }
	 * @param $lesson_id
	 * @param $user_id (Optional) will use the current user if not supplied
	 *
	 * @return bool
	 */
	public function set_user_grades( $quiz_grades, $lesson_id, $user_id = 0 ) {

		// get the user_id if none was passed in use the current logged in user
		if ( ! intval( $user_id ) > 0 ) {
			$user_id = get_current_user_id();
		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		// make sure the parameters are valid before continuing
		if (
			! $quiz_id
			|| empty( $user_id )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| ! get_userdata( $user_id )
			|| ! is_array( $quiz_grades )
		) {
			return false;
		}

		$submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
		if ( ! $submission ) {
			return false;
		}

		Sensei()->quiz_grade_repository->delete_all( $submission->get_id() );

		$answers     = Sensei()->quiz_answer_repository->get_all( $submission->get_id() );
		$answers_map = [];
		foreach ( $answers as $answer ) {
			$answers_map[ $answer->get_question_id() ] = $answer;
		}

		foreach ( $quiz_grades as $question_id => $points ) {
			$answer = $answers_map[ $question_id ];
			Sensei()->quiz_grade_repository->create( $submission->get_id(), $answer->get_id(), $question_id, $points );
		}

		$transient_key = 'quiz_grades_' . $user_id . '_' . $lesson_id;
		set_transient( $transient_key, $quiz_grades, 10 * DAY_IN_SECONDS );

		return true;

	}

	/**
	 * Retrieve the users quiz question grades
	 *
	 * This function gets all the grades for all the questions in the given lesson quiz for a specific user.
	 *
	 * @since 1.7.4
	 *
	 * @param $lesson_id
	 * @param $user_id (Optional) will use the current user if not supplied
	 *
	 * @return array|false $user_quiz_grades or false if none exists for this users
	 */
	public function get_user_grades( $lesson_id, $user_id = 0 ) {

		// get the user_id if none was passed in use the current logged in user
		if ( ! intval( $user_id ) > 0 ) {
			$user_id = get_current_user_id();
		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		if (
			! intval( $lesson_id ) > 0
			|| ! $quiz_id
			|| 'lesson' !== get_post_type( $lesson_id )
			|| ! intval( $user_id ) > 0
			|| ! get_userdata( $user_id )
		) {
			return false;
		}

		// save some time and get the transient cached data
		$transient_key = 'quiz_grades_' . $user_id . '_' . $lesson_id;
		$grades_map    = get_transient( $transient_key );

		// get the data if nothing was stored in the transient
		if ( false === $grades_map ) {
			$submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
			if ( ! $submission ) {
				return false;
			}

			$grades     = Sensei()->quiz_grade_repository->get_all( $submission->get_id() );
			$grades_map = [];
			foreach ( $grades as $grade ) {
				$grades_map[ $grade->get_question_id() ] = $grade->get_points();
			}

			// set the transient with the new valid data for faster retrieval in future
			set_transient( $transient_key, $grades_map, 10 * DAY_IN_SECONDS );
		}

		// if there is no data for this user
		if ( ! is_array( $grades_map ) ) {
			return false;
		}

		return $grades_map;

	}

	/**
	 * Get the user question grade
	 *
	 * This function gets the grade on a quiz for the given question parameter
	 * It does NOT do any grading. It simply retrieves the data that was stored during grading.
	 * this function allows for a fallback to users still using the question saved data from before 1.7.4
	 *
	 * @since 1.7.4
	 *
	 * @param int $lesson_id
	 * @param int $question_id
	 * @param int $user_id ( optional )
	 *
	 * @return bool $question_grade
	 */
	public function get_user_question_grade( $lesson_id, $question_id, $user_id = 0 ) {

		// parameter validation
		if ( empty( $lesson_id ) || empty( $question_id )
			|| ! ( intval( $lesson_id ) > 0 )
			|| ! ( intval( $question_id ) > 0 )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| 'question' !== get_post_type( $question_id ) ) {

			return false;
		}

		$all_user_grades = self::get_user_grades( $lesson_id, $user_id );

		if ( ! $all_user_grades || ! isset( $all_user_grades[ $question_id ] ) ) {
			$fall_back_grade = false;

			if ( 0 === $user_id ) {
				return $fall_back_grade;
			}

			// fallback to data pre 1.7.4
			$args = array(
				'post_id' => $question_id,
				'user_id' => $user_id,
				'type'    => 'sensei_user_answer',
			);

			$question_activity = Sensei_Utils::sensei_check_for_activity( $args, true );
			if ( isset( $question_activity->comment_ID ) ) {
				$fall_back_grade = get_comment_meta( $question_activity->comment_ID, 'user_grade', true );
			}

			return $fall_back_grade;

		}

		return $all_user_grades[ $question_id ];

	}

	/**
	 * Save the user's answers feedback
	 *
	 * For this function you must supply all three parameters. If will return false one is left out.
	 * The data will be saved on the lesson ID supplied.
	 *
	 * @since 1.7.5
	 * @access public
	 *
	 * @param array $answers_feedback{
	 *  $type int $question_id
	 *  $type string $question_feedback
	 * }
	 * @param int   $lesson_id
	 * @param int   $user_id
	 *
	 * @return bool
	 */
	public function save_user_answers_feedback( $answers_feedback, $lesson_id, $user_id = 0 ) {

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		// make sure the parameters are valid before continuing
		if (
			! $quiz_id
			|| empty( $user_id )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| ! get_userdata( $user_id )
			|| ! is_array( $answers_feedback )
		) {
			return false;
		}

		// check if the lesson is started before saving, if not start the lesson for the user
		if ( ! ( 0 < intval( Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) ) ) ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		}

		// encode the feedback
		$encoded_answers_feedback = array();
		foreach ( $answers_feedback as $question_id => $feedback ) {
			$encoded_answers_feedback[ $question_id ] = base64_encode( $feedback );
		}

		$submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
		if ( ! $submission ) {
			return false;
		}

		$grades = Sensei()->quiz_grade_repository->get_all( $submission->get_id() );
		foreach ( $grades as $grade ) {
			$feedback = $encoded_answers_feedback[ $grade->get_question_id() ];
			$grade->set_feedback( $feedback );
		}

		Sensei()->quiz_grade_repository->save_many( $submission->get_id(), $grades );

		// Save transient to make retrieval faster in the future.
		$transient_key = 'sensei_answers_feedback_' . $user_id . '_' . $lesson_id;
		set_transient( $transient_key, $encoded_answers_feedback, 10 * DAY_IN_SECONDS );

		return true;

	}

	/**
	 * Get the user's answers feedback.
	 *
	 * This function returns the feedback submitted by the teacher/admin
	 * during grading. Grading occurs manually or automatically.
	 *
	 * @since 1.7.5
	 * @access public
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 *
	 * @return false | array $answers_feedback{
	 *  $type int $question_id
	 *  $type string $question_feedback
	 * }
	 */
	public function get_user_answers_feedback( $lesson_id, $user_id = 0 ) {

		$answers_feedback = array();

		// get the user_id if none was passed in use the current logged in user
		if ( ! intval( $user_id ) > 0 ) {
			$user_id = get_current_user_id();
		}

		if ( ! intval( $lesson_id ) > 0 || 'lesson' !== get_post_type( $lesson_id )
			|| ! intval( $user_id ) > 0 || ! get_userdata( $user_id ) ) {
			return false;
		}

		// first check the transient to save a few split seconds
		$transient_key    = 'sensei_answers_feedback_' . $user_id . '_' . $lesson_id;
		$encoded_feedback = get_transient( $transient_key );

		// get the data if nothing was stored in the transient
		if ( empty( $encoded_feedback ) || ! $encoded_feedback ) {

			$encoded_feedback = Sensei_Utils::get_user_data( 'quiz_answers_feedback', $lesson_id, $user_id );

			// set the transient with the new valid data for faster retrieval in future
			set_transient( $transient_key, $encoded_feedback, 10 * DAY_IN_SECONDS );

		}

		// if there is no data for this user
		if ( ! is_array( $encoded_feedback ) ) {
			return false;
		}

		foreach ( $encoded_feedback as $question_id => $feedback ) {

			$answers_feedback[ $question_id ] = base64_decode( $feedback );

		}

		return $answers_feedback;

	}

	/**
	 * Get the user's answer feedback for a specific question.
	 *
	 * This function gives you a single answer note/feedback string
	 * for the user on the given question.
	 *
	 * @since 1.7.5
	 * @access public
	 *
	 * @param int $lesson_id   Lesson ID.
	 * @param int $question_id Question ID.
	 * @param int $user_id     User ID.
	 *
	 * @return string|bool Feedback or false if not found.
	 */
	public function get_user_question_feedback( $lesson_id, $question_id, $user_id = 0 ) {

		$feedback = false;

		// parameter validation
		if ( empty( $lesson_id ) || empty( $question_id )
			|| ! ( intval( $lesson_id ) > 0 )
			|| ! ( intval( $question_id ) > 0 )
			|| 'lesson' !== get_post_type( $lesson_id )
			|| 'question' !== get_post_type( $question_id ) ) {

			return false;
		}

		// get all the feedback for the user on the given lesson
		$all_feedback = $this->get_user_answers_feedback( $lesson_id, $user_id );

		if ( ! $all_feedback || empty( $all_feedback )
			|| ! is_array( $all_feedback ) || empty( $all_feedback[ $question_id ] ) ) {

			// fallback to data pre 1.7.4
			// setup the sensei data query
			$args              = array(
				'post_id' => $question_id,
				'user_id' => $user_id,
				'type'    => 'sensei_user_answer',
			);
			$question_activity = Sensei_Utils::sensei_check_for_activity( $args, true );

			// set the default to false and return that if no old data is available.
			if ( isset( $question_activity->comment_ID ) ) {
				$feedback = base64_decode( get_comment_meta( $question_activity->comment_ID, 'answer_note', true ) );
			}

			// finally use the default question feedback
			if ( empty( $feedback ) ) {
				$feedback       = get_post_meta( $question_id, '_answer_feedback', true );
				$user_grade     = $this->get_user_question_grade( $lesson_id, $question_id, $user_id );
				$answer_correct = is_int( $user_grade ) && $user_grade > 0;

				$feedback_block = $answer_correct ? self::get_correct_answer_feedback( $question_id ) : self::get_incorrect_answer_feedback( $question_id );

				if ( $feedback_block ) {
					$feedback = $feedback_block;
				}
			}
		} else {
			$feedback = $all_feedback[ $question_id ];
		}

		/**
		 * Filter the user question feedback.
		 *
		 * @since 1.9.12
		 * @param string $feedback
		 * @param int    $lesson_id
		 * @param int    $question_id
		 * @param int    $user_id
		 */
		return apply_filters( 'sensei_user_question_feedback', $feedback, $lesson_id, $question_id, $user_id );

	}

	/**
	 * Get a top-level inner block.
	 *
	 * @param int    $question_id
	 * @param string $block_name
	 *
	 * @return array|null
	 */
	public static function get_question_inner_block( $question_id, $block_name ) {

		$question = get_post( $question_id );

		if ( has_blocks( $question->post_content ) ) {
			$blocks = parse_blocks( $question->post_content );
			foreach ( $blocks as $block ) {
				if ( $block_name === $block['blockName'] ) {
					return $block;
				}
			}
		}

		return null;
	}

	/**
	 * Get the contents for the correct answer feedback block.
	 *
	 * @param int $question_id Question Id.
	 * @return string block rendered
	 */
	public static function get_correct_answer_feedback( $question_id ) {
		$block = self::get_correct_answer_feedback_block( $question_id );
		return $block ? render_block( $block ) : '';
	}

	/**
	 * Get the contents for the incorrect answer feedback block.
	 *
	 * @access public
	 * @param int $question_id
	 *
	 * @return string
	 */
	public static function get_incorrect_answer_feedback( $question_id ) {
		$block = self::get_incorrect_answer_feedback_block( $question_id );
		return $block ? render_block( $block ) : '';
	}


	/**
	 * Get the contents for the correct answer feedback block.
	 *
	 * @access public
	 * @since 4.6.0
	 * @param int $question_id Question Id.
	 *
	 * @return array
	 */
	public static function get_correct_answer_feedback_block( $question_id ) {
		return self::get_question_inner_block( $question_id, 'sensei-lms/quiz-question-feedback-correct' );
	}

	/**
	 * Get the contents for the incorrect answer feedback block.
	 *
	 * @since 4.6.0
	 * @access public
	 * @param int $question_id Question Id.
	 *
	 * @return array
	 */
	public static function get_incorrect_answer_feedback_block( $question_id ) {
		return self::get_question_inner_block( $question_id, 'sensei-lms/quiz-question-feedback-incorrect' );
	}

	/**
	 * Check if a quiz has no questions, and redirect back to lesson.
	 *
	 * Though a quiz is created for each lesson, it should not be visible
	 * unless it has questions.
	 *
	 * @since 1.9.0
	 * @access public
	 * @return void
	 */
	public function quiz_has_no_questions() {

		if ( ! is_singular( 'quiz' ) ) {
			return;
		}

		global $post;

		$lesson_id = $this->get_lesson_id( $post->ID );

		$has_questions = Sensei_Lesson::lesson_quiz_has_questions( $lesson_id );

		$lesson = get_post( $lesson_id );

		if ( is_singular( 'quiz' ) && ! $has_questions && $_SERVER['REQUEST_URI'] !== "/lesson/$lesson->post_name" ) {

			wp_redirect( get_permalink( $lesson->ID ), 301 );
			exit;

		}

	}

	/**
	 * Check if the quiz is available to the user.
	 *
	 * The quiz becomes available to the user if he is enrolled to the course
	 * and has completed the prerequisite (if any).
	 *
	 * @since 3.15.0
	 *
	 * @param int|null $quiz_id (Optional) The quiz post ID. Defaults to the current post ID.
	 * @param int|null $user_id (Optional) The user ID. Defaults to the current user ID.
	 *
	 * @return bool
	 */
	public static function is_quiz_available( int $quiz_id = null, int $user_id = null ): bool {

		$quiz_id = $quiz_id ? $quiz_id : get_the_ID();
		$user_id = $user_id ? $user_id : get_current_user_id();

		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );
		$course_id = (int) get_post_meta( $lesson_id, '_lesson_course', true );

		// Check if the user has enrolled in the course.
		if ( ! Sensei_Course::is_user_enrolled( $course_id, $user_id ) ) {
			return false;
		}

		// Check if there is a lesson prerequisite and if the user has completed it.
		$prerequisite_id = (int) get_post_meta( $lesson_id, '_lesson_prerequisite', true );
		if (
			$prerequisite_id
			&& ! Sensei_Utils::user_completed_lesson( $prerequisite_id, $user_id )
		) {
			return false;
		}

		return true;

	}

	/**
	 * Check if the user has completed the quiz.
	 *
	 * @since 3.15.0
	 *
	 * @param int|null $quiz_id (Optional) The quiz post ID. Defaults to the current post ID.
	 * @param int|null $user_id (Optional) The user ID. Defaults to the current user ID.
	 *
	 * @return bool
	 */
	public static function is_quiz_completed( int $quiz_id = null, int $user_id = null ): bool {

		$quiz_id = $quiz_id ? $quiz_id : get_the_ID();
		$user_id = $user_id ? $user_id : get_current_user_id();

		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		// Check the lesson status.
		$lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		if ( $lesson_status ) {
			$lesson_status = is_array( $lesson_status ) ? $lesson_status[0] : $lesson_status;

			if ( 'ungraded' === $lesson_status->comment_approved ) {
				return true;
			}

			// Check for a quiz grade.
			$quiz_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );
			if ( '' !== $quiz_grade ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Filter the single title and add the Quiz to it.
	 *
	 * @param string $title
	 * @param int    $post_id title post id
	 *
	 * @return string $quiz_title
	 */
	public static function single_quiz_title( $title, $post_id = 0 ) {

		if ( 'quiz' === get_post_type( $post_id ) ) {

			$title_with_no_quizzes = $title;

			// if the title has quiz, remove it: legacy titles have the word quiz stored.
			if ( 1 < substr_count( strtoupper( $title_with_no_quizzes ), 'QUIZ' ) ) {

				// remove all possible appearances of quiz
				$title_with_no_quizzes = str_replace( 'quiz', '', $title );
				$title_with_no_quizzes = str_replace( 'Quiz', '', $title_with_no_quizzes );
				$title_with_no_quizzes = str_replace( 'QUIZ', '', $title_with_no_quizzes );

			}

			// translators: Placeholder is the quiz name with any instance of the word "quiz" removed.
			$title = sprintf( __( '%s Quiz', 'sensei-lms' ), $title_with_no_quizzes );

			/**
			 * hook document in class-woothemes-sensei-message.php
			 */
			$title = apply_filters( 'sensei_single_title', $title, get_post_type() );
		}

		return $title;

	}

	/**
	 * Initialize the quiz question loop on the single quiz template
	 *
	 * The function will create a global quiz loop variable.
	 *
	 * @since 1.9.0
	 */
	public static function start_quiz_questions_loop() {
		global $sensei_question_loop;

		// Initialise the questions loop object.
		$sensei_question_loop['current']         = -1;
		$sensei_question_loop['total']           = 0;
		$sensei_question_loop['questions']       = [];
		$sensei_question_loop['questions_asked'] = [];
		$sensei_question_loop['posts_per_page']  = -1;
		$sensei_question_loop['current_page']    = 1;
		$sensei_question_loop['total_pages']     = 1;

		$quiz_id             = get_the_ID();
		$pagination_settings = json_decode(
			get_post_meta( $quiz_id, '_pagination', true ),
			true
		);

		if ( ! empty( $pagination_settings['pagination_number'] ) ) {
			$sensei_question_loop['posts_per_page'] = (int) $pagination_settings['pagination_number'];

			// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used for pagination in the frontend.
			if ( ! empty( $_GET['quiz-page'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification
				$sensei_question_loop['current_page'] = max( 1, (int) $_GET['quiz-page'] );
			}
		}
		// Fetch the questions.
		$all_questions = Sensei()->lesson->lesson_quiz_questions( $quiz_id, 'publish' );

		if ( ! $all_questions ) {
			return;
		}

		$sensei_question_loop['questions_asked'] = wp_list_pluck( $all_questions, 'ID' );
		$sensei_question_loop['total']           = count( $all_questions );

		// Paginate the questions.
		if ( $sensei_question_loop['posts_per_page'] > 0 ) {
			$offset         = $sensei_question_loop['posts_per_page'] * ( $sensei_question_loop['current_page'] - 1 );
			$loop_questions = array_slice( $all_questions, $offset, $sensei_question_loop['posts_per_page'] );

			// Calculate the number of pages.
			$sensei_question_loop['total_pages'] = (int) ceil(
				$sensei_question_loop['total'] / $sensei_question_loop['posts_per_page']
			);
		} else {
			$loop_questions = $all_questions;
		}

		// Don't use pagination if quiz has been completed.
		$lesson_id = \Sensei_Utils::get_current_lesson();
		$status    = \Sensei_Utils::user_lesson_status( $lesson_id );

		$quiz_completed = $status && 'in-progress' !== $status->comment_approved;

		$sensei_question_loop['questions'] = $quiz_completed ? $all_questions : $loop_questions;
		$sensei_question_loop['quiz_id']   = $quiz_id;

	}

	/**
	 * Initialize the quiz question loop on the single quiz template
	 *
	 * The function will create a global quiz loop variable.
	 *
	 * @deprecated 3.10.0
	 *
	 * @since 1.9.0
	 */
	public static function stop_quiz_questions_loop() {

		_deprecated_function( __METHOD__, '3.10.0' );

		$sensei_question_loop                    = [];
		$sensei_question_loop['total']           = 0;
		$sensei_question_loop['questions']       = [];
		$sensei_question_loop['questions_asked'] = [];
		$sensei_question_loop['quiz_id']         = '';
		$sensei_question_loop['posts_per_page']  = -1;
		$sensei_question_loop['current_page']    = 1;
		$sensei_question_loop['total_pages']     = 1;

	}

	/**
	 * Output the title for the single quiz page
	 *
	 * @since 1.9.0
	 */
	public static function the_title() {
		?>
		 <header>

			 <h1>

				<?php
				/**
				 * Filter documented in class-sensei-messages.php the_title
				 */
				echo wp_kses_post( apply_filters( 'sensei_single_title', get_the_title( get_post() ), get_post_type( get_the_ID() ) ) );
				?>

			 </h1>

		 </header>

		 <?php
	}

	/**
	 * Output the sensei quiz status message.
	 *
	 * @param int $quiz_id quiz id.
	 */
	public static function the_user_status_message( $quiz_id ) {

		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );
		$status    = Sensei_Utils::sensei_user_quiz_status_message( $lesson_id, get_current_user_id() );
		$messages  = Sensei()->frontend->messages;
		$message   = '<div class="sensei-message ' . esc_attr( $status['box_class'] ) . '">' . wp_kses_post( $status['message'] ) . '</div>';
		$messages  = Sensei()->frontend->messages;

		if ( ! empty( $messages ) ) {
			$message .= wp_kses_post( $messages );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped above.
		echo $message;
	}

	/**
	 * Outputs the quiz hidden fields.
	 *
	 * @since 3.15.0
	 */
	public static function output_quiz_hidden_fields() {

		global $sensei_question_loop;

		foreach ( $sensei_question_loop['questions_asked'] as $question_id ) {
			?>
			<input type="hidden" name="questions_asked[]" form="sensei-quiz-form" value="<?php echo esc_attr( $question_id ); ?>">
			<?php
		}

	}

	/**
	 * Displays the quiz questions pagination when enabled from the quiz pagination settings.
	 * Replaces the default action buttons.
	 *
	 * @since 3.15.0
	 */
	public static function the_quiz_pagination() {

		global $sensei_question_loop;

		if ( $sensei_question_loop['total_pages'] <= 1 ) {
			return;
		}

		wp_enqueue_script( 'sensei-stop-double-submission' );

		// Remove the default action buttons. We will replace them in the pagination template.
		remove_action( 'sensei_single_quiz_questions_after', array( 'Sensei_Quiz', 'action_buttons' ), 10 );

		// Load the pagination template.
		Sensei_Templates::get_template( 'single-quiz/pagination.php' );

	}

	/**
	 * Rendering html element that will be replaced with Progress Bar.
	 *
	 * @since 3.15.0
	 */
	public static function the_quiz_progress_bar() {
		$quiz_id             = get_the_ID();
		$pagination_settings = json_decode(
			get_post_meta( $quiz_id, '_pagination', true ),
			true
		);

		global $sensei_question_loop;

		// Make sure the quiz is paginated and the progress bar enabled.
		if ( $sensei_question_loop['total_pages'] <= 1 || empty( $pagination_settings['show_progress_bar'] ) ) {
			return;
		}

		$attributes = [
			'radius'                   => $pagination_settings['progress_bar_radius'],
			'height'                   => $pagination_settings['progress_bar_height'],
			'customBarColor'           => empty( $pagination_settings['progress_bar_color'] ) ? '' : $pagination_settings['progress_bar_color'],
			'customBarBackgroundColor' => empty( $pagination_settings['progress_bar_background'] ) ? '' : $pagination_settings['progress_bar_background'],
		];

		Sensei()->assets->enqueue( 'sensei-shared-blocks-style', 'blocks/shared-style.scss' );

		echo wp_kses_post( ( new Sensei_Block_Quiz_Progress() )->render( $attributes ) );
	}

	/**
	 * The quiz action buttons needed to output quiz
	 * action such as reset complete and save.
	 *
	 * @since 1.3.0
	 */
	public static function action_buttons() {

		if ( ! self::is_quiz_available() ) {
			return;
		}

		$lesson_id         = Sensei()->quiz->get_lesson_id();
		$is_quiz_completed = self::is_quiz_completed();
		$is_reset_allowed  = self::is_reset_allowed( $lesson_id );
		$has_actions       = $is_reset_allowed || ! $is_quiz_completed;

		if ( ! $has_actions ) {
			return;
		}

		$button_inline_styles = self::get_button_inline_styles();

		wp_enqueue_script( 'sensei-stop-double-submission' );
		?>

		<div class="sensei-quiz-actions">
			<?php if ( ! $is_quiz_completed ) : ?>
				<div class="sensei-quiz-actions-primary wp-block-buttons">
					<div class="sensei-quiz-action wp-block-button">
						<button
							type="submit"
							name="quiz_complete"
							form="sensei-quiz-form"
							class="wp-block-button__link button quiz-submit complete sensei-stop-double-submission"
							style="<?php echo esc_attr( $button_inline_styles ); ?>"
						>
							<?php esc_attr_e( 'Complete', 'sensei-lms' ); ?>
						</button>

						<input type="hidden" name="woothemes_sensei_complete_quiz_nonce" form="sensei-quiz-form" id="woothemes_sensei_complete_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_quiz_nonce' ) ); ?>" />
					</div>
				</div>
			<?php endif ?>

			<div class="sensei-quiz-actions-secondary">
				<?php if ( $is_reset_allowed ) : ?>
					<div class="sensei-quiz-action">
						<button type="submit" name="quiz_reset" form="sensei-quiz-form" class="quiz-submit reset sensei-stop-double-submission">
							<?php esc_attr_e( 'Reset', 'sensei-lms' ); ?>
						</button>

						<input type="hidden" name="woothemes_sensei_reset_quiz_nonce" form="sensei-quiz-form" id="woothemes_sensei_reset_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_reset_quiz_nonce' ) ); ?>" />
					</div>
				<?php endif ?>

				<?php if ( ! $is_quiz_completed ) : ?>
					<div class="sensei-quiz-action">
						<button type="submit" name="quiz_save" form="sensei-quiz-form" class="quiz-submit save sensei-stop-double-submission">
							<?php esc_attr_e( 'Save', 'sensei-lms' ); ?>
						</button>

						<input type="hidden" name="woothemes_sensei_save_quiz_nonce" form="sensei-quiz-form" id="woothemes_sensei_save_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_save_quiz_nonce' ) ); ?>" />
					</div>
				<?php endif ?>
			</div>
		</div>
		<?php

	}

	/**
	 * Get the quiz button inline styles.
	 *
	 * @since 3.15.0
	 *
	 * @param int|null $quiz_id (Optional) The quiz post ID. Defaults to the current post ID.
	 *
	 * @return string
	 */
	public static function get_button_inline_styles( int $quiz_id = null ): string {

		$quiz_id = $quiz_id ? $quiz_id : get_the_ID();

		$button_text_color       = get_post_meta( $quiz_id, '_button_text_color', true );
		$button_background_color = get_post_meta( $quiz_id, '_button_background_color', true );

		$styles = [];

		if ( $button_text_color ) {
			$styles[] = sprintf( 'color: %s', $button_text_color );
		}

		if ( $button_background_color ) {
			$styles[] = sprintf( 'background-color: %s', $button_background_color );
		}

		return implode( '; ', $styles );

	}

	/**
	 * Fetch the quiz grade
	 *
	 * @since 1.9.0
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 *
	 * @return double $user_quiz_grade
	 */
	public static function get_user_quiz_grade( int $lesson_id, int $user_id ): float {

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
		if ( ! $quiz_id ) {
			return 0;
		}

		$quiz_submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
		if ( ! $quiz_submission ) {
			return 0;
		}

		return (float) $quiz_submission->get_final_grade();
	}

	/**
	 * Check the quiz reset property for a given lesson's quiz.
	 *
	 * The data is stored on the quiz but going forward the quiz post
	 * type will be retired, hence the lesson_id is a require parameter.
	 *
	 * @since 1.9.0
	 *
	 * @param int $lesson_id
	 * @return bool
	 */
	public static function is_reset_allowed( $lesson_id ) {

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		$reset_allowed = get_post_meta( $quiz_id, '_enable_quiz_reset', true );
		// backwards compatibility.
		if ( 'on' === $reset_allowed ) {
			$reset_allowed = 1;
		}

		return (bool) $reset_allowed;

	}

	/**
	 * Get a quiz option's value.
	 *
	 * @since 3.14.0
	 *
	 * @param int    $lesson_id Lesson ID.
	 * @param string $option    Option name.
	 * @param mixed  $default   Default value to be returned if the option is unset.
	 *
	 * @return mixed
	 */
	public static function get_option( $lesson_id, $option, $default = null ) {

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$option  = get_post_meta( $quiz_id, '_' . $option, true );

		if ( ! $option ) {
			return $default;
		} else {
			return 'yes' === $option;
		}

	}

	/**
	 * Checking if password is required.
	 *
	 * @param int $lesson_id lesson id.
	 *
	 * @return bool
	 */
	public static function is_pass_required( $lesson_id ) {

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		$reset_allowed = get_post_meta( $quiz_id, '_pass_required', true );
		// backwards compatibility.
		if ( 'on' === $reset_allowed ) {
			$reset_allowed = 1;
		}

		return (bool) $reset_allowed;
	}

	/**
	 * Maybe delete the quiz post after checking if the post exists.
	 *
	 * @since 1.9.5
	 *
	 * @param integer $post_id of the post being permanently deleted.
	 */
	public function maybe_delete_quiz( $post_id ) {

		$quiz_id = Sensei()->lesson->lesson_quizzes( $post_id );

		if ( empty( $quiz_id ) || 'lesson' !== get_post_type( $post_id ) ) {
			return;
		}

		wp_delete_post( $quiz_id );

	}

	/**
	 * Merge quiz answers with questions asked.
	 *
	 * Also, remove any question_ids not part of
	 * the question set for this lesson quiz.
	 *
	 * @param  array $questions_answered The user answers.
	 * @param  array $questions_asked    The ID's of all the asked quiz questions.
	 * @return array
	 */
	private function merge_quiz_answers_with_questions_asked( array $questions_answered, array $questions_asked ): array {
		$merged = [];

		foreach ( array_unique( $questions_asked ) as $question_id ) {
			$merged[ $question_id ] = $questions_answered[ $question_id ] ?? '';
		}

		return $merged;
	}

	/**
	 * Get all the questions of a quiz or get all complete questions if filtering flag is true and is not preview.
	 *
	 * @param int    $quiz_id The quiz id.
	 * @param string $post_status Question post status.
	 * @param string $orderby Question order by.
	 * @param string $order Question order.
	 * @param bool   $filter_incomplete_questions Whether incomplete questions must be filtered out or not. Default false.
	 * @return WP_Post[]
	 */
	public function get_questions( $quiz_id, $post_status = 'any', $orderby = 'meta_value_num title', $order = 'ASC', $filter_incomplete_questions = false ) : array {

		// Set the default question order if it has not already been set for this quiz.
		Sensei()->lesson->set_default_question_order( $quiz_id );

		// Get all questions and multiple questions.
		$question_query_args = array(
			'post_type'        => array( 'question', 'multiple_question' ),
			'posts_per_page'   => - 1,
			'meta_key'         => '_quiz_question_order' . $quiz_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Query limited by the number of questions.
			'orderby'          => $orderby,
			'order'            => $order,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Query limited by the number of questions.
				array(
					'key'   => '_quiz_id',
					'value' => $quiz_id,
				),
			),
			'post_status'      => $post_status,
			'suppress_filters' => 0,
		);

		$questions_query = new WP_Query( $question_query_args );

		$posts = $questions_query->posts;

		if ( is_preview() || ! $filter_incomplete_questions ) {
			return $posts;
		}
		return $this->filter_out_incomplete_questions( $posts );
	}

	/**
	 * Filter out incomplete questions.
	 *
	 * @param array $questions  All questions.
	 *
	 * @return array
	 */
	private function filter_out_incomplete_questions( array $questions ): array {
		$filtered_questions = [];
		foreach ( $questions as $question ) {
			$question_id   = $question->ID;
			$question_type = Sensei()->question->get_question_type( $question_id );
			if ( 'multiple-choice' === $question_type && ! $this->is_multiple_choice_question_complete( $question_id ) ) {
				continue;
			}
			if ( 'gap-fill' === $question_type && ! $this->is_gap_fill_question_complete( $question_id ) ) {
				continue;
			}
			array_push( $filtered_questions, $question );
		}
		return $filtered_questions;
	}
	/**
	 * Return true if multiple choice question has valid answers
	 *
	 * @param int $question_id  question id.
	 *
	 * @return boolean
	 */
	private function is_multiple_choice_question_complete( int $question_id ): bool {
		$right_answers = get_post_meta( $question_id, '_question_right_answer', true );
		$wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true );

		// Multiple choice question is incomplete if there isn't at least one right and one wrong answer.
		if ( ! is_array( $right_answers ) || count( $right_answers ) < 1 || ! is_array( $wrong_answers ) || count( $wrong_answers ) < 1 ) {
			return false;
		}
		// Wrong or right answers values can't be whitespace.
		return ! count(
			array_filter(
				array_merge( $right_answers, $wrong_answers ),
				function( $value ) {
					return '' === trim( $value );
				}
			)
		);
	}

	/**
	 * Return true if gap fill question has valid answers
	 *
	 * @param int $question_id  question id.
	 *
	 * @return boolean
	 */
	private function is_gap_fill_question_complete( int $question_id ): bool {
		$gapfill_array = explode( '||', get_post_meta( $question_id, '_question_right_answer', true ) );
		$text_before   = $gapfill_array[0] ?? '';
		$answer_text   = $gapfill_array[1] ?? '';
		$text_after    = $gapfill_array[2] ?? '';
		// Gap fill question is incomplete if gap text is null or whitespace, or if text before or text after are null or whitespace.
		return ! ( '' === trim( $answer_text ) || ( '' === trim( $text_before ) && '' === trim( $text_after ) ) );
	}

	/**
	 * Sets the questions of a quiz. It handles all related quiz and question meta.
	 *
	 * @param int   $quiz_id      The quiz id.
	 * @param array $question_ids The array of questions ids.
	 */
	public function set_questions( int $quiz_id, array $question_ids ) {
		$old_question_order = get_post_meta( $quiz_id, '_question_order', true );
		$old_question_order = empty( $old_question_order ) ? [] : array_map( 'intval', $old_question_order );

		if ( $question_ids === $old_question_order ) {
			return;
		}

		$added_questions   = array_diff( $question_ids, $old_question_order );
		$removed_questions = array_diff( $old_question_order, $question_ids );

		// Delete question meta from the questions that were removed from the quiz.
		if ( ! empty( $removed_questions ) ) {
			$this->delete_quiz_question_meta( $quiz_id, $removed_questions );
		}

		if ( empty( $question_ids ) ) {
			delete_post_meta( $this->get_lesson_id( $quiz_id ), '_quiz_has_questions' );
			delete_post_meta( $quiz_id, '_question_order' );

			return;
		}

		$question_count = 1;
		foreach ( $question_ids as $question_id ) {
			update_post_meta( $question_id, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $question_count );
			$question_count++;
		}

		foreach ( $added_questions as $added_question ) {
			add_post_meta( $added_question, '_quiz_id', $quiz_id, false );
		}

		update_post_meta( $this->get_lesson_id( $quiz_id ), '_quiz_has_questions', '1' );
		update_post_meta( $quiz_id, '_question_order', array_map( 'strval', $question_ids ) );
	}

	/**
	 * Check if a quiz's lesson has Sensei blocks.
	 *
	 * @param int|WP_Post $quiz Quiz ID or post object.
	 *
	 * @return bool
	 */
	public function has_sensei_blocks( $quiz = null ) {
		$lesson_id = $this->get_lesson_id( $quiz );

		return Sensei()->lesson->has_sensei_blocks( $lesson_id );
	}

	/**
	 * Add quiz-blocks class for quiz page with block-based lesson.
	 *
	 * @param array $classes Existing classes.
	 *
	 * @return array Modified classes.
	 */
	public function add_quiz_blocks_class( $classes ) {
		if ( 'quiz' === get_post_type() && $this->has_sensei_blocks() ) {
			return array_merge( $classes, [ 'quiz-blocks' ] );
		}

		return $classes;
	}

	/**
	 * Helper method to delete all related meta of quiz's questions.
	 *
	 * @param int   $quiz_id      The quiz id.
	 * @param array $question_ids A list of quiz ids to remove the meta from.
	 */
	private function delete_quiz_question_meta( $quiz_id, $question_ids = null ) {
		if ( null === $question_ids ) {
			$question_ids = get_post_meta( $quiz_id, '_question_order', true );
		}

		if ( empty( $question_ids ) ) {
			return;
		}

		foreach ( $question_ids as $question_id ) {
			delete_post_meta( $question_id, '_quiz_id', $quiz_id );
			delete_post_meta( $question_id, '_quiz_question_order' . $quiz_id );

			if (
				'multiple_question' === get_post_type( $question_id )
				&& empty( array_filter( get_post_meta( $question_id, '_quiz_id', false ) ) )
			) {
				wp_delete_post( $question_id, true );
			}
		}
	}

	/**
	 * Update the quiz author.
	 *
	 * @param int $quiz_id       Quiz post ID.
	 * @param int $new_author_id New author.
	 */
	public function update_quiz_author( int $quiz_id, int $new_author_id ) {
		if ( 'quiz' !== get_post_type( $quiz_id ) ) {
			return;
		}

		wp_update_post(
			[
				'ID'          => $quiz_id,
				'post_author' => $new_author_id,
			]
		);

		// Update quiz question author if possible.
		$questions = Sensei()->quiz->get_questions( $quiz_id );
		foreach ( $questions as $question ) {
			if ( $new_author_id === (int) $question->post_author ) {
				continue;
			}

			Sensei()->question->maybe_update_question_author( $question->ID, $new_author_id );
		}
	}

	/**
	 * Replace all pagination links with buttons (<a> => <button>).
	 *
	 * @since 3.15.0
	 *
	 * @param string $html The pagination html.
	 *
	 * @return string
	 */
	public function replace_pagination_links_with_buttons( $html ): string {
		return preg_replace(
			'/<a.+?href="(.+?)">(.+?)<\/a>/',
			'<button type="submit" name="quiz_target_page" form="sensei-quiz-form" value="$1" class="page-numbers">$2</button>',
			$html
		);
	}
}



/**
 * Class WooThemes_Sensei_Quiz
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Quiz extends Sensei_Quiz{}
