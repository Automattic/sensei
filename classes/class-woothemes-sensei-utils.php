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
 * - sensei_log_activity()
 * - sensei_check_for_activity()
 * - sensei_activity_ids()
 * - sensei_delete_activities()
 * - delete_all_user_activity()
 * - sensei_get_activity_value()
 * - sensei_customer_bought_product()
 * - sensei_text_editor()
 * - sensei_save_quiz_answers()
 * - upload_file()
 * - sensei_grade_quiz_auto()
 * - sensei_grade_quiz()
 * - sensei_grade_question_auto()
 * - sensei_grade_question()
 * - sensei_delete_question_grade()
 * - user_start_lesson()
 * - sensei_start_lesson()
 * - sensei_remove_user_from_lesson()
 * - sensei_remove_user_from_course()
 * - sensei_get_quiz_questions()
 * - sensei_get_quiz_total()
 * - sensei_get_user_question_grade()
 * - sensei_get_user_question_answer_notes()
 * - sensei_delete_quiz_answers()
 * - sensei_delete_quiz_grade()
 * - sensei_add_answer_notes()
 * - array_sort_reorder()
 * - sort_array_by_key()
 * - lesson_quiz_questions()
 * - sensei_course_pass_grade()
 * - sensei_course_user_grade()
 * - sensei_user_passed_course()
 * - sensei_user_course_status_message()
 * - sensei_user_quiz_status_message()
 * - user_start_course()
 * - user_started_course()
 * - user_complete_course()
 * - user_completed_course()
 * - user_started_lesson()
 * - user_completed_lesson()
 * - user_course_status()
 * - user_lesson_status()
 * - is_preview_lesson()
 * - user_passed_quiz()
 * - update_lesson_status()
 * - update_course_status()
 * - single_comment_filter()
 * - comment_any_status_filter()
 * - comment_multiple_status_filter()
 * - comment_total_sum_meta_value_filter()
 * - get_posts_count_only_filter()
 * - add_user_data()
 * - update_user_data()
 * - get_user_data()
 * - delete_user_data()
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
					'comment_content' => !empty($args['data']) ? esc_html( $args['data'] ) : '',
					'comment_type' => esc_attr( $args['type'] ),
					'user_id' => intval( $args['user_id'] ),
					'comment_approved' => !empty($args['status']) ? esc_html( $args['status'] ) : 'log', // 'log' == 'sensei_user_answer'
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
		// Sanity check
		if ( empty($args['user_id']) ) {
			_deprecated_argument( __FUNCTION__, '1.0', __('At no point should user_id be equal to 0.', 'woothemes-sensei') );
			return false;
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
		} elseif ( isset( $args['action'] ) && 'update' == $args['action'] ) {
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
	 * @return mixed | int
	 */
	public static function sensei_check_for_activity ( $args = array(), $return_comments = false ) {

		global $woothemes_sensei, $wp_version;
		if ( !$return_comments ) {
			$args['count'] = true;
		}

		// Are we only retrieving a single entry, or not care about the order...
		if ( isset( $args['count'] ) || isset( $args['post_id'] ) ){

			// ...then we don't need to ask the db to order the results, this overrides WP default behaviour
			if ( version_compare( $wp_version, '4.1', '>=' ) ) {
				$args['order'] = false;
				$args['orderby'] = false;
			}
		}

		// A user ID of 0 is in valid, so shortcut this
		if ( isset( $args['user_id'] ) && 0 == intval ( $args['user_id'] ) ) {
			_deprecated_argument( __FUNCTION__, '1.0', __('At no point should user_id be equal to 0.', 'woothemes-sensei') );
			return false;
		}
		// Check for legacy code
		if ( isset($args['type']) && in_array($args['type'], array('sensei_course_start', 'sensei_course_end', 'sensei_lesson_start', 'sensei_lesson_end', 'sensei_quiz_asked', 'sensei_user_grade', 'sensei_quiz_grade', 'sense_answer_notes') ) ) {
			_deprecated_argument( __FUNCTION__, '1.7', sprintf( __('Sensei activity type %s is no longer used.', 'woothemes-sensei'), $args['type'] ) );
			return false;
		}
		// Are we checking for specific comment_approved statuses?
		if ( isset($args['status']) ) {
			// Temporarily store as a custom status if requesting an array...
			if ( is_array( $args['status'] ) && version_compare($wp_version, '4.1', '<') ) {
				// Encode now, decode later
				$args['status'] = implode( ",", $args['status'] );
				// ...use a filter to switch the encoding back
				add_filter( 'comments_clauses', array( __CLASS__, 'comment_multiple_status_filter' ) );
			}
		}
		else {
			$args['status'] = 'any'; // 'log' == 'sensei_user_answer'
		}

		// Take into account WP < 4.1 will automatically add ' comment_approved = 1 OR comment_approved = 0 '
		if ( ( is_array( $args['status'] ) || 'any' == $args['status'] ) && version_compare($wp_version, '4.1', '<') ) {
			add_filter( 'comments_clauses', array( __CLASS__, 'comment_any_status_filter' ) );
		}

        //Get the comments
        /**
         * This filter runs inside Sensei_Utils::sensei_check_for_activity
         *
         * It runs while getting the comments for the given request.
         *
         * @param int|array $comments
         */
        $comments = apply_filters('sensei_check_for_activity', get_comments( $args ) );

		remove_filter( 'comments_clauses', array( __CLASS__, 'comment_multiple_status_filter' ) );
		remove_filter( 'comments_clauses', array( __CLASS__, 'comment_any_status_filter' ) );
		// Return comments
		if ( $return_comments ) {
			// Could check for array of 1 and just return the 1 item?
			if ( is_array($comments) && 1 == count($comments) ) {
				$comments = array_shift($comments);
			}

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

		$comments = WooThemes_Sensei_Utils::sensei_check_for_activity( $args, true );
		// Need to always use an array, even with only 1 item
		if ( !is_array($comments) ) {
			$comments = array( $comments );
		}

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

		// If activity exists remove activity from log
		$comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => intval( $args['post_id'] ), 'user_id' => intval( $args['user_id'] ), 'type' => esc_attr( $args['type'] ) ), true );
		if( $comments ) {
			// Need to always return an array, even with only 1 item
			if ( !is_array( $comments ) ) {
				$comments = array( $comments );
			}
			foreach ( $comments as $key => $value  ) {
				if ( isset( $value->comment_ID ) && 0 < $value->comment_ID ) {
					$dataset_changes = wp_delete_comment( intval( $value->comment_ID ), true );
				} // End If Statement
			} // End For Loop
			// Manually flush the cache
			wp_cache_flush();
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

				// Need to always return an array, even with only 1 item
				if ( ! is_array( $activities ) ) {
					$activities = array( $activities );
				}

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

		$activity_value = false;
		if ( !empty($args['field']) ) {
			$comment = WooThemes_Sensei_Utils::sensei_check_for_activity( $args, true );

			if ( isset( $comment->{$args['field']} ) && '' != $comment->{$args['field']} ) {
				$activity_value = $comment->{$args['field']};
			} // End If Statement
		}
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
		    'post_status' =>  array( 'wc-processing', 'wc-completed' ),
		) );

		foreach ( $orders as $order_id ) {
			$order = new WC_Order( $order_id->ID );
			if ( $order->post_status == 'wc-completed' ) {
				if ( 0 < sizeof( $order->get_items() ) ) {
					foreach( $order->get_items() as $item ) {

						// Allow product ID to be filtered
						$product_id = apply_filters( 'sensei_bought_product_id', $product_id, $order );

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
			$user_id = get_current_user_id();
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
							'data' => base64_encode( $answer ),
							'type' => 'sensei_user_answer', /* FIELD SIZE 20 */
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
									'data' => base64_encode( $attachment_id ),
									'type' => 'sensei_user_answer', /* FIELD SIZE 20 */
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

        /**
         * Filter the data array for the Sensei wp_handle_upload function call
         *
         * This filter was mainly added for Unit Testing purposes.
         *
         * @since 1.7.4
         *
         * @param array  $file_upload_args {
         *      array of current values
         *
         *     @type string test_form set to false by default
         * }
         */
        $file_upload_args = apply_filters( 'sensei_file_upload_args', array('test_form' => false ) );

        $file_return = wp_handle_upload( $file, $file_upload_args );

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
     *
     * This function grades each question automatically if the are auto gradable.
     * It store all question grades.
     *
     * @deprecated since 1.7.4 use WooThemes_Sensei_Grading::grade_quiz_auto instead
     *
	 * @param  integer $quiz_id         ID of quiz
	 * @param  array $submitted questions id ans answers {
     *          @type int $question_id
     *          @type mixed $answer
     * }
	 * @param  integer $total_questions Total questions in quiz (not used)
     * @param string $quiz_grade_type Optional defaults to auto
     *
	 * @return int $quiz_grade total sum of all question grades
	 */
	public static function sensei_grade_quiz_auto( $quiz_id = 0, $submitted = array(), $total_questions = 0, $quiz_grade_type = 'auto' ) {

        return WooThemes_Sensei_Grading::grade_quiz_auto( $quiz_id, $submitted, $total_questions, $quiz_grade_type );

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
			$user_id = get_current_user_id();
		}

		$activity_logged = false;
		if( intval( $quiz_id ) > 0 && intval( $user_id ) > 0 ) {
			$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
			$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
			$activity_logged = update_comment_meta( $user_lesson_status->comment_ID, 'grade', $grade );

			$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );

			do_action( 'sensei_user_quiz_grade', $user_id, $quiz_id, $grade, $quiz_passmark, $quiz_grade_type );
		}

		return $activity_logged;
	}

	/**
	 * Grade question automatically
     *
     * This function checks the question typ and then grades it accordingly.
     *
     * @deprecated since 1.7.4 use WooThemes_Sensei_Grading::grade_question_auto instead
     *
	 * @param integer $question_id
     * @param string $question_type of the standard Sensei question types
	 * @param string $answer
     * @param int $user_id
     *
	 * @return int $question_grade
	 */
	public static function sensei_grade_question_auto( $question_id = 0, $question_type = '', $answer = '', $user_id = 0 ) {

       return  WooThemes_Sensei_Grading::grade_question_auto( $question_id, $question_type, $answer, $user_id  );

	} // end sensei_grade_question_auto

	/**
	 * Grade question
	 * @param  integer $question_id ID of question
	 * @param  integer $grade       Grade received
	 * @return boolean
	 */
	public static function sensei_grade_question( $question_id = 0, $grade = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$activity_logged = false;
		if( intval( $question_id ) > 0 && intval( $user_id ) > 0 ) {

			$user_answer_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question_id, 'user_id' => $user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_ID' ) );
			$activity_logged = update_comment_meta( $user_answer_id, 'user_grade', $grade );

			$answer_notes = get_post_meta( $question_id, '_answer_feedback', true );
			if ( !empty($answer_notes) ) {
				update_comment_meta( $user_answer_id, 'answer_note', base64_encode( $answer_notes ) );
			}

		}

		return $activity_logged;
	}

	public static function sensei_delete_question_grade( $question_id = 0, $user_id = 0 ) {
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$activity_logged = false;
		if( intval( $question_id ) > 0 ) {
			$user_answer_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question_id, 'user_id' => $user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_ID' ) );
			$activity_logged = delete_comment_meta( $user_answer_id, 'user_grade' );
		}

		return $activity_logged;
	}


    /**
     * Alias to Woothemes_Sensei_Utils::sensei_start_lesson
     *
     * @since 1.7.4
     *
     * @param integer $user_id
     * @param integer $lesson_id
     * @param bool $complete
     *
     * @return mixed boolean or comment_ID
     */
    public static function user_start_lesson(  $user_id = 0, $lesson_id = 0, $complete = false ) {

        return self::sensei_start_lesson( $lesson_id, $user_id, $complete );

    }// end user_start_lesson()

	/**
	 * Marked lesson as started for user
	 * @param  integer $lesson_id ID of lesson
	 * @return mixed boolean or comment_ID
	 */
	public static function sensei_start_lesson( $lesson_id = 0, $user_id = 0, $complete = false ) {
		global $woothemes_sensei;

		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$activity_logged = false;

		if( intval( $lesson_id ) > 0 ) {

			$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			if( $course_id ) {
				$is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $course_id, $user_id );
				if( ! $is_user_taking_course ) {
					WooThemes_Sensei_Utils::user_start_course( $user_id, $course_id );
				}
			}

			$metadata = array();
			$status = 'in-progress';

			// Note: When this action runs the lesson status may not yet exist
			do_action( 'sensei_user_lesson_start', $user_id, $lesson_id );

			if( $complete ) {

				$has_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );
				if ( $has_questions ) {
					$status = 'passed'; // Force a pass
					$metadata['grade'] = 100;
				}
				else {
					$status = 'complete';
				}
			}

			// Check if user is already taking the lesson
			$activity_logged = WooThemes_Sensei_Utils::user_started_lesson( $lesson_id, $user_id );
			if( ! $activity_logged ) {
				$metadata['start'] = current_time('mysql');
				$activity_logged = WooThemes_Sensei_Utils::update_lesson_status( $user_id, $lesson_id, $status, $metadata );
			}

			if ( $complete ) {
				// Run this *after* the lesson status has been created/updated
				do_action( 'sensei_user_lesson_end', $user_id, $lesson_id );
			}

		}

		return $activity_logged;
	}

	/**
	 * Remove user from lesson, deleting all data from the corresponding quiz
	 *
	 * @param type $lesson_id
	 * @param type $user_id
	 * @return boolean
	 */
	public static function sensei_remove_user_from_lesson( $lesson_id = 0, $user_id = 0, $from_course = false ) {
		global $woothemes_sensei;

		if( ! $lesson_id ) return false;

		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		// Process quiz
		$lesson_quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );

		// Delete quiz answers, this auto deletes the corresponding meta data, such as the question/answer grade
		WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $lesson_quiz_id, $user_id );

		// Delete lesson status
		$args = array(
			'post_id' => $lesson_id,
			'type' => 'sensei_lesson_status',
			'user_id' => $user_id,
		);
		// This auto deletes the corresponding meta data, such as the quiz grade, and questions asked
		WooThemes_Sensei_Utils::sensei_delete_activities( $args );

		if( ! $from_course ) {
			do_action( 'sensei_user_lesson_reset', $user_id, $lesson_id );
		}

		return true;
	}

	/**
	 * Remove a user from a course, deleting all activities across all lessons
	 *
	 * @param type $course_id
	 * @param type $user_id
	 * @return boolean
	 */
	public static function sensei_remove_user_from_course( $course_id = 0, $user_id = 0 ) {
		global $woothemes_sensei;

		if( ! $course_id ) return false;

		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$lesson_ids = $woothemes_sensei->post_types->course->course_lessons( $course_id, 'any', 'ids' );

		foreach( $lesson_ids as $lesson_id ) {
			WooThemes_Sensei_Utils::sensei_remove_user_from_lesson( $lesson_id, $user_id, true );
		}

		// Delete course status
		$args = array(
			'post_id' => $course_id,
			'type' => 'sensei_course_status',
			'user_id' => $user_id,
		);

		WooThemes_Sensei_Utils::sensei_delete_activities( $args );

		do_action( 'sensei_user_course_reset', $user_id, $course_id );

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

	/**
	 * Returns the user_grade for a specific question and user, or sensei_user_answer entry
	 *
	 * @param mixed $question
	 * @param int $user_id
	 * @return string
	 */
	public static function sensei_get_user_question_grade( $question = 0, $user_id = 0 ) {
		$question_grade = false;
		if( $question ) {
			if ( is_object( $question ) ) {
				$user_answer_id = $question->comment_ID;
			}
			else {
				if( intval( $user_id ) == 0 ) {
					$user_id = get_current_user_id();
				}
				$user_answer_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => intval($question), 'user_id' => $user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_ID' ) );
			}
			if ( $user_answer_id ) {
				$question_grade = get_comment_meta( $user_answer_id, 'user_grade', true );
			}
		}

		return $question_grade;
	}

	/**
	 * Returns the answer_notes for a specific question and user, or sensei_user_answer entry
	 *
     * @deprecated since 1.7.5 use $woothemes_sensei->quiz->get_user_question_feedback instead
	 * @param mixed $question
	 * @param int $user_id
	 * @return string
	 */
	public static function sensei_get_user_question_answer_notes( $question = 0, $user_id = 0 ) {
		$answer_notes = false;
		if( $question ) {
			if ( is_object( $question ) ) {
				$user_answer_id = $question->comment_ID;
			}
			else {
				if( intval( $user_id ) == 0 ) {
					$user_id = get_current_user_id();
				}
				$user_answer_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => intval($question), 'user_id' => $user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_ID' ) );
			}
			if ( $user_answer_id ) {
				$answer_notes = base64_decode( get_comment_meta( $user_answer_id, 'answer_note', true ) );
			}
		}

		return $answer_notes;
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

		$delete_grade = false;
		if( intval( $quiz_id ) > 0 ) {
			$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
			$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
			$delete_grade = delete_comment_meta( $user_lesson_status->comment_ID, 'grade' );
		}

		return $delete_grade;
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

			// Don't store empty values, no point
			if ( !empty($notes) ) {
				$user_lesson_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question_id, 'user_id' => $user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_ID' ) );
				$activity_logged = update_comment_meta( $user_lesson_id, 'answer_note', $notes );
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

				// Check for a pass being required
				$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
				if ( $pass_required ) {
					// Get quiz passmark
					$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );

					// Add up total passmark
					$total_passmark += $quiz_passmark;

					++$lesson_count;
				}
			}
			// Might be a case of no required lessons
			if ( $lesson_count ) {
				$course_passmark = ( $total_passmark / $lesson_count );
			}
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

				// Check for lesson having questions, thus a quiz, thus having a grade
				$has_questions = get_post_meta( $lesson->ID, '_quiz_has_questions', true );
				if ( $has_questions ) {
					$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson->ID, $user_id );
					// Get user quiz grade
					$quiz_grade = get_comment_meta( $user_lesson_status->comment_ID, 'grade', true );

					// Add up total grade
					$total_grade += $quiz_grade;

					++$lesson_count;
				}
			}

			// Might be a case of no lessons with quizzes
			if ( $lesson_count ) {
				$total_grade = ( $total_grade / $lesson_count );
			}

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
			$user_id = get_current_user_id();
		}

		$status = 'not_started';
		$box_class = 'info';
		$message = __( 'You have not started this course yet.', 'woothemes-sensei' );

		if( $course_id > 0 && $user_id > 0 ) {

			$started_course = WooThemes_Sensei_Utils::user_started_course( $course_id, $user_id );

			if( $started_course ) {
				$passmark = WooThemes_Sensei_Utils::sensei_course_pass_grade( $course_id ); // This happens inside sensei_user_passed_course()!
				$user_grade = WooThemes_Sensei_Utils::sensei_course_user_grade( $course_id, $user_id ); // This happens inside sensei_user_passed_course()!
				if( $user_grade >= $passmark ) {
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
			$started_course = WooThemes_Sensei_Utils::user_started_course( $course_id, $user_id );

			// Has user completed lesson
			$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
			$lesson_complete = WooThemes_Sensei_Utils::user_completed_lesson( $user_lesson_status );

			// Quiz ID
			$quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );

			// Quiz grade
			$quiz_grade = false;
			if ( $user_lesson_status ) {
				$quiz_grade = get_comment_meta( $user_lesson_status->comment_ID, 'grade', true );
			}

			// Quiz passmark
			$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );
			$quiz_passmark_float = (float) $quiz_passmark;

			// Pass required
			$pass_required = get_post_meta( $quiz_id, '_pass_required', true );

			// Quiz questions
			$has_quiz_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );

			if ( ! $started_course ) {

				$status = 'not_started_course';
				$box_class = 'info';
				$message = sprintf( __( 'Please sign up for %1$sthe course%2$s before taking this quiz', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( __( 'Sign Up', 'woothemes-sensei' ) ) . '">', '</a>' );

			} elseif ( ! is_user_logged_in() ) {

				$status = 'login_required';
				$box_class = 'info';
				$message = __( 'You must be logged in to take this quiz', 'woothemes-sensei' );

			}
			// Lesson/Quiz is marked as complete thus passing any quiz restrictions
			elseif ( $lesson_complete ) {

				$status = 'passed';
				$box_class = 'tick';
				// Lesson status will be "complete" (has no Quiz)
				if ( ! $has_quiz_questions ) {
					$message = sprintf( __( 'Congratulations! You have passed this lesson.', 'woothemes-sensei' ) );
				}
				// Lesson status will be "graded" (no passmark required so might have failed all the questions)
				elseif ( empty( $quiz_grade ) ) {
					$message = sprintf( __( 'Congratulations! You have completed this lesson.', 'woothemes-sensei' ) );
				}
				// Lesson status will be "passed" (passmark reached)
				elseif ( ! empty( $quiz_grade ) && abs( $quiz_grade ) >= 0 ) {
					if( $is_lesson ) {
						$message = sprintf( __( 'Congratulations! You have passed this lesson\'s quiz achieving %d%%', 'woothemes-sensei' ), round( $quiz_grade ) );
					} else {
						$message = sprintf( __( 'Congratulations! You have passed this quiz achieving %d%%', 'woothemes-sensei' ), round( $quiz_grade ) );
					}
				}

			}
			// Lesson/Quiz not complete
			else {
				// Lesson/Quiz isn't "complete" instead it's ungraded (previously this "state" meant that it *was* complete)
				if ( isset( $user_lesson_status->comment_approved ) && 'ungraded' == $user_lesson_status->comment_approved ) {
					$status = 'complete';
					$box_class = 'info';
					if( $is_lesson ) {
						$message = sprintf( __( 'You have completed this lesson\'s quiz and it will be graded soon. %1$sView the lesson quiz%2$s', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $quiz_id ) ) . '" title="' . esc_attr( get_the_title( $quiz_id ) ) . '">', '</a>' );
					} else {
						$message = sprintf( __( 'You have completed this quiz and it will be graded soon. You require %1$d%% to pass.', 'woothemes-sensei' ), round( $quiz_passmark ) );
					}
				}
				// Lesson status must be "failed"
				elseif ( isset( $user_lesson_status->comment_approved ) && 'failed' == $user_lesson_status->comment_approved ) {
					$status = 'failed';
					$box_class = 'alert';
					if( $is_lesson ) {
						$message = sprintf( __( 'You require %1$d%% to pass this lesson\'s quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $quiz_grade ) );
					} else {
						$message = sprintf( __( 'You require %1$d%% to pass this quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $quiz_grade ) );
					}
				}
				// Lesson/Quiz requires a pass
				elseif( $pass_required ) {
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

		// Legacy filter
		$message = apply_filters( 'sensei_user_quiz_status_' . $status, $message );

		if( $is_lesson && ! in_array( $status, array( 'login_required', 'not_started_course' ) ) ) {
			$extra = '<p><a class="button" href="' . esc_url( get_permalink( $quiz_id ) ) . '" title="' . esc_attr( apply_filters( 'sensei_view_lesson_quiz_text', __( 'View the lesson quiz', 'woothemes-sensei' ) ) ) . '">' . apply_filters( 'sensei_view_lesson_quiz_text', __( 'View the lesson quiz', 'woothemes-sensei' ) ) . '</a></p>';
		}

		// Filter of all messages
		return apply_filters( 'sensei_user_quiz_status', array( 'status' => $status, 'box_class' => $box_class, 'message' => $message, 'extra' => $extra ), $lesson_id, $user_id, $is_lesson );
	}

	/**
	 * Start course for user
	 * @since  1.4.8
	 * @param  integer $user_id   User ID
	 * @param  integer $course_id Course ID
	 * @return mixed boolean or comment_ID
	 */
	public static function user_start_course( $user_id = 0, $course_id = 0 ) {

		$activity_logged = false;

		if( $user_id && $course_id ) {
			// Check if user is already on the Course
			$activity_logged = WooThemes_Sensei_Utils::user_started_course( $course_id, $user_id );
			if ( ! $activity_logged ) {

				// Add user to course
				$course_metadata = array(
					'start' => current_time('mysql'),
					'percent' => 0, // No completed lessons yet
					'complete' => 0,
				);

				$activity_logged = WooThemes_Sensei_Utils::update_course_status( $user_id, $course_id, $course_status = 'in-progress', $course_metadata );

				// Allow further actions
				if ( $activity_logged ) {
					do_action( 'sensei_user_course_start', $user_id, $course_id );
				}
			}
		}

		return $activity_logged;
	}

	/**
	 * Check if a user has started a course or not
	 *
	 * @since  1.7.0
	 * @param type $course_id
	 * @param type $user_id
	 * @return mixed false or comment_ID
	 */
	public static function user_started_course( $course_id = 0, $user_id = 0 ) {

		if( $course_id ) {
			if( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user_course_status_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_id, 'user_id' => $user_id, 'type' => 'sensei_course_status', 'field' => 'comment_ID' ) );
			if( $user_course_status_id ) {
				return $user_course_status_id;
			}
		}
		return false;
	}

	/**
	 * Checks if a user has completed a course by checking every lesson status
	 *
	 * @since  1.7.0
	 * @param  integer $course_id Course ID
	 * @param  integer $user_id   User ID
	 * @return int
	 */
	public static function user_complete_course( $course_id = 0, $user_id = 0 ) {
		global $woothemes_sensei, $wp_version;

		if( $course_id ) {
			if( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$course_status = 'in-progress';
			$course_metadata = array();
			$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
			$lessons_completed = $total_lessons = 0;
			$lesson_status_args = array(
					'user_id' => $user_id,
					'status' => 'any',
					'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
				);

			// Grab all of this Courses' lessons, looping through each...
			$lesson_ids = $woothemes_sensei->post_types->course->course_lessons( $course_id, 'any', 'ids' );
			$total_lessons = count( $lesson_ids );
				// ...if course completion not set to 'passed', and all lessons are complete or graded,
				// ......then all lessons are 'passed'
				// ...else if course completion is set to 'passed', check if each lesson has questions...
				// ......if no questions yet the status is 'complete'
				// .........then the lesson is 'passed'
				// ......else if questions check the lesson status has a grade and that the grade is greater than the lesson passmark
				// .........then the lesson is 'passed'
				// ...if all lessons 'passed' then update the course status to complete

			// The below checks if a lesson is fully completed, though maybe should be Utils::user_completed_lesson()
			$all_lesson_statuses = array();
			// In WordPress 4.1 get_comments() allows a single query to cover multiple comment_post_IDs
			if ( version_compare($wp_version, '4.1', '>=') ) {
				$lesson_status_args['post__in'] = $lesson_ids;
				$all_lesson_statuses = WooThemes_Sensei_Utils::sensei_check_for_activity( $lesson_status_args, true );
				// Need to always return an array, even with only 1 item
				if ( !is_array($all_lesson_statuses) ) {
					$all_lesson_statuses = array( $all_lesson_statuses );
				}
			}
			// ...otherwise check each one
			else {
				foreach( $lesson_ids as $lesson_id ) {
					$lesson_status_args['post_id'] = $lesson_id;
					$each_lesson_status = WooThemes_Sensei_Utils::sensei_check_for_activity( $lesson_status_args, true );
					// Check for valid return before using
					if ( !empty($each_lesson_status->comment_approved) ) {
						$all_lesson_statuses[] = $each_lesson_status;
					}
				}
			}
			foreach( $all_lesson_statuses as $lesson_status ) {
				// If lessons are complete without needing quizzes to be passed
				if ( 'passed' != $course_completion ) {
					switch ( $lesson_status->comment_approved ) {
						// A user cannot 'complete' a course if a lesson...
						case 'in-progress': // ...is still in progress
						case 'ungraded': // ...hasn't yet been graded
							break;

						default:
							$lessons_completed++;
							break;
					}
				}
				else {
					switch ( $lesson_status->comment_approved ) {
						case 'complete': // Lesson has no quiz/questions
						case 'graded': // Lesson has quiz, but it's not important what the grade was
						case 'passed': // Lesson has quiz and the user passed
							$lessons_completed++;
							break;

						// A user cannot 'complete' a course if on a lesson...
						case 'failed': // ...a user failed the passmark on a quiz
						default:
							break;
					}
				}
			} // Each lesson
			if ( $lessons_completed == $total_lessons ) {
				$course_status = 'complete';
			}

			// Update meta data on how many lessons have been completed
			$course_metadata['complete'] = $lessons_completed;
			// update the overall percentage of the course lessons complete (or graded) compared to 'in-progress' regardless of the above
			$course_metadata['percent'] = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) );

			$activity_logged = WooThemes_Sensei_Utils::update_course_status( $user_id, $course_id, $course_status, $course_metadata );

			// Allow further actions
			if ( 'complete' == $course_status ) {
				do_action( 'sensei_user_course_end', $user_id, $course_id );
			}
			return $activity_logged;
		}

		return false;
	}

	/**
	 * Check if a user has completed a course or not
	 *
	 * @param mixed $course course_id or sensei_course_status entry
	 * @param int $user_id
	 * @return boolean
	 */
	public static function user_completed_course( $course = 0, $user_id = 0 ) {

		if( $course ) {
			if ( is_object( $course ) ) {
				$user_course_status = $course->comment_approved;
			}
			elseif ( is_string( $course ) ) {
				$user_course_status = $course;
			}
			else {
				if( ! $user_id ) {
					$user_id = get_current_user_id();
				}

				$user_course_status = WooThemes_Sensei_Utils::user_course_status( $course, $user_id );
				if( isset( $user_course_status->comment_approved ) ){
                    $user_course_status = $user_course_status->comment_approved;
                }

			}
			if( $user_course_status && 'complete' == $user_course_status ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a user has started a lesson or not
	 *
	 * @since  1.7.0
	 * @param type $lesson_id
	 * @param type $user_id
	 * @return mixed false or comment_ID
	 */
	public static function user_started_lesson( $lesson_id = 0, $user_id = 0 ) {

		if( $lesson_id ) {
			if( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user_lesson_status_id = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_status', 'field' => 'comment_ID' ) );
			if( $user_lesson_status_id ) {
				return $user_lesson_status_id;
			}
		}
		return false;
	}

	/**
	 * Check if a user has completed a lesson or not
	 *
	 * @global type $woothemes_sensei
	 * @param mixed $lesson lesson_id or sensei_lesson_status entry
	 * @param int $user_id
	 * @return boolean
	 */
	public static function user_completed_lesson( $lesson = 0, $user_id = 0 ) {
		global $woothemes_sensei;

		if( $lesson ) {
			$lesson_id = 0;
			if ( is_object( $lesson ) ) {
				$user_lesson_status = $lesson->comment_approved;
				$lesson_id = $lesson->comment_post_ID;
			}
			elseif ( is_string( $lesson ) ) {
				$user_lesson_status = $lesson;
			}
			else {
				if( ! $user_id ) {
					$user_id = get_current_user_id();
				}

				$_user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson, $user_id );

				if ( $_user_lesson_status ) {
					$user_lesson_status = $_user_lesson_status->comment_approved;
				}
				else {
					return false; // No status means not complete
				}
				$lesson_id = $lesson;
			}
			if ( 'in-progress' != $user_lesson_status ) {
				// Check for Passed or Completed Setting
				// Should we be checking for the Course completion setting? Surely that should only affect the Course completion, not bypass each Lesson setting
//				$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
//				if ( 'passed' == $course_completion ) {
					switch( $user_lesson_status ) {
						case 'complete':
						case 'graded':
						case 'passed':
							return true;
							break;

						case 'failed':
							// This may be 'completed' depending on...
							if ( $lesson_id ) {
								// Get Quiz ID, this won't be needed once all Quiz meta fields are stored on the Lesson
								$lesson_quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
								if ( $lesson_quiz_id ) {
									// ...the quiz pass setting
									$pass_required = get_post_meta( $lesson_quiz_id, '_pass_required', true );
									if ( empty($pass_required) ) {
										// We just require the user to have done the quiz, not to have passed
										return true;
									}
								}
							}
							return false;
							break;
					}
			} // End If Statement
		}

		return false;
	}

	/**
	 * Returns the requested course status
	 *
	 * @since 1.7.0
	 * @param type $course_id
	 * @param type $user_id
	 * @return object
	 */
	public static function user_course_status( $course_id = 0, $user_id = 0 ) {
		global $woothemes_sensei;

		if( $course_id ) {
			if( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user_course_status = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_id, 'user_id' => $user_id, 'type' => 'sensei_course_status' ), true );
			return $user_course_status;
		}

		return false;
	}

	/**
	 * Returns the requested lesson status
	 *
	 * @since 1.7.0
	 * @param type $lesson_id
	 * @param type $user_id
	 * @return object
	 */
	public static function user_lesson_status( $lesson_id = 0, $user_id = 0 ) {
		global $woothemes_sensei;

		if( $lesson_id ) {
			if( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user_lesson_status = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_status' ), true );
			return $user_lesson_status;
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
		$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );

		// Quiz Grade
		$lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		$quiz_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );

		// Check if Grade is greater than or equal to pass percentage
		$quiz_passmark = abs( round( doubleval( get_post_meta( $quiz_id, '_quiz_passmark', true ) ), 2 ) );
		if ( $quiz_passmark <= intval( $quiz_grade ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Sets the status for the lesson
	 *
	 * @access public
	 * @since  1.7.0
	 * @param type $user_id
	 * @param type $lesson_id
	 * @param type $status
	 * @param type $metadata
	 * @return mixed false or comment_ID
	 */
	public static function update_lesson_status( $user_id, $lesson_id, $status = 'in-progress', $metadata = array() ) {
		$comment_id = false;
		if ( !empty($status) ) {
			$args = array(
					'user_id'   => $user_id,
					'post_id'   => $lesson_id,
					'status'    => $status,
					'type'      => 'sensei_lesson_status', /* FIELD SIZE 20 */
					'action'    => 'update', // Update the existing status...
					'keep_time' => true, // ...but don't change the existing timestamp
				);
			if( 'in-progress' == $status ) {
				unset( $args['keep_time'] ); // Keep updating what's happened
			}

			$comment_id = WooThemes_Sensei_Utils::sensei_log_activity( $args );
			if ( $comment_id && !empty($metadata) ) {
				foreach( $metadata as $key => $value ) {
					update_comment_meta( $comment_id, $key, $value );
				}
			}

			do_action( 'sensei_lesson_status_updated', $status, $user_id, $lesson_id, $comment_id );
		}
		return $comment_id;
	}

	/**
	 * Sets the statuses for the Course
	 *
	 * @access public
	 * @since  1.7.0
	 * @param type $user_id
	 * @param type $course_id
	 * @param type $status
	 * @param type $metadata
	 * @return mixed false or comment_ID
	 */
	public static function update_course_status( $user_id, $course_id, $status = 'in-progress', $metadata = array() ) {
		$comment_id = false;
		if ( !empty($status) ) {
			$args = array(
					'user_id'   => $user_id,
					'post_id'   => $course_id,
					'status'    => $status,
					'type'      => 'sensei_course_status', /* FIELD SIZE 20 */
					'action'    => 'update', // Update the existing status...
					'keep_time' => true, // ...but don't change the existing timestamp
				);
			if( 'in-progress' == $status ) {
				unset( $args['keep_time'] ); // Keep updating what's happened
			}

			$comment_id = WooThemes_Sensei_Utils::sensei_log_activity( $args );
			if ( $comment_id && !empty($metadata) ) {
				foreach( $metadata as $key => $value ) {
					update_comment_meta( $comment_id, $key, $value );
				}
			}
			do_action( 'sensei_course_status_updated', $status, $user_id, $course_id, $comment_id );
		}
		return $comment_id;
	}

	/**
	 * Remove the orderby for comments
	 * @access public
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function single_comment_filter( $pieces ) {
		unset( $pieces['orderby'] );
		unset( $pieces['order'] );

		return $pieces;
	}

	/**
	 * Allow retrieving comments with any comment_approved status, little bypass to WP_Comment. Required only for WP < 4.1
	 * @access public
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function comment_any_status_filter( $pieces ) {

		$pieces['where'] = str_replace( array( "( comment_approved = '0' OR comment_approved = '1' ) AND", "comment_approved = 'any' AND" ), '', $pieces['where'] );

		return $pieces;
	}

	/**
	 * Allow retrieving comments within multiple statuses, little bypass to WP_Comment. Required only for WP < 4.1
	 * @access public
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function comment_multiple_status_filter( $pieces ) {

		preg_match( "/^comment_approved = '([a-z\-\,]+)'/", $pieces['where'], $placeholder );
		if ( !empty($placeholder[1]) ) {
			$statuses = explode( ',', $placeholder[1] );
			$pieces['where'] = str_replace( "comment_approved = '" . $placeholder[1] . "'", "comment_approved IN ('". implode( "', '", $statuses ) . "')", $pieces['where'] );
		}

		return $pieces;
	}

	/**
	 * Adjust the comment query to be faster on the database, used by Analysis admin
	 * @since  1.7.0
	 * @return array
	 */
	public static function comment_total_sum_meta_value_filter( $pieces ) {
		global $wpdb, $wp_version;

		$pieces['fields'] = " COUNT(*) AS total, SUM($wpdb->commentmeta.meta_value) AS meta_sum ";
		unset( $pieces['groupby'] );
		if ( version_compare($wp_version, '4.1', '>=') ) {
			$args['order'] = false;
			$args['orderby'] = false;
		}

		return $pieces;
	}

	/**
	 * Shifts counting of posts to the database where it should be. Likely not to be used due to knock on issues.
	 * @access public
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function get_posts_count_only_filter( $pieces ) {
		global $wp_version;

		$pieces['fields'] = " COUNT(*) AS total ";
		unset( $pieces['groupby'] );
		if ( version_compare($wp_version, '4.1', '>=') ) {
			$args['order'] = false;
			$args['orderby'] = false;
		}
		return $pieces;
	}

    /**
     *
     * Alias to Woothemes_Sensei_Utils::update_user_data
     * @since 1.7.4
     *
     * @param string $data_key maximum 39 characters allowed
     * @param int $post_id
     * @param mixed $value
     * @param int $user_id
     *
     * @return bool $success
     */
    public static function add_user_data( $data_key, $post_id , $value = '' , $user_id = 0  ){

        return self::update_user_data( $data_key, $post_id, $value , $user_id );

    }// end add_user_data

    /**
     * add user specific data to the passed in sensei post type id
     *
     * This function saves comment meta on the users current status. If no status is available
     * status will be created. It only operates on the available sensei Post types: course, lesson, quiz.
     *
     * @since 1.7.4
     *
     * @param string $data_key maximum 39 characters allowed
     * @param int $post_id
     * @param mixed $value
     * @param int $user_id
     *
     * @return bool $success
     */
    public static function update_user_data( $data_key, $post_id, $value = '' , $user_id = 0  ){

        if( ! ( $user_id > 0 ) ){
            $user_id = get_current_user_id();
        }

        $supported_post_types = array( 'course', 'lesson' );
        $post_type = get_post_type( $post_id );
        if( empty( $post_id ) || empty( $data_key )
            || ! is_int( $post_id ) || ! ( intval( $post_id ) > 0 ) || ! ( intval( $user_id ) > 0 )
            || !get_userdata( $user_id )
            || ! in_array( $post_type, $supported_post_types )  ){

            return false;
        }

        // check if there and existing Sensei status on this post type if not create it
        // and get the  activity ID
        $status_function = 'user_'.$post_type.'_status';
        $sensei_user_status = self::$status_function( $post_id ,$user_id  );
        if( ! isset( $sensei_user_status->comment_ID ) ){

            $start_function = 'user_start_'.$post_type;
            $sensei_user_activity_id = self::$start_function( $user_id, $post_id );

        }else{

            $sensei_user_activity_id = $sensei_user_status->comment_ID;

        }

        // store the data
        $success = update_comment_meta( $sensei_user_activity_id, $data_key, $value );

       return $success;

    }//update_user_data

    /**
     * Get the user data stored on the passed in post type
     *
     * This function gets the comment meta on the lesson or course status
     *
     * @since 1.7.4
     *
     * @param $data_key
     * @param $post_id
     * @param int $user_id
     *
     * @return mixed $user_data_value
     */
    public static function get_user_data( $data_key, $post_id, $user_id = 0  ){

        $user_data_value = true;

        if( ! ( $user_id > 0 ) ){
            $user_id = get_current_user_id();
        }

        $supported_post_types = array( 'course', 'lesson' );
        $post_type = get_post_type( $post_id );
        if( empty( $post_id ) || empty( $data_key )
            || ! ( intval( $post_id ) > 0 ) || ! ( intval( $user_id ) > 0 )
            || ! get_userdata( $user_id )
            || !in_array( $post_type, $supported_post_types )  ){

            return false;
        }

        // check if there and existing Sensei status on this post type if not create it
        // and get the  activity ID
        $status_function = 'user_'.$post_type.'_status';
        $sensei_user_status = self::$status_function( $post_id ,$user_id  );
        if( ! isset( $sensei_user_status->comment_ID ) ){
            return false;
        }

        $sensei_user_activity_id = $sensei_user_status->comment_ID;
        $user_data_value = get_comment_meta( $sensei_user_activity_id , $data_key, true );

        return $user_data_value;

    }// end get_user_data

    /**
     * Delete the Sensei user data for the given key, Sensei post type and user combination.
     *
     * @param int $data_key
     * @param int $post_id
     * @param int $user_id
     *
     * @return bool $deleted
     */
    public static function delete_user_data( $data_key, $post_id , $user_id ){
        $deleted = true;

        if( ! ( $user_id > 0 ) ){
            $user_id = get_current_user_id();
        }

        $supported_post_types = array( 'course', 'lesson' );
        $post_type = get_post_type( $post_id );
        if( empty( $post_id ) || empty( $data_key )
            || ! is_int( $post_id ) || ! ( intval( $post_id ) > 0 ) || ! ( intval( $user_id ) > 0 )
            || ! get_userdata( $user_id )
            || !in_array( $post_type, $supported_post_types )  ){

            return false;
        }

        // check if there and existing Sensei status on this post type if not create it
        // and get the  activity ID
        $status_function = 'user_'.$post_type.'_status';
        $sensei_user_status = self::$status_function( $post_id ,$user_id  );
        if( ! isset( $sensei_user_status->comment_ID ) ){
            return false;
        }

        $sensei_user_activity_id = $sensei_user_status->comment_ID;
        $deleted = delete_comment_meta( $sensei_user_activity_id , $data_key );

        return $deleted;

    }// end delete_user_data


    /**
     * The function creates a drop down. Never write up a Sensei select statement again.
     *
     * @since 1.8.0
     *
     * @param string $selected_value
     * @param $options{
     *    @type string $value the value saved in the database
     *    @type string $option what the user will see in the list of items
     * }
     * @param array $attributes{
     *   @type string $attribute  type such name or id etc.
     *  @type string $value
     * }
     * @param bool $enable_none_option
     *
     * @return string $drop_down_element
     */
    public static function generate_drop_down( $selected_value, $options = array() , $attributes = array(), $enable_none_option = true ) {

        $drop_down_element = '';

        // setup the basic attributes
        if( !isset( $attributes['name'] ) || empty( $attributes['name']  ) ) {

            $attributes['name'] = 'sensei-options';

        }

        if( !isset( $attributes['id'] ) || empty( $attributes['id']  ) ) {

            $attributes['id'] = 'sensei-options';

        }

        if( !isset( $attributes['class'] ) || empty( $attributes['class']  ) ) {

            $attributes['class'] ='chosen_select widefat';

        }

        // create element attributes
        $combined_attributes = '';
        foreach( $attributes as $attribute => $value ){

            $combined_attributes .= $attribute . '="'.$value.'"' . ' ';

        }// end for each


        // create the select element
        $drop_down_element .= '<select '. $combined_attributes . ' >' . "\n";

        // show the none option if the client requested
        if( $enable_none_option ) {
            $drop_down_element .= '<option value="">' . __('None', 'woothemes-sensei') . '</option>';
        }

        if ( count( $options ) > 0 ) {

            foreach ($options as $value => $option ){

                $element = '';
                $element.= '<option value="' . esc_attr( $value ) . '"';
                $element .= selected( $value, $selected_value, false ) . '>';
                $element .= esc_html(  $option ) . '</option>' . "\n";

                // add the element to the select html
                $drop_down_element.= $element;
            } // End For Loop

        } // End If Statement

        $drop_down_element .= '</select>' . "\n";

        return $drop_down_element;

    }// generate_drop_down



} // End Class
