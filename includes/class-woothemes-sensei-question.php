<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Question Class
 *
 * All functionality pertaining to the questions post type in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - question_types()
 * -add_column_headings()
 * -add_column_data()
 * -question_edit_panel_metabox()
 * -question_edit_panel()
 * -question_lessons_panel()
 * -save_question()
 * -filter_options()
 * -filter_actions()
 * -get_question_type()
 */
class WooThemes_Sensei_Question {
	public $token;
	public $meta_fields;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {
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
		global $woothemes_sensei, $post, $pagenow;

		add_action( 'admin_enqueue_scripts', array( $woothemes_sensei->post_types->lesson, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $woothemes_sensei->post_types->lesson, 'enqueue_styles' ) );

		$html = '<div id="lesson-quiz" class="single-question"><div id="add-question-main">';

		if( 'post-new.php' == $pagenow ) {

			$html .= '<div id="add-question-actions">';
				$html .= $woothemes_sensei->post_types->lesson->quiz_panel_add( 'question' );
			$html .= '</div>';

		} else {
			$question_id = $post->ID;

			$question_type =  Sensei()->question->get_question_type( $post->ID );

			$html .= '<div id="add-question-metadata"><table class="widefat">';
				$html .= $woothemes_sensei->post_types->lesson->quiz_panel_question( $question_type, 0, $question_id, 'question' );
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

		$quizzes = get_post_meta( $post->ID, '_quiz_id', false );

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

        global $woothemes_sensei;

        //setup the data for saving
		$data = $_POST ;
        $data['quiz_id'] = 0;
		$data['question_id'] = $post_id;

		if ( ! wp_is_post_revision( $post_id ) ){

			// Unhook function to prevent infinite loops
			remove_action( 'save_post', array( $this, 'save_question' ) );

			// Update question data
			$question_id = $woothemes_sensei->post_types->lesson->lesson_save_question( $data, 'question' );

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
		return $question_grade;

	} // end get_question_grade

} // End Class