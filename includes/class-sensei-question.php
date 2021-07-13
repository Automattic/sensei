<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Question Class
 *
 * All functionality pertaining to the questions post type in Sensei.
 *
 * @package Assessment
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Question {
	public $token;
	public $meta_fields;

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->token          = 'question';
		$this->question_types = $this->question_types();
		$this->meta_fields    = array( 'question_right_answer', 'question_wrong_answers' );
		if ( is_admin() ) {
			// Custom Write Panel Columns
			add_filter( 'manage_edit-question_columns', array( $this, 'add_column_headings' ), 20, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );
			add_action( 'add_meta_boxes', array( $this, 'question_edit_panel_metabox' ), 10, 2 );

			// Quesitno list table filters
			add_action( 'restrict_manage_posts', array( $this, 'filter_options' ) );
			add_filter( 'request', array( $this, 'filter_actions' ) );

			add_action( 'save_post_question', array( $this, 'save_question' ), 10, 1 );
		}

		add_action( 'sensei_question_initial_publish', [ $this, 'log_initial_publish_event' ] );
	}

	public function question_types() {
		$types = array(
			'multiple-choice' => __( 'Multiple Choice', 'sensei-lms' ),
			'boolean'         => __( 'True/False', 'sensei-lms' ),
			'gap-fill'        => __( 'Gap Fill', 'sensei-lms' ),
			'single-line'     => __( 'Single Line', 'sensei-lms' ),
			'multi-line'      => __( 'Multi Line', 'sensei-lms' ),
			'file-upload'     => __( 'File Upload', 'sensei-lms' ),
		);

		/**
		 * Filter the question types.
		 *
		 * @hook sensei_question_types
		 *
		 * @param {string[]} $types Question types.
		 * @return {string[]} Associative array of question types.
		 */
		return apply_filters( 'sensei_question_types', $types );
	}

	/**
	 * Add column headings to the "question" post list screen,
	 * while moving the existing ones to the end.
	 *
	 * @access private
	 * @since  1.3.0
	 * @param  array $defaults  Array of column header labels keyed by column ID.
	 * @return array            Updated array of column header labels keyed by column ID.
	 */
	public function add_column_headings( $defaults ) {
		$new_columns                      = [];
		$new_columns['cb']                = '<input type="checkbox" />';
		$new_columns['title']             = _x( 'Question', 'column name', 'sensei-lms' );
		$new_columns['question-type']     = _x( 'Type', 'column name', 'sensei-lms' );
		$new_columns['question-category'] = _x( 'Categories', 'column name', 'sensei-lms' );
		if ( isset( $defaults['date'] ) ) {
			$new_columns['date'] = $defaults['date'];
		}

		// Unset renamed existing columns.
		unset( $defaults['taxonomy-question-type'] );
		unset( $defaults['taxonomy-question-category'] );

		// Add all remaining columns at the end.
		foreach ( $defaults as $column_key => $column_value ) {
			if ( ! isset( $new_columns[ $column_key ] ) ) {
				$new_columns[ $column_key ] = $column_value;
			}
		}

		return $new_columns;
	}


	/**
	 * Add data for our newly-added custom columns.
	 *
	 * @access public
	 * @since  1.3.0
	 * @param  string $column_name
	 * @param  int    $id
	 * @return void
	 */
	public function add_column_data( $column_name, $id ) {
		switch ( $column_name ) {

			case 'id':
				echo esc_html( $id );
				break;

			case 'question-type':
				$question_type = strip_tags( get_the_term_list( $id, 'question-type', '', ', ', '' ) );
				$output        = '&mdash;';
				if ( isset( $this->question_types[ $question_type ] ) ) {
					$output = $this->question_types[ $question_type ];
				}
				echo esc_html( $output );
				break;

			case 'question-category':
				$output = strip_tags( get_the_term_list( $id, 'question-category', '', ', ', '' ) );
				if ( ! $output ) {
					$output = '&mdash;';
				}
				echo esc_html( $output );
				break;

			default:
				break;

		}

	}

	public function question_edit_panel_metabox( $post_type, $post ) {
		if ( in_array( $post_type, array( 'question', 'multiple_question' ) ) ) {

			$metabox_title = __( 'Question', 'sensei-lms' );

			if ( isset( $post->ID ) ) {

				$question_type = Sensei()->question->get_question_type( $post->ID );

				if ( $question_type ) {
					$type = $this->question_types[ $question_type ];
					if ( $type ) {
						$metabox_title = $type;
					}
				}
			}

			add_meta_box( 'question-lessons-panel', __( 'Quizzes', 'sensei-lms' ), array( $this, 'question_lessons_panel' ), 'question', 'side', 'default' );

			if ( ! Sensei()->quiz->is_block_based_editor_enabled() ) {
				add_meta_box( 'multiple-question-lessons-panel', __( 'Quizzes', 'sensei-lms' ), array( $this, 'question_lessons_panel' ), 'multiple_question', 'side', 'default' );
				add_meta_box( 'question-edit-panel', $metabox_title, array( $this, 'question_edit_panel' ), 'question', 'normal', 'high' );
			}
		}
	}

	public function question_edit_panel() {
		global  $post, $pagenow;

		if ( Sensei()->quiz->is_block_based_editor_enabled() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( Sensei()->lesson, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( Sensei()->lesson, 'enqueue_styles' ) );

		$html = '<div id="lesson-quiz" class="single-question"><div id="add-question-main">';

		if ( 'post-new.php' == $pagenow ) {

			$html .= '<div id="add-question-actions">';
			$html .= Sensei()->lesson->quiz_panel_add( 'question' );
			$html .= '</div>';

		} else {
			$question_id = $post->ID;

			$question_type = Sensei()->question->get_question_type( $post->ID );

			$html .= '<div id="add-question-metadata"><table class="widefat">';
			$html .= Sensei()->lesson->quiz_panel_question( $question_type, 0, $question_id, 'question' );
			$html .= '</table></div>';
		}

		$html .= '</div></div>';

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'button'   => array(
						'class'                     => array(),
						'data-uploader-button-text' => array(),
						'data-uploader-title'       => array(),
						'id'                        => array(),
					),
					'input'    => array(
						'checked'     => array(),
						'class'       => array(),
						'id'          => array(),
						'max'         => array(),
						'min'         => array(),
						'name'        => array(),
						'placeholder' => array(),
						'rel'         => array(),
						'size'        => array(),
						'type'        => array(),
						'value'       => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'class' => array(),
						'for'   => array(),
					),
					'option'   => array(
						'value' => array(),
					),
					'select'   => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
					),
					// Explicitly allow textarea tag for WP.com.
					'textarea' => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
						'rows'  => array(),
					),
				)
			)
		);
	}

	public function question_lessons_panel() {
		global $post;

		// translators: Placeholders are an opening and closing <em> tag.
		$no_lessons = sprintf( __( '%1$sThis question does not appear in any quizzes yet.%2$s', 'sensei-lms' ), '<em>', '</em>' );

		if ( ! isset( $post->ID ) ) {
			echo wp_kses_post( $no_lessons );
			return;
		}

		// This retrieves those quizzes the question is directly connected to.
		$quizzes = get_post_meta( $post->ID, '_quiz_id', false );

		// Collate all 'multiple_question' quizzes the question is part of.
		$categories_of_question = wp_get_post_terms( $post->ID, 'question-category', array( 'fields' => 'ids' ) );
		if ( ! empty( $categories_of_question ) ) {
			foreach ( $categories_of_question as $term_id ) {
				$qargs            = array(
					'fields'           => 'ids',
					'post_type'        => 'multiple_question',
					'posts_per_page'   => -1,
					'meta_query'       => array(
						array(
							'key'   => 'category',
							'value' => $term_id,
						),
					),
					'post_status'      => 'any',
					'suppress_filters' => 0,
				);
				$cat_question_ids = get_posts( $qargs );
				foreach ( $cat_question_ids as $cat_question_id ) {
					$cat_quizzes = get_post_meta( $cat_question_id, '_quiz_id', false );
					$quizzes     = array_merge( $quizzes, $cat_quizzes );
				}
			}
			$quizzes = array_unique( array_filter( $quizzes ) );
		}

		if ( 0 == count( $quizzes ) ) {
			echo wp_kses_post( $no_lessons );
			return;
		}

		$lessons = false;

		foreach ( $quizzes as $quiz ) {

			$lesson_id = get_post_meta( $quiz, '_quiz_lesson', true );

			if ( ! $lesson_id ) {
				continue;
			}

			$lessons[ $lesson_id ]['title'] = get_the_title( $lesson_id );
			$lessons[ $lesson_id ]['link']  = admin_url( 'post.php?post=' . $lesson_id . '&action=edit' );
		}

		if ( ! $lessons ) {
			echo wp_kses_post( $no_lessons );
			return;
		}

		$html = '<ul>';

		foreach ( $lessons as $id => $lesson ) {
			$html .= '<li><a href="' . esc_url( $lesson['link'] ) . '">' . esc_html( $lesson['title'] ) . '</a></li>';
		}

		$html .= '</ul>';

		echo wp_kses_post( $html );

	}

	public function save_question( $post_id = 0 ) {
		/*
		 * Ensure that we are on the `post` screen. If so, we can trust that
		 * nonce verification has been performed.
		 */
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		// Setup the data for saving.
		// phpcs:ignore WordPress.Security.NonceVerification
		$data                = $_POST;
		$data['quiz_id']     = 0;
		$data['question_id'] = $post_id;

		if ( ! wp_is_post_revision( $post_id ) ) {

			// Unhook function to prevent infinite loops
			remove_action( 'save_post_question', array( $this, 'save_question' ) );

			// Update question data
			Sensei()->lesson->lesson_save_question( $data, 'question' );

			// Re-hook same function
			add_action( 'save_post_question', array( $this, 'save_question' ) );
		}

		return;
	}

	/**
	 * Add options to filter the questions list table
	 *
	 * @return void
	 */
	public function filter_options() {
		global $typenow;

		if ( is_admin() && 'question' == $typenow ) {

			$output = '';

			// Question type
			$selected     = isset( $_GET['question_type'] ) ? $_GET['question_type'] : '';
			$type_options = '<option value="">' . esc_html__( 'All types', 'sensei-lms' ) . '</option>';
			foreach ( $this->question_types as $label => $type ) {
				$type_options .= '<option value="' . esc_attr( $label ) . '" ' . selected( $selected, $label, false ) . '>' . esc_html( $type ) . '</option>';
			}

			$output .= '<select name="question_type" id="dropdown_question_type">';
			$output .= $type_options;
			$output .= '</select>';

			// Question category
			$cats = get_terms( 'question-category', array( 'hide_empty' => false ) );
			if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
				$selected    = isset( $_GET['question_cat'] ) ? $_GET['question_cat'] : '';
				$cat_options = '<option value="">' . esc_html__( 'All categories', 'sensei-lms' ) . '</option>';
				foreach ( $cats as $cat ) {
					$cat_options .= '<option value="' . esc_attr( $cat->slug ) . '" ' . selected( $selected, $cat->slug, false ) . '>' . esc_html( $cat->name ) . '</option>';
				}

				$output .= '<select name="question_cat" id="dropdown_question_cat">';
				$output .= $cat_options;
				$output .= '</select>';
			}

			$allowed_html = array(
				'option' => array(
					'selected' => array(),
					'value'    => array(),
				),
				'select' => array(
					'id'   => array(),
					'name' => array(),
				),
			);

			echo wp_kses( $output, $allowed_html );
		}
	}

	/**
	 * Filter questions list table
	 *
	 * @param  array $request Current request
	 * @return array          Modified request
	 */
	public function filter_actions( $request ) {
		global $typenow;

		if ( is_admin() && 'question' == $typenow ) {

			// Question type
			$question_type = isset( $_GET['question_type'] ) ? $_GET['question_type'] : '';
			if ( $question_type ) {
				$type_query             = array(
					'taxonomy' => 'question-type',
					'terms'    => $question_type,
					'field'    => 'slug',
				);
				$request['tax_query'][] = $type_query;
			}

			// Question category
			$question_cat = isset( $_GET['question_cat'] ) ? $_GET['question_cat'] : '';
			if ( $question_cat ) {
				$cat_query              = array(
					'taxonomy' => 'question-category',
					'terms'    => $question_cat,
					'field'    => 'slug',
				);
				$request['tax_query'][] = $cat_query;
			}
		}

		return $request;
	}

	/**
	 * Get the type of question by id
	 *
	 * This function uses the post terms to determine which question type
	 * the passed question id belongs to.
	 *
	 * @since 1.7.4
	 *
	 * @param int $question_id
	 *
	 * @return string $question_type | bool
	 */
	public function get_question_type( $question_id ) {

		if ( empty( $question_id ) || ! intval( $question_id ) > 0
			|| 'question' != get_post_type( $question_id ) ) {
			return false;
		}

		$question_type  = 'multiple-choice';
		$question_types = wp_get_post_terms( $question_id, 'question-type' );
		foreach ( $question_types as $type ) {
			$question_type = $type->slug;
		}

		return $question_type;

	}

	/**
	 * Given a question ID, return the grade that can be achieved.
	 *
	 * @since 1.9
	 *
	 * @param int $question_id
	 *
	 * @return int $question_grade | bool
	 */
	public function get_question_grade( $question_id ) {

		if ( empty( $question_id ) || ! intval( $question_id ) > 0
			|| 'question' != get_post_type( $question_id ) ) {
			return false;
		}

		$question_grade_raw = get_post_meta( $question_id, '_question_grade', true );
		// If not set then default to 1...
		if ( false === $question_grade_raw || $question_grade_raw == '' ) {
			$question_grade = 1;
		}
		// ...but allow a grade of 0 for non-marked questions
		else {
			$question_grade = intval( $question_grade_raw );
		}

		/**
		 * Filter the grade for the given question.
		 *
		 * @since 1.9.6
		 * @hook sensei_get_question_grade
		 *
		 * @param {int} $question_grade Question grade.
		 * @param {int} $question_id    Question ID.
		 * @return {int} Question grade.
		 */
		return apply_filters( 'sensei_get_question_grade', $question_grade, $question_id );
	}


	/**
	 * This function simply loads the question type template
	 *
	 * @since 1.9.0
	 * @param string $question_type The question type.
	 */
	public static function load_question_template( $question_type ) {
		$old_template_name = 'single-quiz/question_type-' . $question_type . '.php';
		$new_template_name = 'single-quiz/question-type-' . $question_type . '.php';

		/*
		 * For backwards compatibility, try to locate and load the file with the
		 * old name first.
		 */
		if ( Sensei_Templates::locate_template( $old_template_name ) ) {
			Sensei_Templates::get_template( $old_template_name );
		} else {
			Sensei_Templates::get_template( $new_template_name );
		}
	}

	/**
	 * Echo the sensei question title.
	 *
	 * @uses Sensei_Question::get_the_question_title
	 *
	 * @since 1.9.0
	 * @param $question_id
	 */
	public static function the_question_title( $question_id ) {
		echo wp_kses_post( self::get_the_question_title( $question_id ) );
	}

	/**
	 * Generate the question title with it's grade.
	 *
	 * @since 1.9.0
	 *
	 * @param $question_id
	 * @return string
	 */
	public static function get_the_question_title( $question_id ) {
		/**
		 * Filter the question title.
		 *
		 * @since 1.3.0
		 * @hook sensei_question_title
		 *
		 * @param {string} $title Question title.
		 * @return {string} Question title.
		 */
		$title = apply_filters( 'sensei_question_title', get_the_title( $question_id ) );

		/** This filter is documented in includes/class-sensei-messages.php */
		$title = apply_filters( 'sensei_single_title', $title, 'question' );

		$question_grade = Sensei()->question->get_question_grade( $question_id );

		$title_html  = '<span class="question question-title">';
		$title_html .= wp_kses_post( $title );
		$title_html .= Sensei()->view_helper->format_question_points( $question_grade );
		$title_html .= '</span>';

		return $title_html;
	}

	/**
	 * Tech the question description
	 *
	 * @param $question_id
	 * @return string
	 */
	public static function get_the_question_description( $question_id ) {

		$question = get_post( $question_id );

		/**
		 * Already documented within WordPress Core
		 */
		return apply_filters( 'the_content', wp_kses_post( $question->post_content ) );
	}

	/**
	 * Output the question description
	 *
	 * @since 1.9.0
	 * @param $question_id
	 */
	public static function the_question_description( $question_id ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method (before `the_content` filter).
		echo self::get_the_question_description( $question_id );
	}

	/**
	 * Get the questions media markup
	 *
	 * @since 1.9.0
	 * @param $question_id
	 * @return string
	 */
	public static function get_the_question_media( $question_id ) {

		$question_media      = get_post_meta( $question_id, '_question_media', true );
		$question_media_link = '';
		if ( 0 < intval( $question_media ) ) {
			$mimetype = get_post_mime_type( $question_media );
			if ( $mimetype ) {
				$mimetype_array = explode( '/', $mimetype );
				if ( isset( $mimetype_array[0] ) && $mimetype_array[0] ) {
					$question_media_type        = $mimetype_array[0];
					$question_media_url         = wp_get_attachment_url( $question_media );
					$attachment                 = get_post( $question_media );
					$question_media_title       = $attachment->post_title;
					$question_media_description = $attachment->post_content;
					switch ( $question_media_type ) {
						case 'image':
							/**
							 * Filter the size of the question image.
							 *
							 * @hook sensei_question_image_size
							 *
							 * @param {string} $size        Image size.
							 * @param {int}    $question_id Question ID.
							 * @return {string} Image size.
							 */
							$image_size          = apply_filters( 'sensei_question_image_size', 'medium', $question_id );
							$attachment_src      = wp_get_attachment_image_src( $question_media, $image_size );
							$question_media_link = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank"><img src="' . esc_url( $attachment_src[0] ) . '" width="' . esc_attr( $attachment_src[1] ) . '" height="' . esc_attr( $attachment_src[2] ) . '" /></a>';
							break;

						case 'audio':
							$question_media_link = wp_audio_shortcode( array( 'src' => $question_media_url ) );
							break;

						case 'video':
							$question_media_link = wp_video_shortcode( array( 'src' => $question_media_url ) );
							break;

						default:
							$question_media_filename = basename( $question_media_url );
							$question_media_link     = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank">' . esc_html( $question_media_filename ) . '</a>';
							break;
					}
				}
			}
		}

		$output = '';
		if ( $question_media_link ) {

				$output .= '<div class="question_media_display">';
				$output .= self::question_media_kses( $question_media_link );
				$output .= '<dl>';

			if ( ! empty( $question_media_title ) ) {

				$output .= '<dt>' . wp_kses_post( $question_media_title ) . '</dt>';

			}

			if ( ! empty( $question_media_description ) ) {

				$output .= '<dd>' . wp_kses_post( $question_media_description ) . '</dd>';

			}

				$output .= '</dl>';
				$output .= '</div>';

		}

		return $output;

	}

	/**
	 * Output the question media
	 *
	 * @since 1.9.0
	 * @param string $question_id
	 */
	public static function the_question_media( $question_id ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::question_media_kses( self::get_the_question_media( $question_id ) );
	}

	/**
	 * Special kses processing for media output to allow 'source' video tag.
	 *
	 * @since 3.0.0
	 * @param string $source_string
	 * @return string with allowed html elements
	 */
	private static function question_media_kses( $source_string ) {
		$source_tag   = array(
			'source' => array(
				'type' => true,
				'src'  => true,
			),
		);
		$allowed_html = array_merge( $source_tag, wp_kses_allowed_html( 'post' ) );

		return wp_kses( $source_string, $allowed_html );
	}

	/**
	 * Output a special field for the question needed for question submission.
	 *
	 * @since 1.9.0
	 *
	 * @param $question_id
	 */
	public static function the_question_hidden_fields( $question_id ) {
		?>

			<input type="hidden" name="question_id_<?php echo esc_attr( $question_id ); ?>" value="<?php echo esc_attr( $question_id ); ?>" />
			<input type="hidden" name="questions_asked[]" value="<?php echo esc_attr( $question_id ); ?>" />

		<?php
	}

	/**
	 * This function can only be run withing the single quiz question loop
	 *
	 * @since 1.9.0
	 * @param $question_id
	 */
	public static function answer_feedback_notes( $question_id ) {

		// IDS
		$quiz_id   = get_the_ID();
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		// Make sure this user has submitted answers before we show anything
		$user_answers = Sensei()->quiz->get_user_answers( $lesson_id, get_current_user_id() );
		if ( empty( $user_answers ) ) {
			return;
		}

		// Data to check before showing feedback
		$user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, get_current_user_id() );
		$user_quiz_grade    = Sensei_Quiz::get_user_quiz_grade( $lesson_id, get_current_user_id() );
		$reset_quiz_allowed = Sensei_Quiz::is_reset_allowed( $lesson_id );
		$quiz_graded        = isset( $user_lesson_status->comment_approved ) && ! in_array( $user_lesson_status->comment_approved, array( 'ungraded', 'in-progress' ) );

		$quiz_required_pass_grade     = intval( get_post_meta( $quiz_id, '_quiz_passmark', true ) );
		$succeeded                    = $user_quiz_grade >= $quiz_required_pass_grade;
		$failed_and_reset_not_allowed = ! $succeeded && ! $reset_quiz_allowed;

		// Check if answers must be shown
		$show_answers = $quiz_graded && ( $succeeded || $failed_and_reset_not_allowed );

		/**
		 * Allow dynamic overriding of whether to show question answers or not
		 *
		 * @since 1.9.7
		 * @hook sensei_question_show_answers
		 *
		 * @param {bool} $show_answers Whether to show the answer to the question.
		 * @param {int}  $question_id  Question ID.
		 * @param {int}  $quiz_id      Quiz ID.
		 * @param {int}  $lesson_id    Lesson ID.
		 * @param {int}  $user_id      User ID.
		 * @return {bool} Whether to show the answer to the question.
		 */
		$show_answers = apply_filters( 'sensei_question_show_answers', $show_answers, $question_id, $quiz_id, $lesson_id, get_current_user_id() );

		// Show answers if allowed
		if ( $show_answers ) {
			$answer_notes = Sensei()->quiz->get_user_question_feedback( $lesson_id, $question_id, get_current_user_id() );

			if ( $answer_notes ) {
				?>

				<div class="sensei-message info info-special answer-feedback">

					<?php
						/**
						 * Filter the answer feedback.
						 *
						 * @since 1.9.0
						 * @hook sensei_question_answer_notes
						 *
						 * @param {bool|string} $answer_notes Answer notes.
						 * @param {int}         $question_id  Question ID.
						 * @param {int}         $lesson_id    Lesson ID.
						 * @return {string} Answer notes.
						 */
						echo wp_kses_post( apply_filters( 'sensei_question_answer_notes', $answer_notes, $question_id, $lesson_id ) );

					?>

				</div>

				<?php
			}
		}

	}

	/**
	 * This function has to be run inside the quiz question loop on the single quiz page.
	 *
	 * It show the correct/incorrect answer per question depending on the quiz logic explained here:
	 * https://senseilms.com/documentation/quiz-settings-flowchart/
	 *
	 * Pseudo code for logic:  https://github.com/Automattic/sensei/issues/1422#issuecomment-214494263
	 *
	 * @since 1.9.0
	 */
	public static function the_answer_result_indication() {
		global $sensei_question_loop;

		$quiz_id            = $sensei_question_loop['quiz_id'];
		$question_item      = $sensei_question_loop['current_question'];
		$lesson_id          = Sensei()->quiz->get_lesson_id( $quiz_id );
		$user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, get_current_user_id() );
		$quiz_graded        = isset( $user_lesson_status->comment_approved ) && ! in_array( $user_lesson_status->comment_approved, array( 'in-progress', 'ungraded' ) );

		if ( ! Sensei_Utils::has_started_course( Sensei()->lesson->get_course_id( $lesson_id ), get_current_user_id() ) ) {
			return;
		}

		if ( ! $quiz_graded ) {
			return;
		}

		$user_quiz_grade          = Sensei_Quiz::get_user_quiz_grade( $lesson_id, get_current_user_id() );
		$quiz_required_pass_grade = intval( get_post_meta( $quiz_id, '_quiz_passmark', true ) );
		$user_passed              = $user_quiz_grade >= $quiz_required_pass_grade;

		$show_answers = false;
		if ( ! Sensei_Quiz::is_pass_required( $lesson_id ) || $user_passed || ! Sensei_Quiz::is_reset_allowed( $lesson_id ) ) {
			$show_answers = true;
		}

		/** This filter is documented in self::answer_feedback_notes */
		$show_answers = apply_filters( 'sensei_question_show_answers', $show_answers, $question_item->ID, $quiz_id, $lesson_id, get_current_user_id() );

		if ( $show_answers ) {
			self::output_result_indication( $lesson_id, $question_item->ID );
			return;
		}
	}

	/**
	 * @since 1.9.5
	 *
	 * @param integer $lesson_id
	 * @param integer $question_id
	 */
	public static function output_result_indication( $lesson_id, $question_id ) {

		$question_grade      = Sensei()->question->get_question_grade( $question_id );
		$user_question_grade = Sensei()->quiz->get_user_question_grade( $lesson_id, $question_id, get_current_user_id() );

		// Defaults
		$answer_message = __( 'Incorrect - Right Answer:', 'sensei-lms' ) . ' ' . self::get_correct_answer( $question_id );

		// For zero grade mark as 'correct' but add no classes
		if ( 0 == $question_grade ) {
			$user_correct         = true;
			$answer_message_class = '';
			$answer_message       = '';
		} elseif ( $user_question_grade > 0 ) {
			$user_correct         = true;
			$answer_message_class = 'user_right';
			// translators: Placeholder is the question grade.
			$answer_message = sprintf( __( 'Grade: %d', 'sensei-lms' ), $user_question_grade );
		} else {
			$user_correct         = false;
			$answer_message_class = 'user_wrong';
		}

		// setup answer feedback class
		$answer_notes = Sensei()->quiz->get_user_question_feedback( $lesson_id, $question_id, get_current_user_id() );
		if ( $answer_notes ) {
			$answer_message_class .= ' has_notes';
		}

		/**
		 * Filter the answer message CSS classes.
		 *
		 * @hook sensei_question_answer_message_css_class
		 *
		 * @param {string} $answer_message_class Space-separated CSS classes to apply to answer message.
		 * @param {int}    $lesson_id            Lesson ID.
		 * @param {int}    $question_id          Question ID.
		 * @param {int}    $user_id              User ID.
		 * @param {bool}   $user_correct         Whether this is the correct answer.
		 * @return {string} Space-separated CSS classes to apply to answer message.
		 */
		$final_css_classes = apply_filters( 'sensei_question_answer_message_css_class', $answer_message_class, $lesson_id, $question_id, get_current_user_id(), $user_correct );

		/**
		 * Filter the answer message.
		 *
		 * @hook sensei_question_answer_message_text
		 *
		 * @param {string} $answer_message Answer message.
		 * @param {int}    $lesson_id      Lesson ID.
		 * @param {int}    $question_id    Question ID.
		 * @param {int}    $user_id        User ID.
		 * @param {bool}   $user_correct   Whether this is the correct answer.
		 * @return {string} Answer message.
		 */
		$final_message = apply_filters( 'sensei_question_answer_message_text', $answer_message, $lesson_id, $question_id, get_current_user_id(), $user_correct );
		?>
		<div class="answer_message <?php echo esc_attr( $final_css_classes ); ?>">

			<span><?php echo wp_kses_post( $final_message ); ?></span>

		</div>
		<?php
	}

	/**
	 * Generate the question template data and return it as an array.
	 *
	 * @since 1.9.0
	 *
	 * @param string  $question_id
	 * @param $quiz_id
	 * @return array $question_data
	 */
	public static function get_template_data( $question_id, $quiz_id ) {

		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		$reset_allowed = get_post_meta( $quiz_id, '_enable_quiz_reset', true );
		// backwards compatibility
		if ( 'on' == $reset_allowed ) {
			$reset_allowed = 1;
		}

		// Check again that the lesson is complete
		$user_lesson_end      = Sensei_Utils::user_completed_lesson( Sensei()->quiz->get_lesson_id( $quiz_id ), get_current_user_id() );
		$user_lesson_complete = false;
		if ( $user_lesson_end ) {
			$user_lesson_complete = true;
		}

		// setup the question data
		$data                           = [];
		$data['ID']                     = $question_id;
		$data['title']                  = get_the_title( $question_id );
		$data['content']                = get_post( $question_id )->post_content;
		$data['quiz_id']                = $quiz_id;
		$data['lesson_id']              = Sensei()->quiz->get_lesson_id( $quiz_id );
		$data['type']                   = Sensei()->question->get_question_type( $question_id );
		$data['question_grade']         = Sensei()->question->get_question_grade( $question_id );
		$data['user_question_grade']    = Sensei()->quiz->get_user_question_grade( $lesson_id, $question_id, get_current_user_id() );
		$data['question_right_answer']  = get_post_meta( $question_id, '_question_right_answer', true );
		$data['question_wrong_answers'] = get_post_meta( $question_id, '_question_wrong_answers', true );
		$data['user_answer_entry']      = Sensei()->quiz->get_user_question_answer( $lesson_id, $question_id, get_current_user_id() );
		$data['lesson_completed']       = Sensei_Utils::user_completed_course( $lesson_id, get_current_user_id() );
		$data['quiz_grade_type']        = get_post_meta( $quiz_id, '_quiz_grade_type', true );
		$data['reset_quiz_allowed']     = $reset_allowed;
		$data['lesson_complete']        = $user_lesson_complete;

		/**
		 * Filter the question template data. This filter fires in
		 * the get_template_data function.
		 *
		 * @since 1.9.0
		 * @hook sensei_get_question_template_data
		 *
		 * @param {array} $data        Question data.
		 * @param {int}   $question_id Question ID.
		 * @param {int}   $quiz_id     Quiz ID.
		 * @return {array} Question data.
		 */
		return apply_filters( 'sensei_get_question_template_data', $data, $question_id, $quiz_id );

	}

	/**
	 * Load multiple choice question data on the sensei_get_question_template_data filter.
	 *
	 * @since 1.9.0
	 *
	 * @param array  $question_data
	 * @param string $question_id
	 * @param string $quiz_id
	 *
	 * @return array()
	 */
	public static function file_upload_load_question_data( $question_data, $question_id, $quiz_id ) {

		if ( 'file-upload' === Sensei()->question->get_question_type( $question_id ) ) {

			// Get uploaded file.
			$attachment_id         = $question_data['user_answer_entry'];
			$answer_media_url      = '';
			$answer_media_filename = '';

			$question_helptext = '';
			if ( isset( $question_data['question_wrong_answers'][0] ) ) {

				$question_helptext = $question_data['question_wrong_answers'][0];

			}

			if ( 0 < intval( $attachment_id ) ) {

				$answer_media_url      = wp_get_attachment_url( $attachment_id );
				$filename_raw          = basename( $answer_media_url );
				$answer_media_filename = Sensei_Grading_User_Quiz::remove_hash_prefix( $filename_raw );

			}

			$upload_size = wp_max_upload_size();
			if ( ! $upload_size ) {
				$upload_size = 0;
			}

			// translators: Placeholder are the upload size and the measurement (e.g. 5 MB).
			$max_upload_size = sprintf( __( 'Maximum upload file size: %s', 'sensei-lms' ), esc_html( size_format( $upload_size ) ) );

			// Assemble all the data needed by the file upload template.
			$question_data['answer_media_url']      = $answer_media_url;
			$question_data['answer_media_filename'] = $answer_media_filename;
			$question_data['max_upload_size']       = $max_upload_size;

			$question_data['question_helptext'] = $question_helptext;

		}

		return $question_data;

	}

	/**
	 * Load multiple choice question data on the sensei_get_question_template_data
	 * filter.
	 *
	 * @since 1.9.0
	 *
	 * @param $question_data
	 * @param $question_id
	 * @param $quiz_id
	 *
	 * @return array()
	 */
	public static function multiple_choice_load_question_data( $question_data, $question_id, $quiz_id ) {

		if ( 'multiple-choice' == Sensei()->question->get_question_type( $question_id ) ) {

			$answer_type = 'radio';
			if ( is_array( $question_data['question_right_answer'] ) && ( 1 < count( $question_data['question_right_answer'] ) ) ) {

				$answer_type = 'checkbox';

			}

			// Merge right and wrong answers
			if ( is_array( $question_data['question_right_answer'] ) ) {

				$merged_options = array_merge( $question_data['question_wrong_answers'], $question_data['question_right_answer'] );

			} else {

				array_push( $question_data['question_wrong_answers'], $question_data['question_right_answer'] );
				$merged_options = $question_data['question_wrong_answers'];

			}

			// Setup answer options array.
			$question_answers_options = array();
			$count                    = 0;

			foreach ( $merged_options as $answer ) {

				$count++;
				$question_option = array();

				if ( ( $question_data['lesson_completed'] && $question_data['user_quiz_grade'] != '' )
					|| ( $question_data['lesson_completed'] && ! $question_data['reset_quiz_allowed'] && $question_data['user_quiz_grade'] != '' )
					|| ( 'auto' == $question_data['quiz_grade_type'] && ! $question_data['reset_quiz_allowed'] && ! empty( $question_data['user_quiz_grade'] ) ) ) {

					$user_correct = false;

					// For zero grade mark as 'correct' but add no classes
					if ( 0 == $question_data['question_grade'] ) {

						$user_correct = true;

					} elseif ( $question_data['user_question_grade'] > 0 ) {

						$user_correct = true;

					}
				}

				// setup the option specific classes
				$answer_class = '';
				if ( isset( $user_correct ) && 0 < $question_data['question_grade'] ) {
					if ( is_array( $question_data['question_right_answer'] ) && in_array( $answer, $question_data['question_right_answer'] ) ) {

						$answer_class .= ' right_answer';

					} elseif ( ! is_array( $question_data['question_right_answer'] ) && $question_data['question_right_answer'] == $answer ) {

						$answer_class .= ' right_answer';

					} elseif ( ( is_array( $question_data['user_answer_entry'] ) && in_array( $answer, $question_data['user_answer_entry'] ) )
						|| ( ! $question_data['user_answer_entry'] && $question_data['user_answer_entry'] == $answer ) ) {

						$answer_class = 'user_wrong';
						if ( $user_correct ) {

							$answer_class = 'user_right';

						}
					}
				}

				// determine if the current option must be checked
				$checked = '';
				if ( isset( $question_data['user_answer_entry'] ) ) {
					if ( is_array( $question_data['user_answer_entry'] ) && in_array( $answer, $question_data['user_answer_entry'] ) ) {

						$checked = 'checked="checked"';

					} elseif ( ! is_array( $question_data['user_answer_entry'] ) ) {

						$checked = checked( $answer, $question_data['user_answer_entry'], false );

					}
				}

				// Load the answer option data
				$question_option['ID']           = Sensei()->lesson->get_answer_id( $answer );
				$question_option['answer']       = $answer;
				$question_option['option_class'] = $answer_class;
				$question_option['checked']      = $checked;
				$question_option['count']        = $count;
				$question_option['type']         = $answer_type;

				// add the speci  fic option to the list of options for this question
				$question_answers_options[ $question_option['ID'] ] = $question_option;

			}

			// Shuffle the array depending on the settings
			$answer_options_sorted = array();
			$random_order          = get_post_meta( $question_data['ID'], '_random_order', true );
			if ( $random_order && $random_order == 'yes' ) {

				$answer_options_sorted = $question_answers_options;
				shuffle( $answer_options_sorted );

			} else {

				$answer_order        = array();
				$answer_order_string = get_post_meta( $question_data['ID'], '_answer_order', true );
				if ( $answer_order_string ) {

					$answer_order = array_filter( explode( ',', $answer_order_string ) );
					if ( count( $answer_order ) > 0 ) {

						foreach ( $answer_order as $answer_id ) {

							if ( isset( $question_answers_options[ $answer_id ] ) ) {

								$answer_options_sorted[ $answer_id ] = $question_answers_options[ $answer_id ];
								unset( $question_answers_options[ $answer_id ] );

							}
						}

						if ( count( $question_answers_options ) > 0 ) {
							foreach ( $question_answers_options as $id => $answer ) {

								$answer_options_sorted[ $id ] = $answer;

							}
						}
					} else {

						$answer_options_sorted = $question_answers_options;

					}
				} else {

					$answer_options_sorted = $question_answers_options;

				}
			}

			// assemble and setup the data for the templates data array
			$question_data['answer_options'] = $answer_options_sorted;

		}

		return $question_data;

	}

	/**
	 * Load the gap fill question data on the sensei_get_question_template_data
	 * filter.
	 *
	 * @since 1.9.0
	 *
	 * @param $question_data
	 * @param $question_id
	 * @param $quiz_id
	 *
	 * @return array()
	 */
	public static function gap_fill_load_question_data( $question_data, $question_id, $quiz_id ) {

		if ( 'gap-fill' == Sensei()->question->get_question_type( $question_id ) ) {

			$gapfill_array                 = explode( '||', $question_data['question_right_answer'] );
			$question_data['gapfill_pre']  = isset( $gapfill_array[0] ) ? $gapfill_array[0] : '';
			$question_data['gapfill_gap']  = isset( $gapfill_array[1] ) ? $gapfill_array[1] : '';
			$question_data['gapfill_post'] = isset( $gapfill_array[2] ) ? $gapfill_array[2] : '';

		}

		return $question_data;

	}


	/**
	 * Get the correct answer for a question
	 *
	 * @param $question_id
	 * @return string $correct_answer or empty
	 */
	public static function get_correct_answer( $question_id ) {
		$right_answer = get_post_meta( $question_id, '_question_right_answer', true );
		$type         = Sensei()->question->get_question_type( $question_id );

		if ( 'boolean' == $type ) {
			if ( 'true' === $right_answer ) {
				$right_answer = __( 'True', 'sensei-lms' );
			} else {
				$right_answer = __( 'False', 'sensei-lms' );
			}
		} elseif ( 'multiple-choice' == $type ) {

			$right_answer = (array) $right_answer;
			$right_answer = implode( ', ', $right_answer );

		} elseif ( 'gap-fill' == $type ) {

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

			$right_answer = $gapfill_pre . ' <span class="highlight">' . $gapfill_gap . '</span> ' . $gapfill_post;

		} else {

			// for non auto gradable question types no answer should be returned.
			$right_answer = '';

		}

		/**
		 * Filter the correct answer response.
		 *
		 * Can be used for text filters.
		 *
		 * @since 1.9.7
		 * @hook sensei_questions_get_correct_answer
		 *
		 * @param {string} $right_answer Correct answer.
		 * @param {int}    $question_id  Question ID.
		 * @return {string} Correct answer.
		 */
		return apply_filters( 'sensei_questions_get_correct_answer', $right_answer, $question_id );
	}

	/**
	 * Get answers by ID keys.
	 *
	 * @param string[] $answers Answers string.
	 *
	 * @return string[] Answers with the correct ID keys.
	 */
	public function get_answers_by_id( $answers = [] ) {
		$answers_by_id = [];

		foreach ( $answers as $answer ) {
			$answers_by_id[ Sensei()->lesson->get_answer_id( $answer ) ] = $answer;
		}

		return $answers_by_id;
	}

	/**
	 * Get answers sorted.
	 *
	 * @param string[]        $answers      Answers string by ID.
	 * @param string[]|string $answer_order Sorted answers IDs.
	 *
	 * @return string[] The sorted answers.
	 */
	public function get_answers_sorted( $answers, $answer_order ) {
		$answers_sorted = [];

		if ( is_string( $answer_order ) ) {
			$answer_order = explode( ',', $answer_order );
		}

		foreach ( $answer_order as $answer_id ) {
			if ( isset( $answers[ $answer_id ] ) ) {
				$answers_sorted[ $answer_id ] = $answers[ $answer_id ];
				unset( $answers[ $answer_id ] );
			}
		}

		if ( count( $answers ) > 0 ) {
			foreach ( $answers as $id => $answer ) {
				$answers_sorted[ $id ] = $answer;
			}
		}

		return $answers_sorted;
	}

	/**
	 * Log an event when a question is initially published.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @param WP_Post $question The question object.
	 */
	public function log_initial_publish_event( $question ) {
		$event_properties = [
			'page'          => 'unknown',
			'question_type' => $this->get_question_type( $question->ID ),
		];

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( $screen && 'question' === $screen->id ) {
				$event_properties['page'] = 'question';
			} elseif ( isset( $_REQUEST['action'] ) && 'lesson_update_question' === $_REQUEST['action'] ) {
				$event_properties['page'] = 'lesson';
			}
		}

		sensei_log_event( 'question_add', $event_properties );
	}

	/**
	 * Check if a question can change to a new author. For normal questions, this is only possible if it
	 * doesn't belong to any other quiz that has a different author.
	 *
	 * @param int $question_id   The question post ID.
	 * @param int $new_author_id The new author ID.
	 *
	 * @return bool
	 */
	private function can_question_change_author( int $question_id, int $new_author_id ) {
		$question = get_post( $question_id );

		if ( ! $question || ! in_array( $question->post_type, [ 'question', 'multiple_question' ], true ) ) {
			return false;
		}

		if ( 'multiple_question' === $question->post_type ) {
			// These stick to the quiz. However, we don't attempt to change the questions in the category.
			return true;
		}

		$can_question_change_author = true;
		$quiz_ids                   = array_filter( get_post_meta( $question->ID, '_quiz_id' ) );
		foreach ( $quiz_ids as $quiz_id ) {
			$quiz = get_post( $quiz_id );
			if (
				$quiz
				&& 'quiz' === $quiz->post_type
				&& $new_author_id !== (int) $quiz->post_author
			) {
				$can_question_change_author = false;
				break;
			}
		}

		return $can_question_change_author;
	}

	/**
	 * Update the question author if possible.
	 *
	 * @param int $question_id   Question post ID.
	 * @param int $new_author_id New author.
	 *
	 * @return bool Whether the question author could be changed.
	 */
	public function maybe_update_question_author( int $question_id, int $new_author_id ) {
		if ( ! $question_id || ! $this->can_question_change_author( $question_id, $new_author_id ) ) {
			return false;
		}

		wp_update_post(
			[
				'ID'          => $question_id,
				'post_author' => $new_author_id,
			]
		);

		return true;
	}

}

/**
 * Class WooThemes_Sensei_Question
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Question extends Sensei_Question{}
