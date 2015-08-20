<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading User Quiz Class
 *
 * All functionality pertaining to the Admin Grading User Profile in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.3.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - build_data_array()
 * - display()
 */
class WooThemes_Sensei_Grading_User_Quiz {
	public $user_id;
	public $lesson_id;
	public $quiz_id;

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct ( $user_id = 0, $quiz_id = 0 ) {
		$this->user_id = intval( $user_id );
		$this->quiz_id = intval( $quiz_id );
		$this->lesson_id = get_post_meta( $this->quiz_id, '_quiz_lesson', true );
	} // End __construct()

	/**
	 * build_data_array builds the data for use on the page
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return array
	 */
	public function build_data_array() {
		$data_array = WooThemes_Sensei_Utils::sensei_get_quiz_questions( $this->quiz_id );
		return $data_array;
	} // End build_data_array()

	/**
	 * Display output to the admin view
     *
     * This view is shown when grading a quiz for a single user in admin under grading
     *
	 * @since  1.3.0
	 * @return html
	 */
	public function display() {
		// Get data for the user
		$questions = $this->build_data_array();

        global $woothemes_sensei;
		$count = 0;
		$graded_count = 0;
		$user_quiz_grade_total = 0;
		$quiz_grade_total = 0;
		$quiz_grade = 0;
        $lesson_id = $this->lesson_id;
        $user_id = $this->user_id;

		?><form name="<?php esc_attr_e( 'quiz_' . $this->quiz_id ); ?>" action="" method="post">
			<?php wp_nonce_field( 'sensei_manual_grading', '_wp_sensei_manual_grading_nonce' ); ?>
			<input type="hidden" name="sensei_manual_grade" value="<?php esc_attr_e( $this->quiz_id ); ?>" />
			<input type="hidden" name="sensei_grade_next_learner" value="<?php esc_attr_e( $this->user_id ); ?>" />
			<div class="total_grade_display">
				<span><?php esc_attr_e( __( 'Grade:', 'woothemes-sensei' ) ); ?></span>
				<span class="total_grade_total"><?php echo $user_quiz_grade_total; ?></span> / <span class="quiz_grade_total"><?php echo $quiz_grade_total; ?></span> (<span class="total_grade_percent"><?php echo $quiz_grade; ?></span>%)
			</div>
			<div class="buttons">
				<input type="submit" value="<?php esc_attr_e( __( 'Save', 'woothemes-sensei' ) ); ?>" class="grade-button button-primary" title="Saves grades as currently marked on this page" />
				<input type="button" value="<?php esc_attr_e( __( 'Auto grade', 'woothemes-sensei' ) ); ?>" class="autograde-button button-secondary" title="Where possible, automatically grades questions that have not yet been graded" />
				<input type="reset" value="<?php esc_attr_e( __( 'Reset', 'woothemes-sensei' ) ); ?>" class="reset-button button-secondary" title="Resets all questions to ungraded and total grade to 0" />
			</div>
			<div class="clear"></div><br/><?php

		$lesson_status_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $this->lesson_id, 'user_id' => $this->user_id, 'type' => 'sensei_lesson_status', 'field' => 'comment_ID' ) );
		$user_quiz_grade = get_comment_meta( $lesson_status_id, 'grade', true );
		$correct_answers = 0;

		foreach( $questions as $question ) {
			$question_id = $question->ID;
			++$count;

			$type = false;
			$type_name = '';

			$types = wp_get_post_terms( $question_id, 'question-type' );
			foreach( $types as $t ) {
				$type = $t->name;
				break;
			}

			if( ! $type ) {
				$type = 'multiple-choice';
			}

			$question_answer_notes = $woothemes_sensei->quiz->get_user_question_feedback( $lesson_id, $question_id, $user_id );


			$question_grade_total = get_post_meta( $question_id, '_question_grade', true );
			if( ! $question_grade_total || 0 == intval( $question_grade_total ) ) {
				$question_grade_total = 1;
			}
			$quiz_grade_total += $question_grade_total;

			$right_answer = get_post_meta( $question_id, '_question_right_answer', true );
			$user_answer_content = $woothemes_sensei->quiz->get_user_question_answer( $lesson_id, $question_id, $user_id );
			$type_name = __( 'Multiple Choice', 'woothemes-sensei' );
			$grade_type = 'manual-grade';

			switch( $type ) {
				case 'boolean':
					$type_name = __( 'True/False', 'woothemes-sensei' );
					$right_answer = ucfirst( $right_answer );
					$user_answer_content = ucfirst( $user_answer_content );
					$grade_type = 'auto-grade';
				break;
				case 'multiple-choice':
					$type_name = __( 'Multiple Choice', 'woothemes-sensei' );
					$grade_type = 'auto-grade';
				break;
				case 'gap-fill':
					$type_name = __( 'Gap Fill', 'woothemes-sensei' );

					$right_answer_array = explode( '||', $right_answer );
					if ( isset( $right_answer_array[0] ) ) { $gapfill_pre = $right_answer_array[0]; } else { $gapfill_pre = ''; }
					if ( isset( $right_answer_array[1] ) ) { $gapfill_gap = $right_answer_array[1]; } else { $gapfill_gap = ''; }
					if ( isset( $right_answer_array[2] ) ) { $gapfill_post = $right_answer_array[2]; } else { $gapfill_post = ''; }

					if( ! $user_answer_content ) {
						$user_answer_content = '______';
					}

					$right_answer = $gapfill_pre . ' <span class="highlight">' . $gapfill_gap . '</span> ' . $gapfill_post;
					$user_answer_content = $gapfill_pre . ' <span class="highlight">' . $user_answer_content . '</span> ' . $gapfill_post;
					$grade_type = 'auto-grade';

				break;
				case 'multi-line':
					$type_name = __( 'Multi Line', 'woothemes-sensei' );
					$grade_type = 'manual-grade';
				break;
				case 'single-line':
					$type_name = __( 'Single Line', 'woothemes-sensei' );
					$grade_type = 'manual-grade';
				break;
				case 'file-upload':
					$type_name = __( 'File Upload', 'woothemes-sensei' );
					$grade_type = 'manual-grade';

					// Get uploaded file
					if( $user_answer_content ) {
						$attachment_id = $user_answer_content;
						$answer_media_url = $answer_media_filename = '';
						if( 0 < intval( $attachment_id ) ) {
							$answer_media_url = wp_get_attachment_url( $attachment_id );
							$answer_media_filename = basename( $answer_media_url );
							if( $answer_media_url && $answer_media_filename ) {
								$user_answer_content = sprintf( __( 'Submitted file: %1$s', 'woothemes-sensei' ), '<a href="' . esc_url( $answer_media_url ) . '" target="_blank">' . esc_html( $answer_media_filename ) . '</a>' );
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
			$user_answer_content = (array) $user_answer_content;
			$right_answer = (array) $right_answer;
			$question_title = sprintf( __( 'Question %d: ', 'woothemes-sensei' ), $count ) . $type_name;

			$graded_class = '';
			$user_question_grade = $woothemes_sensei->quiz->get_user_question_grade( $lesson_id, $question_id, $user_id );
			$graded_class = 'ungraded';
			if( intval( $user_question_grade ) > 0 ) {
				$graded_class = 'user_right';
				++$correct_answers;
				$user_quiz_grade_total += $user_question_grade;
				++$graded_count;
			} else {
				if( ! is_string( $user_question_grade ) && intval( $user_question_grade ) == 0 ) {
					$graded_class = 'user_wrong';
					++$graded_count;
				}
				$user_question_grade = 0;
			}

			?><div class="postbox question_box <?php esc_attr_e( $type ); ?> <?php esc_attr_e( $grade_type ); ?> <?php esc_attr_e( $graded_class ); ?>" id="<?php esc_attr_e( 'question_' . $question_id . '_box' ); ?>">
				<div class="handlediv" title="Click to toggle"><br></div>
				<h3 class="hndle"><span><?php echo $question_title; ?></span></h3>
				<div class="inside">
					<div class="sensei-grading-actions">
						<div class="actions">
							<input type="hidden" class="question_id" value="<?php esc_attr_e( $question_id ); ?>" />
							<input type="hidden" class="question_total_grade" name="question_total_grade" value="<?php echo $question_grade_total; ?>" />
							<span class="grading-mark icon_right"><input type="radio" class="<?php esc_attr_e( 'question_' . $question_id . '_right_option' ); ?>" name="<?php esc_attr_e( 'question_' . $question_id ); ?>" value="right" <?php checked( $graded_class, 'user_right', true ); ?> /></span>
							<span class="grading-mark icon_wrong"><input type="radio" class="<?php esc_attr_e( 'question_' . $question_id . '_wrong_option' ); ?>" name="<?php esc_attr_e( 'question_' . $question_id ); ?>" value="wrong" <?php checked( $graded_class, 'user_wrong', true ); ?> /></span>
							<input type="number" class="question-grade" name="<?php esc_attr_e( 'question_' . $question_id . '_grade' ); ?>" id="<?php esc_attr_e( 'question_' . $question_id . '_grade' ); ?>" value="<?php esc_attr_e( $user_question_grade ); ?>" min="0" max="<?php esc_attr_e( $question_grade_total ); ?>" />
							<span class="question-grade-total"><?php echo $question_grade_total; ?></span>
						</div>
					</div>
					<div class="sensei-grading-answer">
						<h4><?php echo apply_filters( 'sensei_question_title', $question->post_title ); ?></h4>
						<?php echo apply_filters( 'the_content', $question->post_content );?>
						<p class="user-answer"><?php
							foreach ( $user_answer_content as $_user_answer ) {
								echo apply_filters( 'sensei_answer_text', $_user_answer ) . "<br>";
							}
						?></p>
						<div class="right-answer">
							<h5><?php _e( 'Correct answer', 'woothemes-sensei' ) ?></h5>
							<span class="correct-answer"><?php
								foreach ( $right_answer as $_right_answer ) {
									echo apply_filters( 'sensei_answer_text', $_right_answer ) . "<br>";
								}
							?></span>
						</div>
						<div class="answer-notes">
							<h5><?php _e( 'Grading Notes', 'woothemes-sensei' ) ?></h5>
							<textarea class="correct-answer" name="questions_feedback[<?php esc_attr_e( $question_id ); ?>]" placeholder="<?php _e( 'Add notes here...', 'woothemes-sensei' ) ?>"><?php echo $question_answer_notes; ?></textarea>
						</div>
					</div>
				</div>
			</div><?php
		}

		$quiz_grade = intval( $user_quiz_grade );
		$all_graded = 'no';
		if( intval( $count ) == intval( $graded_count ) ) {
			$all_graded = 'yes';
		}

		?>  <input type="hidden" name="total_grade" id="total_grade" value="<?php esc_attr_e( $user_quiz_grade_total ); ?>" />
			<input type="hidden" name="total_questions" id="total_questions" value="<?php esc_attr_e( $count ); ?>" />
			<input type="hidden" name="quiz_grade_total" id="quiz_grade_total" value="<?php esc_attr_e( $quiz_grade_total ); ?>" />
			<input type="hidden" name="total_graded_questions" id="total_graded_questions" value="<?php esc_attr_e( $graded_count ); ?>" />
			<input type="hidden" name="all_questions_graded" id="all_questions_graded" value="<?php esc_attr_e( $all_graded ); ?>" />
			<div class="total_grade_display">
				<span><?php esc_attr_e( __( 'Grade:', 'woothemes-sensei' ) ); ?></span>
				<span class="total_grade_total"><?php echo $user_quiz_grade_total; ?></span> / <span class="quiz_grade_total"><?php echo $quiz_grade_total; ?></span> (<span class="total_grade_percent"><?php echo $quiz_grade; ?></span>%)
			</div>
			<div class="buttons">
				<input type="submit" value="<?php esc_attr_e( 'Save' ); ?>" class="grade-button button-primary" title="Saves grades as currently marked on this page" />
				<input type="button" value="<?php esc_attr_e( __( 'Auto grade', 'woothemes-sensei' ) ); ?>" class="autograde-button button-secondary" title="Where possible, automatically grades questions that have not yet been graded" />
				<input type="reset" value="<?php esc_attr_e( __( 'Reset', 'woothemes-sensei' ) ); ?>" class="reset-button button-secondary" title="Resets all questions to ungraded and total grade to 0" />
			</div>
			<div class="clear"></div>
			<script type="text/javascript">
				jQuery( window ).load( function() {
					// Calculate total grade on page load to make sure everything is set up correctly
					jQuery.fn.autoGrade();
				});
			</script>
		</form><?php
	} // End display()

} // End Class
