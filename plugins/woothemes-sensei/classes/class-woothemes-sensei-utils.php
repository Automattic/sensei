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
 * - sensei_get_activity_value()
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
		global $wpdb;

		// Args, minimum data required for WP
		$data = array(
					'comment_post_ID' => intval( $args['post_id'] ),
					'comment_author' => '', // Not needed
					'comment_author_email' => '', // Not needed
					'comment_author_url' => '', // Not needed
					'comment_content' => esc_html( $args['data'] ),
					'comment_type' => esc_attr( $args['type'] ),
					'user_id' => intval( $args['user_id'] ),
					'comment_approved' => 1,
				);
		// Allow extra data
		if ( !empty($args['username']) ) {
			$data['comment_author'] = sanitize_user( $args['username'] );
		}
		if ( !empty($args['user_email']) ) {
			$data['comment_author_email'] = sanitize_email( $args['user_email'] );
		}
		if ( !empty($args['user_url']) ) {
			$data['comment_author_url'] = esc_url( $args['user_url'] );
		}
		if ( !empty($args['parent']) ) {
			$data['comment_parent'] = $args['parent'];
		}
		do_action( 'sensei_log_activity_before', $args, $data );

		$flush_cache = false;
		// Custom Logic
		// Check if comment exists first
		$comment_id = $wpdb->get_var( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s ", $args['post_id'], $args['user_id'], $args['type'] ) );
		if ( ! $comment_id ) {
			// Add the comment
			$comment_id = wp_insert_comment( $data );
			$flush_cache = true;
		}
		elseif ( isset( $args['action'] ) && 'update' == $args['action'] ) {
			// Update the comment if an update was requested
			$data['comment_ID'] = $comment_id;
			// By default update the timestamp of the comment
			if ( empty($args['keep_time']) ) {
				$data['comment_date'] = current_time('mysql');
			}
			wp_update_comment( $data );
			$flush_cache = true;
		} // End If Statement
		// Manually Flush the Cache
		if ( $flush_cache ) {
			wp_cache_flush();
		}

		do_action( 'sensei_log_activity_after', $args, $data );

		if ( 0 < $comment_id ) {
			// Return the ID so that it can be used for meta data storage
			return $comment_id;
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
		if ( !$return_comments ) {
			$args['count'] = true;
		}
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
		return intval($comments); // This is the count, check the return from WP_Comment_Query
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
				} elseif( isset( $args['field'] ) && 'user_id' == $args['field'] ) {
					array_push( $post_ids, $value->user_id );
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
	 * @return boolean
	 */
	public static function sensei_delete_activities ( $args = array() ) {
		$dataset_changes = false;
		// If activity exists
		// Remove activity from log
   	    $comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => intval( $args['post_id'] ), 'user_id' => intval( $args['user_id'] ), 'type' => esc_attr( $args['type'] ) ), true );
    	if( $comments ) {
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
     * Delete all activity for specified user
     * @access public
	 * @since  1.5.0
     * @param  integer $user_id User ID
     * @return boolean
     */
    public static function delete_all_user_activity( $user_id = 0 ) {

    	$dataset_changes = false;

    	if( $user_id ) {

			$activities = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_id ), true );

			if( $activities ) {
				foreach( $activities as $activity ) {
					if( '' == $activity->comment_type ) continue;
					if( strpos( 'sensei_', $activity->comment_type ) != 0 ) continue;
					$dataset_changes = wp_delete_comment( intval( $activity->comment_ID ), true );
					wp_cache_flush();
				}
			}
		}

		return $dataset_changes;
	} // Edn delete_all_user_activity()


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

		foreach ( $orders as $order_id ) {
			$order = new WC_Order( $order_id->ID );
			if ( $order->status == 'completed' ) {
				if ( 0 < sizeof( $order->get_items() ) ) {
					foreach( $order->get_items() as $item ) {

						// Check if user has bought product
						if ( $item['product_id'] == $product_id || $item['variation_id'] == $product_id ) {

							// Check if user has an active subscription for product
							if( class_exists( 'WC_Subscriptions_Manager' ) ) {
								$sub_key = WC_Subscriptions_Manager::get_subscription_key( $order_id->ID, $product_id );
								if( $sub_key ) {
									$sub = WC_Subscriptions_Manager::get_subscription( $sub_key );
									if( $sub && isset( $sub['status'] ) ) {
										if( 'active' == $sub['status'] ) {
											return true;
										} else {
											return false;
										}
									}
								}
							}

							// Customer has bought product
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
	public static function sensei_text_editor( $content = '', $editor_id = 'senseitexteditor', $input_name = '' ) {

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
				'theme_advanced_buttons1' => $buttons,
				'theme_advanced_buttons2' => ''
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
	public static function sensei_save_quiz_answers( $submitted = false, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
		} else {
			$user = get_userdata( $user_id );
		}

		$answers_saved = false;

		if( $submitted && intval( $user_id ) > 0 ) {

    		foreach( $submitted as $question_id => $answer ) {

    			// Get question type
    			$question_types = wp_get_post_terms( $question_id, 'question-type' );
    			foreach( $question_types as $type ) {
    				$question_type = $type->slug;
    			}

    			if( ! $question_type ) {
    				$question_type = 'multiple-choice';
    			}

    			// Sanitise answer
    			if( 0 == get_magic_quotes_gpc() ) {
    				$answer = wp_unslash( $answer );
    			}
    			switch( $question_type ) {
    				case 'multi-line': $answer = nl2br( $answer ); break;
    				case 'single-line': break;
    				case 'gap-fill': break;
    				default: $answer = maybe_serialize( $answer ); break;
    			}
    			$args = array(
								    'post_id' => $question_id,
								    'username' => $user->user_login,
								    'user_email' => $user->user_email,
								    'user_url' => $user->user_url,
								    'data' => base64_encode( $answer ),
								    'type' => 'sensei_user_answer', /* FIELD SIZE 20 */
								    'parent' => 0,
								    'user_id' => $user_id,
								    'action' => 'update'
								);
				$answers_saved = WooThemes_Sensei_Utils::sensei_log_activity( $args );
    		}

    		// Handle file upload questions
    		if( isset( $_FILES ) ) {
				foreach( $_FILES as $field => $file ) {
					if( strpos( $field, 'file_upload_' ) !== false ) {
						$question_id = str_replace( 'file_upload_', '', $field );
						if( $file && $question_id ) {
							$attachment_id = self::upload_file( $file );
							if( $attachment_id ) {
								$args = array(
								    'post_id' => $question_id,
								    'username' => $user->user_login,
								    'user_email' => $user->user_email,
								    'user_url' => $user->user_url,
								    'data' => base64_encode( $attachment_id ),
								    'type' => 'sensei_user_answer', /* FIELD SIZE 20 */
								    'parent' => 0,
								    'user_id' => $user_id,
								    'action' => 'update'
								);
								$answers_saved = WooThemes_Sensei_Utils::sensei_log_activity( $args );
							}
						}
					}
				}
			}
    	}

    	return $answers_saved;
	} // End sensei_save_quiz_answers()

	public static function upload_file( $file = array() ) {

		require_once( ABSPATH . 'wp-admin/includes/admin.php' );

        $file_return = wp_handle_upload( $file, array('test_form' => false ) );

        if( isset( $file_return['error'] ) || isset( $file_return['upload_error_handler'] ) ) {
            return false;
        } else {

            $filename = $file_return['file'];

            $attachment = array(
                'post_mime_type' => $file_return['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content' => '',
                'post_status' => 'inherit',
                'guid' => $file_return['url']
            );

            $attachment_id = wp_insert_attachment( $attachment, $file_return['url'] );

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata( $attachment_id, $filename );
            wp_update_attachment_metadata( $attachment_id, $attachment_data );

            if( 0 < intval( $attachment_id ) ) {
            	return $attachment_id;
            }
        }

        return false;
	}

	/**
	 * Grade quiz automatically
	 * @param  integer $quiz_id         ID of quiz
	 * @param  integer $lesson_id       ID of lesson
	 * @param  boolean $submitted       Submitted answers
	 * @param  integer $total_questions Total questions in quiz
	 * @return boolean                  Whether quiz was successfully graded or not
	 */
	public static function sensei_grade_quiz_auto( $quiz_id = 0, $submitted = false, $total_questions = 0, $quiz_grade_type = 'auto' ) {
		global $current_user;

		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}
		$grade = 0;
		$correct_answers = 0;
		$quiz_graded = false;
		$quiz_autogradable = true;

		if( intval( $quiz_id ) > 0 && $submitted ) {

			if( $quiz_grade_type == 'auto' ) {
				// Can only autograde these question types
				$autogradable_question_types = apply_filters( 'sensei_autogradable_question_types', array( 'multiple-choice', 'boolean' ) );
				$grade_total = 0;
				foreach( $submitted as $question_id => $answer ) {
					// check if the question is autogradable
					$question_type = get_the_terms( $question_id, 'question-type' );
					$question_type = $question_type[0]->slug;
					if ( in_array( $question_type, $autogradable_question_types ) ) {
						// Get user question grade
						$question_grade = WooThemes_Sensei_Utils::sensei_grade_question_auto( $question_id, $question_type, $answer, $user_id );
						$grade_total += $question_grade;
					}
					else {
						// There is a question that cannot be autograded
						$quiz_autogradable = false;
					}
				}
				// Only if the whole quiz was autogradable do we set a grade
				if ( $quiz_autogradable ) {
					$quiz_total = WooThemes_Sensei_Utils::sensei_get_quiz_total( $quiz_id );

					$grade = abs( round( ( doubleval( $grade_total ) * 100 ) / ( $quiz_total ), 2 ) );

					$activity_logged = WooThemes_Sensei_Utils::sensei_grade_quiz( $quiz_id, $grade, $user_id, $quiz_grade_type );
				}
				else {
					$grade = new WP_Error('autograde', __('Quiz is not fully autogradeable due to questions used.', 'woothemes-sensei'));
				}
			}
		}

		return $grade;
	} // End sensei_grade_quiz_auto()

	/**
	 * Grade quiz
	 * @param  integer $quiz_id ID of quiz
	 * @param  integer $grade   Grade received
	 * @param  integer $user_id ID of user being graded
	 * @return boolean
	 */
	public static function sensei_grade_quiz( $quiz_id = 0, $grade = 0, $user_id = 0, $quiz_grade_type = 'auto' ) {
		if( intval( $user_id ) == 0 ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
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

			$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );
			do_action( 'sensei_user_quiz_grade', $user_id, $quiz_id, $grade, $quiz_passmark, $quiz_grade_type );
		}

		return $activity_logged;
	}

	/**
	 * Grade question automatically
	 * @param  integer $question_id ID of question
	 * @param  string  $answer      User's answer
	 * @return integer              User's grade for question
	 */
	public static function sensei_grade_question_auto( $question_id = 0, $question_type = '', $answer = '', $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$question_grade = 0;
		if( intval( $question_id ) > 0 ) {
			if ( empty($question_type) ) {
				$question_type = get_the_terms( $question_id, 'question-type' );
				$question_type = $question_type[0]->slug;
			}
			// Allow full override of autograding
			$question_grade = apply_filters( 'sensei_pre_grade_question_auto', $question_grade, $question_id, $question_type, $answer );
			if ( !$question_grade ) {
				switch( $question_type ) {
					case 'multiple-choice':
					case 'boolean' :
						$right_answer = (array) get_post_meta( $question_id, '_question_right_answer', true );

						if( 0 == get_magic_quotes_gpc() ) {
							$answer = wp_unslash( $answer );
						}
						$answer = (array) $answer;
						if ( is_array( $right_answer ) && count( $right_answer ) == count( $answer ) ) {
							// Loop through all answers ensure none are 'missing'
							$all_correct = true;
							foreach ( $answer as $check_answer ) {
								if ( !in_array( $check_answer, $right_answer ) ) {
									$all_correct = false;
								}
							}
							// If all correct then grade
							if ( $all_correct ) {
								$question_grade = get_post_meta( $question_id, '_question_grade', true );
								if( ! $question_grade || $question_grade == '' ) {
									$question_grade = 1;
								}
							}
						}
						break;
					default:
						// Allow autograding of any other question type
						$question_grade = apply_filters( 'sensei_grade_question_auto', $question_grade, $question_id, $question_type, $answer );
						break;
				} // switch question_type
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
	public static function sensei_grade_question( $question_id = 0, $grade = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
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

			$answer_notes = get_post_meta( $question_id, '_answer_feedback', true );
			WooThemes_Sensei_Utils::sensei_add_answer_notes( $question_id, $user_id, $answer_notes );
		}

		return $activity_logged;
	}

	public static function sensei_delete_question_grade( $question_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user = wp_get_current_user();
		} else {
			$user = get_userdata( $user_id );
		}

		$activity_logged = false;
		if( intval( $question_id ) > 0 ) {

			$args = array(
							    'post_id' => $question_id,
							    'username' => $user->user_login,
							    'user_email' => $user->user_email,
							    'user_url' => $user->user_url,
							    'data' => '',
							    'type' => 'sensei_user_grade', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $user->ID,
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
	public static function sensei_start_lesson( $lesson_id = 0, $user_id = 0, $complete = false ) {
		global $woothemes_sensei;

		if( intval( $user_id ) == 0 ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
		} else {
			$user = get_userdata( $user_id );
		}

		$activity_logged = false;

		if( intval( $lesson_id ) > 0 ) {

			$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			if( $course_id ) {
				$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_id, 'user_id' => $user->ID, 'type' => 'sensei_course_start' ) );
				if( ! $is_user_taking_course ) {
					WooThemes_Sensei_Utils::user_start_course( $user->ID, $course_id );
				}
			}

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

			do_action( 'sensei_user_lesson_start', $user_id, $lesson_id );

			if( $complete ) {
				$args = array(
                                    'post_id' => $lesson_id,
                                    'username' => $user->user_login,
                                    'user_email' => $user->user_email,
                                    'user_url' => $user->user_url,
                                    'data' => __( 'Lesson completed and passed by the user', 'woothemes-sensei' ),
                                    'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
                                    'parent' => 0,
                                    'user_id' => $user_id
                                );
                WooThemes_Sensei_Utils::sensei_log_activity( $args );

                do_action( 'sensei_user_lesson_end', $user_id, $lesson_id );

                $quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
				$quiz_args = array(
								'post_id' => $quiz_id,
								'username' => $user->user_login,
								'user_email' => $user->user_email,
								'user_url' => $user->user_url,
								'data' => 100,
								'type' => 'sensei_quiz_grade', /* FIELD SIZE 20 */
								'parent' => 0,
								'user_id' => $user_id,
								'action' => 'update'
							);

				WooThemes_Sensei_Utils::sensei_log_activity( $quiz_args );
			}
		}

		return $activity_logged;
	}

	public static function sensei_remove_user_from_lesson( $lesson_id = 0, $user_id = 0 ) {

		if( ! $lesson_id ) return false;

		if( intval( $user_id ) == 0 ) {
			$user = wp_get_current_user();
		} else {
			$user = get_userdata( $user_id );
		}

		// Process all quizzes
		$quizzes = WooThemes_Sensei_Lesson::lesson_quizzes( $lesson_id );
		foreach( $quizzes as $quiz ) {

			// Delete quiz answers
			WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $quiz->ID, $user->ID );

			// Delete quiz grade
			WooThemes_Sensei_Utils::sensei_delete_quiz_grade( $quiz->ID, $user->ID );

			// Delete all question grades
			$questions = WooThemes_Sensei_Utils::sensei_get_quiz_questions( $quiz->ID );
			foreach( $questions as $question ) {
				WooThemes_Sensei_Utils::sensei_delete_question_grade( $question->ID, $user->ID );
			}
		}

		// Delete lesson activities
		$activities = array( 'sensei_lesson_start', 'sensei_lesson_end' );
		foreach( $activities as $activity ) {

			$args = array(
			    'post_id' => $lesson_id,
			    'username' => $user->user_login,
			    'user_email' => $user->user_email,
			    'user_url' => $user->user_url,
			    'data' => '',
			    'type' => $activity,
			    'parent' => 0,
			    'user_id' => $user->ID,
			    'action' => 'update'
			);

			WooThemes_Sensei_Utils::sensei_delete_activities( $args );
		}

		return true;
	}

	public static function sensei_remove_user_from_course( $course_id = 0, $user_id = 0 ) {

		if( ! $course_id ) return false;

		if( intval( $user_id ) == 0 ) {
			$user = wp_get_current_user();
		} else {
			$user = get_userdata( $user_id );
		}

		$lessons = WooThemes_Sensei_Course::course_lessons( $course_id );

		foreach( $lessons as $lesson ) {
			WooThemes_Sensei_Utils::sensei_remove_user_from_lesson( $lesson->ID, $user->ID );
		}

		// Delete course activities
		$activities = array( 'sensei_course_start' );
		foreach( $activities as $activity ) {

			$args = array(
			    'post_id' => $course_id,
			    'username' => $user->user_login,
			    'user_email' => $user->user_email,
			    'user_url' => $user->user_url,
			    'data' => '',
			    'type' => $activity,
			    'parent' => 0,
			    'user_id' => $user->ID,
			    'action' => 'update'
			);

			WooThemes_Sensei_Utils::sensei_delete_activities( $args );
		}

		return true;
	}

	public static function sensei_get_quiz_questions( $quiz_id = 0 ) {
		global $woothemes_sensei;

		$questions = array();

		if( intval( $quiz_id ) > 0 ) {
			$questions = $woothemes_sensei->post_types->lesson->lesson_quiz_questions( $quiz_id );
			$questions = WooThemes_Sensei_Utils::array_sort_reorder( $questions );
		}

		return $questions;
	}

	public static function sensei_get_quiz_total( $quiz_id = 0 ) {

		$quiz_total = 0;

		if( $quiz_id > 0 ) {
			$questions = WooThemes_Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
			$question_grade = 0;
			foreach( $questions as $question ) {
				$question_grade = get_post_meta( $question->ID, '_question_grade', true );
				if( ! $question_grade || $question_grade == '' ) {
                	$question_grade = 1;
                }
				$quiz_total += $question_grade;
			}
		}

		return $quiz_total;
	}

	public static function sensei_get_user_question_grade( $question_id = 0, $user_id = 0 ) {
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

	public static function sensei_delete_quiz_answers( $quiz_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
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

	public static function sensei_delete_quiz_grade( $quiz_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$delete_answers = false;
		if( intval( $quiz_id ) > 0 ) {
			$delete_answers = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade' ) );
		}

		return $delete_answers;
	}

	/**
	 * Add answer notes to question
	 * @param  integer $question_id ID of question
	 * @param  integer $user_id     ID of user
	 * @return boolean
	 */
	public static function sensei_add_answer_notes( $question_id = 0, $user_id = 0, $notes = '' ) {
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$activity_logged = false;

		if( intval( $question_id ) > 0 ) {
			$notes = base64_encode( $notes );
			$args = array(
							    'post_id' => $question_id,
							    'username' => $user->user_login,
							    'user_email' => $user->user_email,
							    'user_url' => $user->user_url,
							    'data' => $notes,
							    'type' => 'sensei_answer_notes', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $user_id,
							    'action' => 'update'
							);
			// Don't store empty values, no point
			if ( !empty($notes) ) {
				$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
			}
			else {
				$activity_logged = true;
			}
		}

		return $activity_logged;
	}

	/**
	 * array_sort_reorder handle sorting of table data
	 * @since  1.3.0
	 * @param  array $return_array data to be ordered
	 * @return array $return_array ordered data
	 */
	public static function array_sort_reorder( $return_array ) {
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
	public static function sort_array_by_key( $array, $key ) {
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
	public static function lesson_quiz_questions( $quiz_id = 0 ) {
		$questions_array = array();
		if ( 0 < $quiz_id ) {
			$question_args = array( 'post_type'         => 'question',
                                    'numberposts'       => -1,
                                    'orderby'           => 'ID',
                                    'order'             => 'ASC',
                                    'meta_query'		=> array(
										array(
											'key'       => '_quiz_id',
											'value'     => $quiz_id,
										)
									),
                                    'post_status'       => 'any',
                                    'suppress_filters'  => 0
                                );
            $questions_array = get_posts( $question_args );
        } // End If Statement
        return $questions_array;
	} // End lesson_quiz_questions()

	/**
	 * Get pass mark for course
	 * @param  integer $course_id ID of course
	 * @return integer            Pass mark for course
	 */
	public static function sensei_course_pass_grade( $course_id = 0 ) {
		global $woothemes_sensei;

		$course_passmark = 0;

		if( $course_id > 0 ) {
			$lessons = $woothemes_sensei->post_types->course->course_lessons( $course_id );
			$lesson_count = 0;
			$total_passmark = 0;
			foreach( $lessons as $lesson ) {

				// Get Quiz ID
                $quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson->ID );

                // Get quiz passmark
                $quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );

                // Add up total passmark
                $total_passmark += $quiz_passmark;

                ++$lesson_count;
			}

			$course_passmark = ( $total_passmark / $lesson_count );
		}

		return round( $course_passmark );
	}

	/**
	 * Get user total grade for course
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 * @return integer            User's total grade
	 */
	public static function sensei_course_user_grade( $course_id = 0, $user_id = 0 ) {
		global $woothemes_sensei;

		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$total_grade = 0;

		if( $course_id > 0 && $user_id > 0 ) {
			$lessons = $woothemes_sensei->post_types->course->course_lessons( $course_id );
			$lesson_count = 0;
			$total_grade = 0;
			foreach( $lessons as $lesson ) {

				// Get Quiz ID
                $quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson->ID );

                // Get user quiz grade
                $quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );

                // Add up total grade
                $total_grade += $quiz_grade;

                ++$lesson_count;
			}

			$total_grade = ( $total_grade / $lesson_count );
		}

		return round( $total_grade );
	}

	/**
	 * Check if user has passed a course
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 * @return boolean
	 */
	public static function sensei_user_passed_course( $course_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$pass = false;

		if( $course_id > 0 && $user_id > 0 ) {
			$passmark = WooThemes_Sensei_Utils::sensei_course_pass_grade( $course_id );
			$user_grade = WooThemes_Sensei_Utils::sensei_course_user_grade( $course_id, $user_id );

			if( $user_grade >= $passmark ) {
				$pass = true;
			}
		}

		return $pass; // Should add the $passmark and $user_grade as part of the return!
	}

	/**
	 * Set the status message displayed to the user for a course
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 * @return array              Status code and message
	 */
	public static function sensei_user_course_status_message( $course_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		$status = 'not_started';
		$box_class = 'info';
		$message = __( 'You have not started this course yet.', 'woothemes-sensei' );

		if( $course_id > 0 && $user_id > 0 ) {

			$started_course = sensei_has_user_started_course( $course_id, $user_id );

			if( $started_course ) {
				$passmark = WooThemes_Sensei_Utils::sensei_course_pass_grade( $course_id ); // This happens inside sensei_user_passed_course()!
				$user_grade = WooThemes_Sensei_Utils::sensei_course_user_grade( $course_id, $user_id ); // This happens inside sensei_user_passed_course()!
				$user_pass = WooThemes_Sensei_Utils::sensei_user_passed_course( $course_id, $user_id );

				if( $user_pass ) {
					$status = 'passed';
					$box_class = 'tick';
					$message = sprintf( __( 'You have passed this course with a grade of %1$d%%.', 'woothemes-sensei' ), $user_grade );
				} else {
					$status = 'failed';
					$box_class = 'alert';
					$message = sprintf( __( 'You require %1$d%% to pass this course. Your grade is %2$d%%.', 'woothemes-sensei' ), $passmark, $user_grade );
				}
			}

		}

		$message = apply_filters( 'sensei_user_course_status_' . $status, $message );

		return array( 'status' => $status, 'box_class' => $box_class, 'message' => $message );
	}

	/**
	 * Set the status message displayed to the user for a quiz
	 * @param  integer $lesson_id ID of quiz lesson
	 * @param  integer $user_id   ID of user
	 * @return array              Status code and message
	 */
	public static function sensei_user_quiz_status_message( $lesson_id = 0, $user_id = 0, $is_lesson = false ) {
		global $woothemes_sensei, $current_user;
		if( intval( $user_id ) == 0 ) {
			$user_id = $current_user->ID;
		}

		$status = 'not_started';
		$box_class = 'info';
		$message = __( 'You have not taken this lesson\'s quiz yet', 'woothemes-sensei' );
		$extra = '';

		if( $lesson_id > 0 && $user_id > 0 ) {

			// Prerequisite lesson
			$prerequisite = get_post_meta( $lesson_id, '_lesson_prerequisite', true );

			// Course ID
			$course_id = absint( get_post_meta( $lesson_id, '_lesson_course', true ) );

			// Has user started course
			$started_course = sensei_has_user_started_course( $course_id, $user_id );

			// Has use completed lesson
			$lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
			$lesson_complete = false;
			if ( '' != $lesson_end ) {
				$lesson_complete = true;
			}

			// Quiz ID
            $quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );

            // Quiz grade
			$quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );

			// Quiz passmark
			$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );
			$quiz_passmark_float = (float) $quiz_passmark;

			// Pass required
			$pass_required = get_post_meta( $quiz_id, '_pass_required', true );

			// Quiz questions
			$quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $quiz_id );

			if ( ! $started_course ) {

				$status = 'not_started_course';
				$box_class = 'info';
				$message = sprintf( __( 'Please sign up for %1$sthe course%2$s before taking this quiz', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( __( 'Sign Up', 'woothemes-sensei' ) ) . '">', '</a>' );

			} elseif ( ! is_user_logged_in() ) {

				$status = 'login_required';
				$box_class = 'info';
				$message = __( 'You must be logged in to take this quiz', 'woothemes-sensei' );

			} elseif ( isset( $lesson_complete ) && $lesson_complete ) {

				if ( isset( $quiz_grade ) && ! is_bool( $quiz_grade ) && abs( $quiz_grade ) >= 0 ) {

					if ( $quiz_grade >= abs( round( $quiz_passmark_float, 2 ) ) ) {

						$status = 'passed';
						$box_class = 'tick';
						if( $is_lesson ) {
							if ( 0 < count( $quiz_questions ) ) {
								$message = sprintf( __( 'Congratulations! You have passed this lesson\'s quiz achieving %d%%', 'woothemes-sensei' ), round( $quiz_grade ) );
							} else {
								$message = sprintf( __( 'Congratulations! You have passed this lesson.', 'woothemes-sensei' ) );
							} // End If Statement
						} else {
							$message = sprintf( __( 'Congratulations! You have passed this quiz achieving %d%%', 'woothemes-sensei' ), round( $quiz_grade ) );
						}

					} else {

						if( $pass_required ) {

							$status = 'failed';
							$box_class = 'alert';
							if( $is_lesson ) {
								$message = sprintf( __( 'You require %1$d%% to pass this lesson\'s quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $quiz_grade ) );
							} else {
								$message = sprintf( __( 'You require %1$d%% to pass this quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $quiz_grade ) );
							}

						}

					}

				} else {

					$status = 'complete';
					$box_class = 'info';
					if( $is_lesson ) {
						if ( $pass_required ) {
							$message = sprintf( __( 'You have completed this lesson\'s quiz and it will be graded soon. %1$sView the lesson quiz%2$s', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $quiz_id ) ) . '" title="' . esc_attr( get_the_title( $quiz_id ) ) . '">', '</a>' );
						} else {
							$message = sprintf( __( 'Congratulations! You have passed this lesson.', 'woothemes-sensei' ) );
						}
					} else {
						$message = sprintf( __( 'You have completed this quiz and it will be graded soon. You require %1$d%% to pass.', 'woothemes-sensei' ), round( $quiz_passmark ) );
					}
				}

			} else {
				if ( isset( $quiz_grade ) && ! is_bool( $quiz_grade ) && abs( $quiz_grade ) >= 0 ) {
					if ( $quiz_grade >= abs( round( $quiz_passmark_float, 2 ) ) ) {

						$status = 'passed';
						$box_class = 'tick';
						if( $is_lesson ) {
							if( $pass_required ) {
								$message = sprintf( __( 'Congratulations! You have passed this lesson\'s quiz achieving %d%%', 'woothemes-sensei' ), round( $quiz_grade ) );
							} else {
								$message = sprintf( __( 'Congratulations! You have passed this lesson.', 'woothemes-sensei' ) );
							}
						} else {
							$message = sprintf( __( 'Congratulations! You have passed this quiz achieving %d%%', 'woothemes-sensei' ), round( $quiz_grade ) );
						}

					} else {

						if( $pass_required ) {

							$status = 'failed';
							$box_class = 'alert';
							if( $is_lesson ) {
								$message = sprintf( __( 'You require %1$d%% to pass this lesson\'s quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $quiz_grade ) );
							} else {
								$message = sprintf( __( 'You require %1$d%% to pass this quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $quiz_grade ) );
							}

						}

					}
				} else {

					if( $pass_required ) {
						$status = 'not_started';
						$box_class = 'info';
						if( $is_lesson ) {
							$message = sprintf( __( 'You require %1$d%% to pass this lesson\'s quiz.', 'woothemes-sensei' ), round( $quiz_passmark ) );
						} else {
							$message = sprintf( __( 'You require %1$d%% to pass this quiz.', 'woothemes-sensei' ), round( $quiz_passmark ) );
						}
					}
				}
			}

		}

		$message = apply_filters( 'sensei_user_quiz_status_' . $status, $message );

		if( $is_lesson && ! in_array( $status, array( 'login_required', 'not_started_course' ) ) ) {
			$extra = '<p><a class="button" href="' . esc_url( get_permalink( $quiz_id ) ) . '" title="' . esc_attr( apply_filters( 'sensei_view_quiz_text', __( 'View the lesson quiz', 'woothemes-sensei' ) ) ) . '">' . apply_filters( 'sensei_view_quiz_text', __( 'View the lesson quiz', 'woothemes-sensei' ) ) . '</a></p>';
		}

		return array( 'status' => $status, 'box_class' => $box_class, 'message' => $message, 'extra' => $extra );
	}

	/**
	 * Start course for user
	 * @since  1.4.8
	 * @param  integer $user_id   User ID
	 * @param  integer $course_id Course ID
	 * @return void
	 */
	public static function user_start_course( $user_id = 0, $course_id = 0 ) {

		if( $user_id && $course_id ) {

			// Get user object
			$user = get_userdata( $user_id );

			// Add user to course
			$args = array(
			    'post_id' => $course_id,
			    'username' => $user->user_login,
			    'user_email' => $user->user_email,
			    'user_url' => $user->user_url,
			    'data' => __( 'Course started by the user', 'woothemes-sensei' ),
			    'type' => 'sensei_course_start', /* FIELD SIZE 20 */
			    'parent' => 0,
			    'user_id' => $user_id
			);
			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

			// Allow further actions
			if ( $activity_logged ) {
				do_action( 'sensei_user_course_start', $user_id, $course_id );
				return true;
			}
		}

		return false;
	}

	public static function user_completed_course( $course_id = 0, $user_id = 0 ) {
		global $woothemes_sensei;

		if( $course_id ) {

			if( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user_course_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_id, 'user_id' => $user_id, 'type' => 'sensei_course_end', 'field' => 'comment_content' ) );
			// Completion is unknown
			if ( empty( $user_course_end ) ) {
				$course_lessons = $woothemes_sensei->post_types->course->course_lessons( $course_id );
				$lessons_completed = 0;
				foreach ( $course_lessons as $lesson ){

					$single_lesson_complete = WooThemes_Sensei_Utils::user_completed_lesson( $lesson->ID, $user_id );

					if( $single_lesson_complete ) {
						$lessons_completed++;
					}
					else {
						// If the lesson isn't complete no need to check the rest of the lessons
						break;
					}

				} // End For Loop

				if ( absint( $lessons_completed ) == absint( count( $course_lessons ) ) && ( 0 < absint( count( $course_lessons ) ) ) && ( 0 < absint( $lessons_completed ) ) ) {
					// Mark for future checks that the Course is complete
					$user = get_user_by( 'id', intval( $user_id ) );
					$args = array(
								'post_id' => $course_id,
								'username' => $user->user_login,
								'user_email' => $user->user_email,
								'user_url' => $user->user_url,
								'data' => __( 'Course completed by the user', 'woothemes-sensei' ),
								'type' => 'sensei_course_end', /* FIELD SIZE 20 */
								'parent' => 0,
								'user_id' => $user->ID,
								'action' => 'update'
							);
					WooThemes_Sensei_Utils::sensei_log_activity( $args );
					do_action( 'sensei_user_course_end', $user_id, $course_id );
					return true; // All lessons complete
				}
				return false;
			}
			return true; // Have sensei_course_end log
		}
		return false;
	}

	public static function user_completed_lesson( $lesson_id = 0, $user_id = 0 ) {
		global $woothemes_sensei;

		if( $lesson_id ) {
			if( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
			if ( '' != $user_lesson_end ) {

				//Check for Passed or Completed Setting
				$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];

				if ( 'passed' == $course_completion ) {

					// If Setting is Passed -> Check for Quiz Grades
					// Get Quiz ID
					$lesson_quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );

					if ( $lesson_quiz_id ) {
						// Get quiz pass setting
						$pass_required = get_post_meta( $lesson_quiz_id, '_pass_required', true );

						if ( $pass_required ) {

							$passed_quiz = WooThemes_Sensei_Utils::user_passed_quiz( $lesson_quiz_id, $user_id );

							if( $passed_quiz ) {
								return true;
							}

						} else {
							return true;
						}
					} // End If Statement
				} else {
					return true;
				} // End If Statement;
			} // End If Statement
		}

		return false;
	}

	public static function is_preview_lesson( $lesson_id ) {
		$is_preview = false;

		if( 'lesson' == get_post_type( $lesson_id ) ) {
			$lesson_preview = get_post_meta( $lesson_id, '_lesson_preview', true );
			if ( isset( $lesson_preview ) && '' != $lesson_preview ) {
				$is_preview = true;
			}
		}

		return $is_preview;
	}

	public static function user_passed_quiz( $quiz_id = 0, $user_id = 0 ) {

		if( ! $quiz_id  ) return false;

		if( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Quiz Grade
		$quiz_grade = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
		// Check if Grade is greater than or equal to pass percentage
		$quiz_passmark = abs( round( doubleval( get_post_meta( $quiz_id, '_quiz_passmark', true ) ), 2 ) );
		if ( $quiz_passmark <= intval( $quiz_grade ) ) {
			return true;
		}

		return false;

	}

} // End Class
?>