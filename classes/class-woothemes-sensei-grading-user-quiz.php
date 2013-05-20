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

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct ( $user_id = 0, $quiz_id = 0 ) {
		$this->user_id = intval( $user_id );
		$this->quiz_id = intval( $quiz_id );
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
	 * display output to the admin view
	 * @since  1.3.0
	 * @return html
	 */
	public function display() {
		// Get data for the user
		$questions = $this->build_data_array();

		?><form name="<?php esc_attr_e( 'quiz_' . $this->quiz_id ); ?>" action="" method="post">
			<?php wp_nonce_field( 'sensei_manual_grading', '_wp_sensei_manual_grading_nonce' ); ?>
			<input type="hidden" name="sensei_manual_grade" value="<?php esc_attr_e( $this->quiz_id ); ?>" />
			<input type="hidden" name="sensei_grade_next_learner" value="<?php esc_attr_e( $this->user_id ); ?>" />
			<div class="buttons">
				<input type="submit" value="<?php esc_attr_e( __( 'Grade', 'woothemes-sensei' ) ); ?>" class="grade-button button-primary" title="Saves grades as currently marked on this page" />
				<input type="reset" value="<?php esc_attr_e( __( 'Reset', 'woothemes-sensei' ) ); ?>" class="reset-button button-secondary" title="Resets all questions to ungraded and total grade to 0" />
				<input type="button" value="<?php esc_attr_e( __( 'Auto grade', 'woothemes-sensei' ) ); ?>" class="autograde-button button-secondary" title="Where possible, automatically grades questions that have not yet been graded" />
			</div>
			<div class="clear"></div><br/><?php

		$user_quiz_grade = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $this->quiz_id, 'user_id' => $this->user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );

		$count = 0;
		$correct_answers = 0;
		foreach( $questions as $question ) {
			$question_id = $question->ID;
			++$count;

			$types = wp_get_post_terms( $question_id, 'question-type' );
			foreach( $types as $t ) {
				$type = $t->name;
				break;
			}

			$right_answer = stripslashes( get_post_meta( $question_id, '_question_right_answer', true ) );
			$user_answer = maybe_unserialize( base64_decode( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question_id, 'user_id' => $this->user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_content' ) ) ) );
			$grade_type = 'manual-grade';

			switch( $type ) {
				case 'boolean':
					$type_name = __( 'True/False', 'woothemes-sensei' );
					$right_answer = ucfirst( $right_answer );
					$user_answer = ucfirst( $user_answer );
					$grade_type = 'auto-grade';
				break;
				case 'multiple-choice':
					$type_name = __( 'Multiple Choice', 'woothemes-sensei' );
					$grade_type = 'auto-grade';
				break;
				case 'gap-fill':
					$type_name = __( 'Gap Fill', 'woothemes-sensei' );

					$right_answer_array = explode( '|', $right_answer );
					if ( isset( $right_answer_array[0] ) ) { $gapfill_pre = $right_answer_array[0]; } else { $gapfill_pre = ''; }
					if ( isset( $right_answer_array[1] ) ) { $gapfill_gap = $right_answer_array[1]; } else { $gapfill_gap = ''; }
					if ( isset( $right_answer_array[2] ) ) { $gapfill_post = $right_answer_array[2]; } else { $gapfill_post = ''; }

					$right_answer = $gapfill_pre . ' <span class="highlight">' . $gapfill_gap . '</span> ' . $gapfill_post;
					$user_answer = $gapfill_pre . ' <span class="highlight">' . $user_answer . '</span> ' . $gapfill_post;
					$grade_type = 'auto-grade';

				break;
				case 'multi-line':
					$type_name = __( 'Multi Line', 'woothemes-sensei' );
					$grade_type = 'manual-grade';
				break;
				case 'essay-paste':
					$type_name = __( 'Essay Paste', 'woothemes-sensei' );
					$grade_type = 'manual-grade';
				break;
				case 'single-line':
					$type_name = __( 'Single Line', 'woothemes-sensei' );
					$grade_type = 'manual-grade';
				break;
				default:
					// Nothing
				break;
			}

			$question_title = sprintf( __( 'Question %d: ', 'woothemes-sensei' ), $count ) . $type_name;

			if( ! is_bool( $user_quiz_grade ) ) {
				$graded_class = '';
				$user_question_grade = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question_id, 'user_id' => $this->user_id, 'type' => 'sensei_user_grade', 'field' => 'comment_content' ) );
				$graded_class = 'ungraded';
				if( intval( $user_question_grade ) > 0 ) {
					$graded_class = 'user_right';
					++$correct_answers;
				} else {
					if( ! is_bool( $user_question_grade ) && intval( $user_question_grade ) == 0 ) {
						$graded_class = 'user_wrong';
					}
				}
			}
			?><div class="postbox question_box <?php esc_attr_e( $type ); ?> <?php esc_attr_e( $grade_type ); ?> <?php esc_attr_e( $graded_class ); ?>" id="<?php esc_attr_e( 'question_' . $question_id . '_box' ); ?>">
				<div class="handlediv" title="Click to toggle"><br></div>
				<h3 class="hndle"><span><?php echo $question_title; ?></span></h3>
				<div class="inside">
					<div class="sensei-grading-actions">
						<div class="right-answer">
							<h5><?php _e( 'Correct answer', 'woothemes-sensei' ) ?></h5>
							<span class="correct-answer"><?php echo $right_answer; ?></span>
						</div>
						<div class="actions">
							<input type="hidden" class="question_id" value="<?php esc_attr_e( $question_id ); ?>" />
							<input type="hidden" name="<?php esc_attr_e( 'question_' . $question_id . '_grade' ); ?>" id="<?php esc_attr_e( 'question_' . $question_id . '_grade' ); ?>" value="1" />
							<span class="grading-mark icon_right"><input type="radio" name="<?php esc_attr_e( 'question_' . $question_id ); ?>" value="right" <?php checked( $graded_class, 'user_right', true ); ?> /></span>
							<span class="grading-mark icon_wrong"><input type="radio" name="<?php esc_attr_e( 'question_' . $question_id ); ?>" value="wrong" <?php checked( $graded_class, 'user_wrong', true ); ?> /></span>
						</div>
					</div>
					<div class="sensei-grading-answer">
						<h4><?php echo $question->post_title; ?></h4>
						<p class="user-answer"><?php echo $user_answer; ?></p>
					</div>
					<div class="clear"></div>
				</div>
			</div><?php
		}

		$quiz_grade = intval( $user_quiz_grade );

		?>  <input type="hidden" name="total_grade" id="total_grade" value="<?php esc_attr_e( $correct_answers ); ?>" />
			<input type="hidden" name="total_questions" id="total_questions" value="<?php esc_attr_e( $count ); ?>" />
			<div class="total_grade_display">
				<span id="total_grade_total"><?php echo $correct_answers; ?></span> / <?php echo $count; ?> (<span id="total_grade_percent"><?php echo $quiz_grade; ?></span>%)
			</div>
			<div class="buttons">
				<input type="submit" value="<?php esc_attr_e( __( 'Grade', 'woothemes-sensei' ) ); ?>" class="grade-button button-primary" title="Saves grades as currently marked on this page" />
				<input type="reset" value="<?php esc_attr_e( __( 'Reset', 'woothemes-sensei' ) ); ?>" class="reset-button button-secondary" title="Resets all questions to ungraded and total grade to 0" />
				<input type="button" value="<?php esc_attr_e( __( 'Auto grade', 'woothemes-sensei' ) ); ?>" class="autograde-button button-secondary" title="Where possible, automatically grades questions that have not yet been graded" />
			</div>
			<div class="clear"></div>
		</form><?php
	} // End display()

} // End Class
?>