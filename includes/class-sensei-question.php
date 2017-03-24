<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
	 * @since  1.0.0
	 */
	public function __construct () {
        $this->token = 'question';
		$this->question_types = $this->question_types();
		$this->meta_fields = array( 'question_right_answer', 'question_wrong_answers' );
		if ( is_admin() ) {
			// Custom Write Panel Columns
			add_filter( 'manage_edit-question_columns', array( $this, 'add_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );
			add_action( 'add_meta_boxes', array( $this, 'question_edit_panel_metabox' ), 10, 2 );

			// Quesitno list table filters
			add_action( 'restrict_manage_posts', array( $this, 'filter_options' ) );
			add_filter( 'request', array( $this, 'filter_actions' ) );

			add_action( 'save_post', array( $this, 'save_question' ), 10, 1 );
		} // End If Statement
	} // End __construct()

	public function question_types() {
		$types = array(
			'multiple-choice' => __( 'Multiple Choice', 'woothemes-sensei' ),
			'boolean' => __( 'True/False', 'woothemes-sensei' ),
			'gap-fill' => __( 'Gap Fill', 'woothemes-sensei' ),
			'single-line' => __( 'Single Line', 'woothemes-sensei' ),
			'multi-line' => __( 'Multi Line', 'woothemes-sensei' ),
			'file-upload' => __( 'File Upload', 'woothemes-sensei' ),
		);

		return apply_filters( 'sensei_question_types', $types );
	}

	/**
	 * Add column headings to the "lesson" post list screen.
	 * @access public
	 * @since  1.3.0
	 * @param  array $defaults
	 * @return array $new_columns
	 */
	public function add_column_headings ( $defaults ) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = _x( 'Question', 'column name', 'woothemes-sensei' );
		$new_columns['question-type'] = _x( 'Type', 'column name', 'woothemes-sensei' );
		$new_columns['question-category'] = _x( 'Categories', 'column name', 'woothemes-sensei' );
		if ( isset( $defaults['date'] ) ) {
			$new_columns['date'] = $defaults['date'];
		}

		return $new_columns;
	} // End add_column_headings()

	/**
	 * Add data for our newly-added custom columns.
	 * @access public
	 * @since  1.3.0
	 * @param  string $column_name
	 * @param  int $id
	 * @return void
	 */
	public function add_column_data ( $column_name, $id ) {
		global $wpdb, $post;

		switch ( $column_name ) {

			case 'id':
				echo $id;
			break;

			case 'question-type':
				$question_type = strip_tags( get_the_term_list( $id, 'question-type', '', ', ', '' ) );
				$output = '&mdash;';
				if( isset( $this->question_types[ $question_type ] ) ) {
					$output = $this->question_types[ $question_type ];
				}
				echo $output;
			break;

			case 'question-category':
				$output = strip_tags( get_the_term_list( $id, 'question-category', '', ', ', '' ) );
				if( ! $output ) {
					$output = '&mdash;';
				}
				echo $output;
			break;

			default:
			break;

		}

	} // End add_column_data()

	public function question_edit_panel_metabox( $post_type, $post ) {
		if( in_array( $post_type, array( 'question', 'multiple_question' ) ) ) {

			$metabox_title = __( 'Question', 'woothemes-sensei' );

			if( isset( $post->ID ) ) {

                $question_type = Sensei()->question->get_question_type( $post->ID );

				if( $question_type ) {
					$type = $this->question_types[ $question_type ];
					if( $type ) {
						$metabox_title = $type;
					}
				}
			}
			add_meta_box( 'question-edit-panel', $metabox_title, array( $this, 'question_edit_panel' ), 'question', 'normal', 'high' );
			add_meta_box( 'question-lessons-panel', __( 'Quizzes', 'woothemes-sensei' ), array( $this, 'question_lessons_panel' ), 'question', 'side', 'default' );
			add_meta_box( 'multiple-question-lessons-panel', __( 'Quizzes', 'woothemes-sensei' ), array( $this, 'question_lessons_panel' ), 'multiple_question', 'side', 'default' );
		}
	}

	public function question_edit_panel() {
		global  $post, $pagenow;

		add_action( 'admin_enqueue_scripts', array( Sensei()->lesson, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( Sensei()->lesson, 'enqueue_styles' ) );

		$html = '<div id="lesson-quiz" class="single-question"><div id="add-question-main">';

		if( 'post-new.php' == $pagenow ) {

			$html .= '<div id="add-question-actions">';
				$html .= Sensei()->lesson->quiz_panel_add( 'question' );
			$html .= '</div>';

		} else {
			$question_id = $post->ID;

			$question_type =  Sensei()->question->get_question_type( $post->ID );

			$html .= '<div id="add-question-metadata"><table class="widefat">';
				$html .= Sensei()->lesson->quiz_panel_question( $question_type, 0, $question_id, 'question' );
			$html .= '</table></div>';
		}

		$html .= '</div></div>';

		echo $html;
	}

	public function question_lessons_panel() {
		global $post;

		$no_lessons = sprintf( __( '%1$sThis question does not appear in any quizzes yet.%2$s', 'woothemes-sensei' ), '<em>', '</em>' );

		if( ! isset( $post->ID ) ) {
			echo $no_lessons;
			return;
		}

		// This retrieves those quizzes the question is directly connected to.
		$quizzes = get_post_meta( $post->ID, '_quiz_id', false );

		// Collate all 'multiple_question' quizzes the question is part of.
		$categories_of_question = wp_get_post_terms( $post->ID, 'question-category', array( 'fields' => 'ids' ) );
		if ( ! empty( $categories_of_question ) ) {
			foreach ( $categories_of_question as $term_id ) {
				$qargs = array(
					'fields'           => 'ids',
					'post_type'        => 'multiple_question',
					'posts_per_page'   => -1,
					'meta_query'       => array(
						array(
							'key'      => 'category',
							'value'    => $term_id,
						),
					),
					'post_status'      => 'any',
					'suppress_filters' => 0,
				);
				$cat_question_ids = get_posts( $qargs );
				foreach( $cat_question_ids as $cat_question_id ) {
					$cat_quizzes = get_post_meta( $cat_question_id, '_quiz_id', false );
					$quizzes = array_merge( $quizzes, $cat_quizzes );
				}
			}
			$quizzes = array_unique( array_filter( $quizzes ) );
		}

		if( 0 == count( $quizzes ) ) {
			echo $no_lessons;
			return;
		}

		$lessons = false;

		foreach( $quizzes as $quiz ) {

			$lesson_id = get_post_meta( $quiz, '_quiz_lesson', true );

			if( ! $lesson_id ) continue;

			$lessons[ $lesson_id ]['title'] = get_the_title( $lesson_id );
			$lessons[ $lesson_id ]['link'] = admin_url( 'post.php?post=' . $lesson_id . '&action=edit' );
		}

		if( ! $lessons ) {
			echo $no_lessons;
			return;
		}

		$html = '<ul>';

		foreach( $lessons as $id => $lesson ) {
			$html .= '<li><a href="' . esc_url( $lesson['link'] ) . '">' . esc_html( $lesson['title'] ) . '</a></li>';
		}

		$html .= '</ul>';

		echo $html;

	}

	public function save_question( $post_id = 0 ) {

		if( ! isset( $_POST['post_type']
            ) || 'question' != $_POST['post_type'] ) {
            return;
        }



        //setup the data for saving
		$data = $_POST ;
        $data['quiz_id'] = 0;
		$data['question_id'] = $post_id;

		if ( ! wp_is_post_revision( $post_id ) ){

			// Unhook function to prevent infinite loops
			remove_action( 'save_post', array( $this, 'save_question' ) );

			// Update question data
			$question_id = Sensei()->lesson->lesson_save_question( $data, 'question' );

			// Re-hook same function
			add_action( 'save_post', array( $this, 'save_question' ) );
		}

		return;
	}

	/**
	 * Add options to filter the questions list table
	 * @return void
	 */
	public function filter_options() {
		global $typenow;

		if( is_admin() && 'question' == $typenow ) {

			$output = '';

			// Question type
			$selected = isset( $_GET['question_type'] ) ? $_GET['question_type'] : '';
			$type_options = '<option value="">' . __( 'All types', 'woothemes-sensei' ) . '</option>';
			foreach( $this->question_types as $label => $type ) {
				$type_options .= '<option value="' . esc_attr( $label ) . '" ' . selected( $selected, $label, false ) . '>' . esc_html( $type ) . '</option>';
			}

			$output .= '<select name="question_type" id="dropdown_question_type">';
			$output .= $type_options;
			$output .= '</select>';

			// Question category
			$cats = get_terms( 'question-category', array( 'hide_empty' => false ) );
			if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
				$selected = isset( $_GET['question_cat'] ) ? $_GET['question_cat'] : '';
				$cat_options = '<option value="">' . __( 'All categories', 'woothemes-sensei' ) . '</option>';
				foreach( $cats as $cat ) {
					$cat_options .= '<option value="' . esc_attr( $cat->slug ) . '" ' . selected( $selected, $cat->slug, false ) . '>' . esc_html( $cat->name ) . '</option>';
				}

				$output .= '<select name="question_cat" id="dropdown_question_cat">';
				$output .= $cat_options;
				$output .= '</select>';
			}

			echo $output;
		}
	}

	/**
	 * Filter questions list table
	 * @param  array $request Current request
	 * @return array          Modified request
	 */
	public function filter_actions( $request ) {
		global $typenow;

		if( is_admin() && 'question' == $typenow ) {

			// Question type
			$question_type = isset( $_GET['question_type'] ) ? $_GET['question_type'] : '';
			if( $question_type ) {
				$type_query = array(
					'taxonomy' => 'question-type',
					'terms' => $question_type,
					'field' => 'slug',
				);
				$request['tax_query'][] = $type_query;
			}

			// Question category
			$question_cat = isset( $_GET['question_cat'] ) ? $_GET['question_cat'] : '';
			if( $question_cat ) {
				$cat_query = array(
					'taxonomy' => 'question-category',
					'terms' => $question_cat,
					'field' => 'slug',
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
    public function get_question_type( $question_id ){

        if( empty( $question_id ) || ! intval( $question_id ) > 0
            || 'question' != get_post_type( $question_id )   ){
            return false;
        }

        $question_type = 'multiple-choice';
        $question_types = wp_get_post_terms( $question_id, 'question-type' );
        foreach( $question_types as $type ) {
            $question_type = $type->slug;
        }

        return $question_type;

    }// end get_question_type

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
		 * @since 1.9.6 introduced
		 */
		return apply_filters( 'sensei_get_question_grade', $question_grade, $question_id );

	} // end get_question_grade


    /**
     * This function simply loads the question type template
     *
     * @since 1.9.0
     * @param $question_type
     */
    public static function load_question_template( $question_type ){

        Sensei_Templates::get_template  ( 'single-quiz/question_type-' . $question_type . '.php' );
    }

    /**
     * Echo the sensei question title.
     *
     * @uses WooThemes_Sensei_Question::get_the_question_title
     *
     * @since 1.9.0
     * @param $question_id
     */
    public static function the_question_title( $question_id ){

        echo self::get_the_question_title( $question_id );

    }// end the_question_title

    /**
     * Generate the question title with it's grade.
     *
     * @since 1.9.0
     *
     * @param $question_id
     * @return string
     */
    public static function get_the_question_title( $question_id ){

        /**
         * Filter the sensei question title
         *
         * @since 1.3.0
         * @param $question_title
         */
        $title = apply_filters( 'sensei_question_title', get_the_title( $question_id ) );

        /**
         * hook document in class-woothemes-sensei-message.php the_title()
         */
        $title = apply_filters( 'sensei_single_title', $title, 'question');
		
		$question_grade = Sensei()->question->get_question_grade( $question_id );

        $title_html  = '<span class="question question-title">';
        $title_html .= $title;
		$title_html .= Sensei()->view_helper->format_question_points( $question_grade );
		$title_html .='</span>';

        return $title_html;
    }

    /**
     * Tech the question description
     *
     * @param $question_id
     * @return string
     */
    public static function get_the_question_description( $question_id ){

        $question = get_post( $question_id );

        /**
         * Already documented within WordPress Core
         */
        return apply_filters( 'the_content', $question->post_content );

    }

    /**
     * Output the question description
     *
     * @since 1.9.0
     * @param $question_id
     */
    public static function the_question_description( $question_id  ){

        echo self::get_the_question_description( $question_id );

    }

    /**
     * Get the questions media markup
     *
     * @since 1.9.0
     * @param $question_id
     * @return string
     */
    public static function get_the_question_media( $question_id ){

        $question_media = get_post_meta( $question_id, '_question_media', true );
        $question_media_link = '';
        if( 0 < intval( $question_media ) ) {
            $mimetype = get_post_mime_type( $question_media );
            if( $mimetype ) {
                $mimetype_array = explode( '/', $mimetype);
                if( isset( $mimetype_array[0] ) && $mimetype_array[0] ) {
                    $question_media_type = $mimetype_array[0];
                    $question_media_url = wp_get_attachment_url( $question_media );
                    $attachment = get_post( $question_media );
                    $question_media_title = $attachment->post_title;
                    $question_media_description = $attachment->post_content;
                    switch( $question_media_type ) {
                        case 'image':
                            $image_size = apply_filters( 'sensei_question_image_size', 'medium', $question_id );
                            $attachment_src = wp_get_attachment_image_src( $question_media, $image_size );
                            $question_media_link = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank"><img src="' . $attachment_src[0] . '" width="' . $attachment_src[1] . '" height="' . $attachment_src[2] . '" /></a>';
                            break;

                        case 'audio':
                            $question_media_link = wp_audio_shortcode( array( 'src' => $question_media_url ) );
                            break;

                        case 'video':
                            $question_media_link = wp_video_shortcode( array( 'src' => $question_media_url ) );
                            break;

                        default:
                            $question_media_filename = basename( $question_media_url );
                            $question_media_link = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank">' . $question_media_filename . '</a>';
                            break;
                    }
                }
            }
        }

        $output = '';
        if( $question_media_link ) {

                $output .= '<div class="question_media_display">';
                $output .=      $question_media_link;
                $output .= '<dl>';

                if( $question_media_title ) {

                   $output .= '<dt>'. $question_media_title. '</dt>';

                 }

                if( $question_media_description ) {

                    $output .= '<dd>' . $question_media_description . '</dd>';

                }

                $output .= '</dl>';
                $output .= '</div>';


         }

        return $output;

    } // end get_the_question_media


    /**
     * Output the question media
     *
     * @since 1.9.0
     * @param string $question_id
     */
    public static function the_question_media( $question_id ){

        echo self::get_the_question_media( $question_id );

    }

    /**
     * Output a special field for the question needed for question submission.
     *
     * @since 1.9.0
     *
     * @param $question_id
     */
    public static function the_question_hidden_fields( $question_id ){
        ?>

            <input type="hidden" name="question_id_<?php $question_id;?>" value="<?php $question_id;?>" />
            <input type="hidden" name="questions_asked[]" value="<?php echo esc_attr( $question_id ); ?>" />

        <?php
    }

    /**
     * This function can only be run withing the single quiz question loop
     *
     * @since 1.9.0
     * @param $question_id
     */
    public static function answer_feedback_notes( $question_id ){

        //IDS
        $quiz_id = get_the_ID();
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
        $quiz_grade_type    = get_post_meta( $quiz_id , '_quiz_grade_type', true );
		$quiz_graded        = isset( $user_lesson_status->comment_approved ) && ! in_array( $user_lesson_status->comment_approved, array( 'ungraded', 'in-progress' ) );

	    $quiz_required_pass_grade = intval( get_post_meta($quiz_id, '_quiz_passmark', true) );
		$succeeded = $user_quiz_grade >= $quiz_required_pass_grade;
		$failed_and_reset_not_allowed = ! $succeeded && ! $reset_quiz_allowed;

		// Check if answers must be shown
		$show_answers = $quiz_graded && ( $succeeded || $failed_and_reset_not_allowed );

	    /**
         * Allow dynamic overriding of whether to show question answers or not
         *
         * @since 1.9.7
         * 
         * @param boolean $show_answers
         * @param integer $question_id
         * @param integer $quiz_id
         * @param integer $lesson_id
         * @param integer $user_id
         */
	    $show_answers = apply_filters( 'sensei_question_show_answers', $show_answers, $question_id, $quiz_id, $lesson_id, get_current_user_id() );

		// Show answers if allowed
		if( $show_answers ) {
            $answer_notes = Sensei()->quiz->get_user_question_feedback( $lesson_id, $question_id, get_current_user_id() );

            if( $answer_notes ) { ?>

                <div class="sensei-message info info-special answer-feedback">

                    <?php

                        /**
                         * Filter the answer feedback
                         * Since 1.9.0
                         *
                         * @param string $answer_notes
                         * @param string $question_id
                         * @param string $lesson_id
                         */
                        echo apply_filters( 'sensei_question_answer_notes', $answer_notes, $question_id, $lesson_id );

                    ?>

                </div>

            <?php }

        }// end if we can show answer feedback

    }// end answer_feedback_notes

	/**
	 * This function has to be run inside the quiz question loop on the single quiz page.
	 *
	 * It show the correct/incorrect answer per question depending on the quiz logic explained here:
	 * https://docs.woothemes.com/document/sensei-quiz-settings-flowchart/
	 *
	 * Pseudo code for logic:  https://github.com/Automattic/sensei/issues/1422#issuecomment-214494263
	 *
	 * @since 1.9.0
	 */
	public static function the_answer_result_indication(){

		global $post,  $current_user, $sensei_question_loop;

		$answer_message       = '';
		$answer_message_class = '';
		$quiz_id              = $sensei_question_loop['quiz_id'];
		$question_item        = $sensei_question_loop['current_question'];
		$lesson_id            = Sensei()->quiz->get_lesson_id( $quiz_id );
		$user_lesson_status   = Sensei_Utils::user_lesson_status( $lesson_id, get_current_user_id() );
		$quiz_graded          = isset( $user_lesson_status->comment_approved ) && ! in_array( $user_lesson_status->comment_approved, array( 'in-progress', 'ungraded' ) );

		if ( ! Sensei_Utils::user_started_course( Sensei()->lesson->get_course_id( $lesson_id ), get_current_user_id() ) ) {
			return;
		}

		if ( ! $quiz_graded ) {
			return;
		}

		$user_quiz_grade          = Sensei_Quiz::get_user_quiz_grade( $lesson_id, get_current_user_id() );
		$quiz_required_pass_grade = intval( get_post_meta($quiz_id, '_quiz_passmark', true) );
		$user_passed              = $user_quiz_grade >= $quiz_required_pass_grade;

		$show_answers = false;
		if( ! Sensei_Quiz::is_pass_required( $lesson_id ) || $user_passed || ! Sensei_Quiz::is_reset_allowed( $lesson_id ) ) {
			$show_answers = true;
		}

		// This filter is documented in self::answer_feedback_notes()
		$show_answers = apply_filters( 'sensei_question_show_answers', $show_answers, $question_item->ID, $quiz_id, $lesson_id, get_current_user_id() );

		if( $show_answers ) {
			Sensei_Question::output_result_indication( $lesson_id, $question_item->ID);
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

		$question_grade       = Sensei()->question->get_question_grade( $question_id );
		$user_question_grade  = Sensei()->quiz->get_user_question_grade( $lesson_id, $question_id, get_current_user_id() );

		// Defaults
		$user_correct         = false;
		$answer_message_class = 'user_wrong';
		$answer_message       = __( 'Incorrect - Right Answer:','woothemes-sensei') . ' ' . self::get_correct_answer( $question_id );

		// For zero grade mark as 'correct' but add no classes
		if ( 0 == $question_grade   ) {
			$user_correct         = true;
			$answer_message_class = '';
			$answer_message       = '';
		} elseif( $user_question_grade > 0 ) {
			$user_correct         = true;
			$answer_message_class = 'user_right';
			$answer_message       = sprintf( __( 'Grade: %d', 'woothemes-sensei' ), $user_question_grade );
		}

		// setup answer feedback class
		$answer_notes = Sensei()->quiz->get_user_question_feedback( $lesson_id, $question_id, get_current_user_id() );
		if( $answer_notes ) {
			$answer_message_class .= ' has_notes';
		}

		?>
		<div class="answer_message <?php echo esc_attr( $answer_message_class ); ?>">

			<span><?php echo $answer_message; ?></span>

		</div>
		<?php
	}

	/**
     * Generate the question template data and return it as an array.
     *
     * @since 1.9.0
     *
     * @param string $question_id
     * @param $quiz_id
     * @return array $question_data
     */
    public static function get_template_data( $question_id, $quiz_id ){

        $lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id  );

        $reset_allowed = get_post_meta( $quiz_id, '_enable_quiz_reset', true );
        //backwards compatibility
        if( 'on' == $reset_allowed ) {
            $reset_allowed = 1;
        }

        // Check again that the lesson is complete
        $user_lesson_end = Sensei_Utils::user_completed_lesson( Sensei()->quiz->get_lesson_id( $quiz_id), get_current_user_id() );
        $user_lesson_complete = false;
        if ( $user_lesson_end ) {
            $user_lesson_complete = true;
        }

        //setup the question data
        $data[ 'ID' ]                     = $question_id;
        $data[ 'title' ]                  = get_the_title( $question_id );
        $data[ 'content' ]                = get_post( $question_id )->post_content;
        $data[ 'quiz_id' ]                = $quiz_id;
        $data[ 'lesson_id' ]              = Sensei()->quiz->get_lesson_id( $quiz_id );
        $data[ 'type' ]                   = Sensei()->question->get_question_type( $question_id );
        $data[ 'question_grade' ]         = Sensei()->question->get_question_grade(  $question_id  );
        $data[ 'user_question_grade' ]    = Sensei()->quiz->get_user_question_grade( $lesson_id,  $question_id , get_current_user_id());
        $data[ 'question_right_answer' ]  = get_post_meta( $question_id , '_question_right_answer', true );
        $data[ 'question_wrong_answers' ] = get_post_meta( $question_id , '_question_wrong_answers', true );
        $data[ 'user_answer_entry' ]      = Sensei()->quiz->get_user_question_answer( $lesson_id,  $question_id , get_current_user_id() );
        $data[ 'lesson_completed' ]       = Sensei_Utils::user_completed_course( $lesson_id, get_current_user_id( ) );
        $data[ 'quiz_grade_type' ]        = get_post_meta( $quiz_id , '_quiz_grade_type', true );
        $data[ 'reset_quiz_allowed' ]     = $reset_allowed;
        $data[ 'lesson_complete' ]        = $user_lesson_complete;

        /**
         * Filter the question template data. This filter fires  in
         * the get_template_data function
         *
         * @hooked self::boolean_load_question_data
         *
         * @since 1.9.0
         *
         * @param array $data
         * @param string $question_id
         * @param string $quiz_id
         */
        return apply_filters( 'sensei_get_question_template_data', $data, $question_id, $quiz_id );

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
    public static function file_upload_load_question_data ( $question_data, $question_id, $quiz_id ){


        if( 'file-upload' == Sensei()->question->get_question_type( $question_id ) ) {

            // Get uploaded file
            $attachment_id = $question_data[ 'user_answer_entry' ];
            $answer_media_url = $answer_media_filename = '';


            $question_helptext = '';
            if( isset( $question_data['question_wrong_answers'][0] ) ) {

                $question_helptext =  $question_data['question_wrong_answers'][0];

            }


            if( 0 < intval( $attachment_id ) ) {

                $answer_media_url = wp_get_attachment_url( $attachment_id );
                $answer_media_filename = basename( $answer_media_url );

            }


            // Get max upload file size, formatted for display
            // Code copied from wp-admin/includes/media.php:1515
            $upload_size_unit = $max_upload_size = wp_max_upload_size();
            $sizes = array( 'KB', 'MB', 'GB' );
            for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ ) {
                $upload_size_unit /= 1024;
            }
            if ( $u < 0 ) {

                $upload_size_unit = 0;
                $u = 0;

            } else {

                $upload_size_unit = (int) $upload_size_unit;

            }
            $max_upload_size = sprintf( __( 'Maximum upload file size: %d%s', 'woothemes-sensei' ), esc_html( $upload_size_unit ), esc_html( $sizes[ $u ] ) );

            // Assemble all the data needed by the file upload template
            $question_data[ 'answer_media_url' ]      = $answer_media_url;
            $question_data[ 'answer_media_filename' ] = $answer_media_filename;
            $question_data[ 'max_upload_size' ]       = $max_upload_size;

            $question_data[ 'question_helptext' ]     = $question_helptext;

        }// end if is file upload type

        return $question_data;

    }// end file_upload_load_question_data

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
    public static function multiple_choice_load_question_data( $question_data, $question_id, $quiz_id ){

        if( 'multiple-choice' == Sensei()->question->get_question_type( $question_id ) ) {


            $answer_type = 'radio';
            if ( is_array( $question_data[ 'question_right_answer' ] ) && ( 1 < count( $question_data[ 'question_right_answer' ] ) ) ) {

                $answer_type = 'checkbox';

            }

            // Merge right and wrong answers
            if ( is_array( $question_data[ 'question_right_answer' ] ) ) {

                $merged_options = array_merge( $question_data[ 'question_wrong_answers' ], $question_data[ 'question_right_answer' ] );

            }  else {

                array_push( $question_data[ 'question_wrong_answers' ], $question_data[ 'question_right_answer' ] );
                $merged_options = $question_data[ 'question_wrong_answers' ];

            }

            // Setup answer options array.
            $question_answers_options = array();
            $count = 0;

            foreach( $merged_options as $answer ) {

                $count++;
                $question_option = array();

                if( ( $question_data[ 'lesson_completed' ] && $question_data[ 'user_quiz_grade' ] != '' )
                    || ( $question_data[ 'lesson_completed' ] && ! $question_data[ 'reset_quiz_allowed' ] && $question_data[ 'user_quiz_grade' ] != '' )
                    || ( 'auto' == $question_data[ 'quiz_grade_type' ] && ! $question_data[ 'reset_quiz_allowed' ]  && ! empty( $question_data[ 'user_quiz_grade' ] ) ) ) {

                    $user_correct = false;


                    // For zero grade mark as 'correct' but add no classes
                    if ( 0 == $question_data[ 'question_grade' ] ) {

                        $user_correct = true;

                    }  else if( $question_data[ 'user_question_grade' ] > 0 ) {

                        $user_correct = true;

                    }

                }

                // setup the option specific classes
                $answer_class = '';
                if( isset( $user_correct ) && 0 < $question_data[ 'question_grade' ] ) {
                    if ( is_array( $question_data['question_right_answer'] ) && in_array($answer, $question_data['question_right_answer']) ) {

                        $answer_class .= ' right_answer';

                    }  elseif( !is_array($question_data['question_right_answer']) && $question_data['question_right_answer'] == $answer ) {

                        $answer_class .= ' right_answer';

                    } elseif( ( is_array( $question_data['user_answer_entry']  ) && in_array($answer, $question_data['user_answer_entry'] ) )
                        ||  ( !  $question_data['user_answer_entry'] &&  $question_data['user_answer_entry'] == $answer ) ) {

                        $answer_class = 'user_wrong';
                        if( $user_correct ) {

                            $answer_class = 'user_right';

                        }

                    }

                }

                // determine if the current option must be checked
                $checked = '';
                if ( isset( $question_data['user_answer_entry'] ) && 0 < count( $question_data['user_answer_entry'] ) ) {
                    if ( is_array( $question_data['user_answer_entry'] ) && in_array( $answer, $question_data['user_answer_entry'] ) ) {

                        $checked = 'checked="checked"';

                    } elseif ( !is_array( $question_data['user_answer_entry'] ) ) {

                        $checked = checked( $answer, $question_data['user_answer_entry'] , false );

                    }

                } // End If Statement

                //Load the answer option data
                $question_option[ 'ID' ]          = Sensei()->lesson->get_answer_id( $answer );
                $question_option[ 'answer' ]      = $answer;
                $question_option[ 'option_class'] = $answer_class;
                $question_option[ 'checked']      = $checked;
                $question_option[ 'count' ]       = $count;
                $question_option[ 'type' ] = $answer_type;

                // add the speci  fic option to the list of options for this question
                $question_answers_options[$question_option[ 'ID' ]] = $question_option;

            } // end for each option


            // Shuffle the array depending on the settings
            $answer_options_sorted = array();
            $random_order = get_post_meta( $question_data['ID'], '_random_order', true );
            if(  $random_order && $random_order == 'yes' ) {

                $answer_options_sorted = $question_answers_options;
                shuffle( $answer_options_sorted );

            } else {

                $answer_order = array();
                $answer_order_string = get_post_meta( $question_data['ID'], '_answer_order', true );
                if( $answer_order_string ) {

                    $answer_order = array_filter( explode( ',', $answer_order_string ) );
                    if( count( $answer_order ) > 0 ) {

                        foreach( $answer_order as $answer_id ) {

                            if( isset( $question_answers_options[ $answer_id ] ) ) {

                                $answer_options_sorted[ $answer_id ] = $question_answers_options[ $answer_id ];
                                unset( $question_answers_options[ $answer_id ] );

                            }

                        }

                        if( count( $question_answers_options ) > 0 ) {
                            foreach( $question_answers_options as $id => $answer ) {

                                $answer_options_sorted[ $id ] = $answer;

                            }
                        }

                    }else{

                        $answer_options_sorted = $question_answers_options;

                    }

                }else{

                    $answer_options_sorted = $question_answers_options;

                } // end if $answer_order_string

            } // end if random order


            // assemble and setup the data for the templates data array
            $question_data[ 'answer_options' ]    =  $answer_options_sorted;

        }

        return $question_data;

    }//  end multiple_choice_load_question_data

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
    public static function gap_fill_load_question_data( $question_data, $question_id, $quiz_id ){

        if( 'gap-fill' == Sensei()->question->get_question_type( $question_id ) ) {

            $gapfill_array = explode( '||', $question_data[ 'question_right_answer' ] );
            $question_data[ 'gapfill_pre' ]  = isset( $gapfill_array[0] ) ? $gapfill_array[0] : '';
            $question_data[ 'gapfill_gap' ]  = isset( $gapfill_array[1] ) ? $gapfill_array[1] : '';
            $question_data[ 'gapfill_post' ] = isset( $gapfill_array[2] ) ? $gapfill_array[2] : '';

        }

        return $question_data;

    }//  end gap_fill_load_question_data


    /**
     * Get the correct answer for a question
     *
     * @param $question_id
     * @return string $correct_answer or empty
     */
    public static function get_correct_answer( $question_id ){

        $right_answer = get_post_meta( $question_id, '_question_right_answer', true );
        $type = Sensei()->question->get_question_type( $question_id );
        $type_name = __( 'Multiple Choice', 'woothemes-sensei' );
        $grade_type = 'manual-grade';

        if ('boolean'== $type ) {

            $right_answer = ucfirst($right_answer);

        }elseif( 'multiple-choice' == $type ) {

            $right_answer = (array) $right_answer;
            $right_answer = implode( ', ', $right_answer );

        }elseif( 'gap-fill' == $type ) {

            $right_answer_array = explode( '||', $right_answer );
            if ( isset( $right_answer_array[0] ) ) { $gapfill_pre = $right_answer_array[0]; } else { $gapfill_pre = ''; }
            if ( isset( $right_answer_array[1] ) ) { $gapfill_gap = $right_answer_array[1]; } else { $gapfill_gap = ''; }
            if ( isset( $right_answer_array[2] ) ) { $gapfill_post = $right_answer_array[2]; } else { $gapfill_post = ''; }

            $right_answer = $gapfill_pre . ' <span class="highlight">' . $gapfill_gap . '</span> ' . $gapfill_post;

        }else{

            // for non auto gradable question types no answer should be returned.
            $right_answer = '';

        }

        /**
         * Filters the correct answer response.
         *
         * Can be used for text filters.
         *
         * @since 1.9.7
         *
         * @param string $right_answer Correct answer.
         * @param int    $question_id  Question ID
         */
        return apply_filters( 'sensei_questions_get_correct_answer', $right_answer, $question_id );

    } // get_correct_answer

} // End Class

/**
 * Class WooThemes_Sensei_Question
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Question extends Sensei_Question{}
