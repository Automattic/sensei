<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Utilities Class
 *
 * Common utility functions for Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Utilities
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - get_placeholder_image()
 * - sensei_is_woocommerce_present()
 * - sensei_is_woocommerce_activated()
 * - sensei_log_Activity()
 * - sensei_check_for_activity()
 * - sensei_activity_ids()
 * - sensei_delete_activites()
 * - sensei_ get_activity_value()
 * - sensei_customer_bought_product()
 */
class WooThemes_Sensei_Utils {
	/**
	 * Get the placeholder thumbnail image.
	 * @access  public
	 * @since   1.0.0
	 * @return  string The URL to the placeholder thumbnail image.
	 */
	public static function get_placeholder_image () {
		global $woothemes_sensei;
		return esc_url( apply_filters( 'sensei_placeholder_thumbnail', $woothemes_sensei->plugin_url . 'assets/images/placeholder.png' ) );
	} // End get_placeholder_image()

	/**
	 * Check if WooCommerce is present.
	 * @access public
	 * @since  1.0.2
	 * @static
	 * @return void
	 */
	public static function sensei_is_woocommerce_present () {
		if ( class_exists( 'Woocommerce' ) ) {
			return true;
		} else {
			$active_plugins = apply_filters( 'active_plugins', get_option('active_plugins' ) );
			if ( is_array( $active_plugins ) && in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) {
				return true;
			} else {
				return false;
			} // End If Statement
		} // End If Statement
	} // End sensei_is_woocommerce_present()

	/**
	 * Check if WooCommerce is active.
	 * @access public
	 * @since  1.0.2
	 * @static
	 * @return void
	 */
	public static function sensei_is_woocommerce_activated () {
		global $woothemes_sensei;
		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_present() && isset( $woothemes_sensei->settings->settings['woocommerce_enabled'] ) && $woothemes_sensei->settings->settings['woocommerce_enabled'] ) { return true; } else { return false; }
	} // End sensei_is_woocommerce_activated()

	/**
	 * Log an activity item.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return void
	 */
	public static function sensei_log_activity ( $args = array() ) {
		// Setup & Prep Data
		$time = current_time('mysql');
		// Args
		$data = array(
					    'comment_post_ID' => intval( $args['post_id'] ),
					    'comment_author' => sanitize_user( $args['username'] ),
					    'comment_author_email' => sanitize_email( $args['user_email'] ),
					    'comment_author_url' => esc_url( $args['user_url'] ),
					    'comment_content' => esc_html( $args['data'] ),
					    'comment_type' => esc_attr( $args['type'] ),
					    'comment_parent' => $args['parent'],
					    'user_id' => intval( $args['user_id'] ),
					    'comment_date' => $time,
					    'comment_approved' => 1,
					);

		do_action( 'sensei_log_activity_before', $args, $data );

		// Custom Logic
		// Check if comment exists first
		if ( isset( $args['action'] ) && 'update' == $args['action'] ) {
			// Get existing comments ids
			$activity_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'post_id' => intval( $args['post_id'] ), 'user_id' => intval( $args['user_id'] ), 'type' => esc_attr( $args['type'] ), 'field' => 'comment' ) );
			if ( isset( $activity_ids[0] ) && 0 < $activity_ids[0] ) {
				$comment_id = $activity_ids[0];
			} // End If Statement
			$commentarr = array();
			if ( isset( $comment_id ) && 0 < $comment_id ) {
				// Get the comment
				$commentarr = get_comment( $comment_id, ARRAY_A );
			} // End If Statement
			if ( isset( $commentarr['comment_ID'] ) && 0 < $commentarr['comment_ID'] ) {
				// Update the comment
				$data['comment_ID'] = $commentarr['comment_ID'];
				$comment_id = wp_update_comment( $data );
			} else {
				// Add the comment
				$comment_id = wp_insert_comment( $data );
			} // End If Statement
		} else {
			// Add the comment
			$comment_id = wp_insert_comment( $data );
		} // End If Statement
		// Manually Flush the Cache
		wp_cache_flush();

		do_action( 'sensei_log_activity_after', $args, $data );

		if ( 0 < $comment_id ) {
			return true;
		} else {
			return false;
		} // End If Statement
	} // End sensei_log_activity()


	/**
	 * Check for Sensei activity.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @param  bool $return_comments (default: false)
	 * @return void
	 */
	public static function sensei_check_for_activity ( $args = array(), $return_comments = false ) {
		global $woothemes_sensei;
		if ( is_admin() ) {
			remove_filter( 'comments_clauses', array( $woothemes_sensei->admin, 'comments_admin_filter' ) );
		} // End If Statement
		// Get comments
		$comments = get_comments( $args );
		if ( is_admin() ) {
			add_filter( 'comments_clauses', array( $woothemes_sensei->admin, 'comments_admin_filter' ) );
		} // End If Statement
		// Return comments
		if ( $return_comments ) {
			return $comments;
		} // End If Statement
		// Count comments
		if ( is_array( $comments ) && ( 0 < intval( count( $comments ) ) ) ) {
			return true;
		} else {
			return false;
		} // End If Statement
	} // End sensei_check_for_activity()


	/**
	 * Get IDs of Sensei activity items.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return void
	 */
	public static function sensei_activity_ids ( $args = array() ) {
		global $woothemes_sensei;
		if ( is_admin() ) {
			remove_filter( 'comments_clauses', array( $woothemes_sensei->admin, 'comments_admin_filter' ) );
		} // End If Statement
		// Get comments
		$comments = get_comments( $args );
		$post_ids = array();
		// Count comments
		if ( is_array( $comments ) && ( 0 < intval( count( $comments ) ) ) ) {
			foreach ( $comments as $key => $value  ) {
				// Add matches to id array
				if ( isset( $args['field'] ) && 'comment' == $args['field'] ) {
					array_push( $post_ids, $value->comment_ID );
				} else {
					array_push( $post_ids, $value->comment_post_ID );
				} // End If Statement
			} // End For Loop
			// Reset array indexes
			$post_ids = array_unique( $post_ids );
			$post_ids = array_values( $post_ids );
		} // End If Statement
		if ( is_admin() ) {
			add_filter( 'comments_clauses', array( $woothemes_sensei->admin, 'comments_admin_filter' ) );
		} // End If Statement
		return $post_ids;
	} // End sensei_activity_ids()


	/**
	 * Delete Sensei activities.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return void
	 */
	public static function sensei_delete_activities ( $args = array() ) {
		$dataset_changes = false;
		// If activity exists
		if ( WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => intval( $args['post_id'] ), 'user_id' => intval( $args['user_id'] ), 'type' => esc_attr( $args['type'] ) ) ) ) {
			// Remove activity from log
    	    $comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => intval( $args['post_id'] ), 'user_id' => intval( $args['user_id'] ), 'type' => esc_attr( $args['type'] ) ), true );
    	    foreach ( $comments as $key => $value  ) {
    	    	if ( isset( $value->comment_ID ) && 0 < $value->comment_ID ) {
		    		$dataset_changes = wp_delete_comment( intval( $value->comment_ID ), true );
		    		// Manually flush the cache
		    		wp_cache_flush();
		    	} // End If Statement
		    } // End For Loop
    	} // End If Statement
    	return $dataset_changes;
    } // End sensei_delete_activities()


	/**
	 * Get value for a specified activity.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return void
	 */
	public static function sensei_get_activity_value ( $args = array() ) {
		global $woothemes_sensei;
		if ( is_admin() ) {
			remove_filter( 'comments_clauses', array( $woothemes_sensei->admin, 'comments_admin_filter' ) );
		} // End If Statement
		$activity_value = false;
		if ( isset( $args['user_id'] ) && 0 < intval( $args['user_id'] ) ) {
			// Get activities
			$comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => intval( $args['post_id'] ), 'user_id' => intval( $args['user_id'] ), 'type' => esc_attr( $args['type'] ) ), true );
			foreach ( $comments as $key => $value ) {
				// Get the activity value
			    if ( isset( $value->{$args['field']} ) && '' != $value->{$args['field']} ) {
			    	$activity_value = $value->{$args['field']};
			    } // End If Statement
			} // End For Loop
		} // End If Statement
		if ( is_admin() ) {
			add_filter( 'comments_clauses', array( $woothemes_sensei->admin, 'comments_admin_filter' ) );
		} // End If Statement
		return $activity_value;
	} // End sensei_get_activity_value()

	/**
	 * Checks if a user (by email) has bought an item.
	 * @access public
	 * @since  1.0.0
	 * @param  string $customer_email
	 * @param  int $user_id
	 * @param  int $product_id
	 * @return bool
	 */
	public static function sensei_customer_bought_product ( $customer_email, $user_id, $product_id ) {
		global $wpdb;

		$emails = array();

		if ( $user_id ) {
			$user = get_user_by( 'id', intval( $user_id ) );
			$emails[] = $user->user_email;
		}

		if ( is_email( $customer_email ) )
			$emails[] = $customer_email;

		if ( sizeof( $emails ) == 0 )
			return false;

		$orders = get_posts( array(
		    'numberposts' => -1,
		    'meta_key'    => '_customer_user',
		    'meta_value'  => intval( $user_id ),
		    'post_type'   => 'shop_order',
		    'post_status' => 'publish'
		) );

		// REFACTOR - check if $orders contains items and return appropriate response (false?) if not.

		foreach ( $orders as $order_id ) {
			$order = new WC_Order( $order_id->ID );
			if ( $order->status == 'completed' ) {
				if ( 0 < sizeof( $order->get_items() ) ) {
					foreach( $order->get_items() as $item ) {
						if ( $item['product_id'] == $product_id || $item['variation_id'] == $product_id ) {
							return true;
						} // End If Statement
					} // End For Loop
				} // End If Statement
			} // End If Statement
		} // End For Loop
	} // End sensei_customer_bought_product()

	/**
	 * Load the WordPress rich text editor
	 * @param  string $content    Initial content for editor
	 * @param  string $editor_id  ID of editor (only lower case characters - no spaces, underscores, hyphens, etc.)
	 * @param  string $input_name Name for textarea form element
	 * @return void
	 */
	public function sensei_text_editor( $content = '', $editor_id = 'senseitexteditor', $input_name = '' ) {

		if( ! $input_name ) $input_name = $editor_id;

		$buttons = 'bold,italic,underline,strikethrough,blockquote,bullist,numlist,justifyleft,justifycenter,justifyright,undo,redo,pastetext';

		$settings = array(
			'media_buttons' => false,
			'wpautop' => true,
			'textarea_name' => $input_name,
			'editor_class' => 'sensei_text_editor',
			'teeny' => false,
			'dfw' => false,
			'tinymce' => array(
				'theme_advanced_buttons1' => $buttons
			),
			'quicktags' => false
		);

		wp_editor( $content, $editor_id, $settings );

	} // End sensei_text_editor()

	/**
	 * Save quiz answers submitted by users
	 * @param  boolean $submitted User's quiz answers
	 * @return boolean            Whether the answers were saved or not
	 */
	public function sensei_save_quiz_answers( $submitted = false, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
			$user = $current_user;
		} else {
			$user = get_userdata( $user_id );
		}

		$answers_saved = false;

		if( $submitted && intval( $user_id ) > 0 ) {
    		foreach( $submitted as $question_id => $answer ) {
    			$args = array(
								    'post_id' => $question_id,
								    'username' => $user->user_login,
								    'user_email' => $user->user_email,
								    'user_url' => $user->user_url,
								    'data' => base64_encode( maybe_serialize( $answer ) ),
								    'type' => 'sensei_user_answer', /* FIELD SIZE 20 */
								    'parent' => 0,
								    'user_id' => $user_id,
								    'action' => 'update'
								);
				$answers_saved = WooThemes_Sensei_Utils::sensei_log_activity( $args );
    		}
    	}

    	return $answers_saved;
	} // End sensei_save_quiz_answers()

	/**
	 * Grade quiz automatically
	 * @param  integer $quiz_id         ID of quiz
	 * @param  integer $lesson_id       ID of lesson
	 * @param  boolean $submitted       Submitted answers
	 * @param  integer $total_questions Total questions in quiz
	 * @return boolean                  Whether quiz was successfully graded or not
	 */
	public function sensei_grade_quiz_auto( $quiz_id = 0, $submitted = false, $total_questions = 0, $quiz_grade_type = 'auto' ) {
		global $current_user;

		$grade = 0;
		$correct_answers = 0;
		$quiz_graded = false;

		if( intval( $quiz_id ) > 0 && $submitted && intval( $total_questions ) > 0 ) {

			if( $quiz_grade_type == 'auto' ) {
				$grade_total = 0;
				foreach( $submitted as $question_id => $answer ) {
					$question_grade = WooThemes_Sensei_Utils::sensei_grade_question_auto( $question_id, $answer );
					$grade_total += $question_grade;
				}

				$grade = abs( round( ( doubleval( $grade_total ) * 100 ) / ( $total_questions ), 2 ) );

				$activity_logged = WooThemes_Sensei_Utils::sensei_grade_quiz( $quiz_id, $grade );
			}
		}

		return $grade;
	} // End sensei_grade_quiz_auto()

	/**
	 * Grade question
	 * @param  integer $quiz_id ID of quiz
	 * @param  integer $grade   Grade received
	 * @param  integer $user_id ID of user being graded
	 * @return boolean
	 */
	public function sensei_grade_quiz( $quiz_id = 0, $grade = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
			$user = $current_user;
		} else {
			$user = get_userdata( $user_id );
		}

		$activity_logged = false;
		if( intval( $quiz_id ) > 0 ) {

			$args = array(
							    'post_id' => $quiz_id,
							    'username' => $user->user_login,
							    'user_email' => $user->user_email,
							    'user_url' => $user->user_url,
							    'data' => $grade,
							    'type' => 'sensei_quiz_grade', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $user_id,
							    'action' => 'update'
							);

			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
		}

		return $activity_logged;
	}

	/**
	 * Grade question automatically
	 * @param  integer $question_id ID of question
	 * @param  string  $answer      User's answer
	 * @return integer              User's grade for question
	 */
	public function sensei_grade_question_auto( $question_id = 0, $answer = '', $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		$question_grade = 0;
		if( intval( $question_id ) > 0 ) {
			$right_answer = get_post_meta( $question_id, '_question_right_answer', true );
			if ( 0 == strcmp( $right_answer, $answer ) ) {
				// TO DO: Enable custom grades for questions
				$question_grade = 1;
			}
			$activity_logged = WooThemes_Sensei_Utils::sensei_grade_question( $question_id, $question_grade, $user_id );
		}

		return $question_grade;
	}

	/**
	 * Grade question
	 * @param  integer $question_id ID of question
	 * @param  integer $grade       Grade received
	 * @return boolean
	 */
	public function sensei_grade_question( $question_id = 0, $grade = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
			$user = $current_user;
		} else {
			$user = get_userdata( $user_id );
		}

		$activity_logged = false;
		if( intval( $question_id ) > 0 && intval( $user_id ) > 0 ) {

			$args = array(
							    'post_id' => $question_id,
							    'username' => $user->user_login,
							    'user_email' => $user->user_email,
							    'user_url' => $user->user_url,
							    'data' => $grade,
							    'type' => 'sensei_user_grade', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $user_id,
							    'action' => 'update'
							);
			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
		}

		return $activity_logged;
	}

	public function sensei_delete_question_grade( $question_id = 0 ) {
		global $current_user;

		$activity_logged = false;
		if( intval( $question_id ) > 0 ) {

			$args = array(
							    'post_id' => $question_id,
							    'username' => $current_user->user_login,
							    'user_email' => $current_user->user_email,
							    'user_url' => $current_user->user_url,
							    'data' => '',
							    'type' => 'sensei_user_grade', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $current_user->ID,
							    'action' => 'update'
							);

			$activity_logged = WooThemes_Sensei_Utils::sensei_delete_activities( $args );
		}

		return $activity_logged;
	}

	/**
	 * Marked lesson as started for user
	 * @param  integer $lesson_id ID of lesson
	 * @return boolean
	 */
	public function sensei_start_lesson( $lesson_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
			$user = $current_user;
		} else {
			$user = get_userdata( $user_id );
		}

		$activity_logged = false;

		if( intval( $lesson_id ) > 0 ) {
			$args = array(
							    'post_id' => $lesson_id,
							    'username' => $user->user_login,
							    'user_email' => $user->user_email,
							    'user_url' => $user->user_url,
							    'data' => __( 'Lesson started by the user', 'woothemes-sensei' ),
							    'type' => 'sensei_lesson_start', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $user_id
							);

			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
		}

		return $activity_logged;
	}

	public function sensei_get_quiz_questions( $quiz_id = 0 ) {

		$questions = array();

		if( intval( $quiz_id ) > 0 ) {
			$args = array(	'post_type' 		=> 'question',
								'numberposts' 		=> -1,
								'orderby'         	=> 'ID',
	    						'order'           	=> 'ASC',
	    						'meta_key'        	=> '_quiz_id',
	    						'meta_value'      	=> $quiz_id,
	    						'post_status'		=> 'publish',
								'suppress_filters' 	=> 0
								);
			$questions = get_posts( $args );

			$questions = WooThemes_Sensei_Utils::array_sort_reorder( $questions );
		}

		return $questions;
	}

	public function sensei_get_user_question_grade( $question_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		$question_grade = false;
		if( intval( $question_id ) > 0 ) {
			$question_grade = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question_id, 'user_id' => $user_id, 'type' => 'sensei_user_grade', 'field' => 'comment_content' ) );
		}

		return $question_grade;
	}

	public function sensei_delete_quiz_answers( $quiz_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		$delete_answers = false;
		if( intval( $quiz_id ) > 0 ) {
			$questions = WooThemes_Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
			foreach( $questions as $question ) {
				$delete_answers = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $question->ID, 'user_id' => $user_id, 'type' => 'sensei_user_answer' ) );
			}
		}

		return $delete_answers;
	}

	/**
	 * array_sort_reorder handle sorting of table data
	 * @since  1.3.0
	 * @param  array $return_array data to be ordered
	 * @return array $return_array ordered data
	 */
	public function array_sort_reorder( $return_array ) {
		if ( isset( $_GET['orderby'] ) && '' != esc_html( $_GET['orderby'] ) ) {
			$sort_key = '';
			// if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->sortable_columns ) ) {
			// 	$sort_key = esc_html( $_GET['orderby'] );
			// } // End If Statement
			if ( '' != $sort_key ) {
					WooThemes_Sensei_Utils::sort_array_by_key($return_array,$sort_key);
				if ( isset( $_GET['order'] ) && 'desc' == esc_html( $_GET['order'] ) ) {
					$return_array = array_reverse( $return_array, true );
				} // End If Statement
			} // End If Statement
			return $return_array;
		} else {
			return $return_array;
		} // End If Statement
	} // End array_sort_reorder()

	/**
	 * sort_array_by_key sorts array by key
	 * @since  1.3.0
	 * @param  $array by ref
	 * @param  $key string column name in array
	 * @return void
	 */
	public function sort_array_by_key( &$array, $key ) {
	    $sorter = array();
	    $ret = array();
	    reset( $array );
	    foreach ( $array as $ii => $va ) {
	        $sorter[$ii] = $va[$key];
	    } // End For Loop
	    asort( $sorter );
	    foreach ( $sorter as $ii => $va ) {
	        $ret[$ii] = $array[$ii];
	    } // End For Loop
	    $array = $ret;
	} // End sort_array_by_key()

	/**
	 * lesson_quiz_questions gets array of lesson quiz questions
	 * @since  1.3.2
	 * @param  integer $quiz_id
	 * @return array of quiz questions
	 */
	public function lesson_quiz_questions( $quiz_id = 0 ) {
		$questions_array = array();
		if ( 0 < $quiz_id ) {
			$question_args = array( 'post_type'         => 'question',
                                    'numberposts'       => -1,
                                    'orderby'           => 'ID',
                                    'order'             => 'ASC',
                                    'meta_key'          => '_quiz_id',
                                    'meta_value'        => $quiz_id,
                                    'post_status'       => 'any',
                                    'suppress_filters'  => 0
                                );
            $questions_array = get_posts( $question_args );
        } // End If Statement
        return $questions_array;
	} // End lesson_quiz_questions()

} // End Class
?>