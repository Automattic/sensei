<?php
/**
 * The Template for displaying all single course meta information.
 *
 * Override this template by copying it to yourtheme/sensei/single-course/course-meta.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

global $post, $current_user, $woocommerce;
	 	
// Get User Meta
get_currentuserinfo();
// Check if the user is taking the course
$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
// Handle user starting the course
if ( isset( $_POST['course_start'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_start_course_noonce' ], 'woothemes_sensei_start_course_noonce' ) && !$is_user_taking_course ) {
    // Start the course
	$args = array(
					    'post_id' => $post->ID,
					    'username' => $current_user->user_login,
					    'user_email' => $current_user->user_email,
					    'user_url' => $current_user->user_url,
					    'data' => 'Course started by the user',
					    'type' => 'sensei_course_start', /* FIELD SIZE 20 */
					    'parent' => 0,
					    'user_id' => $current_user->ID
					);
	$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
	$is_user_taking_course = false;
	if ( $activity_logged ) {
		$is_user_taking_course = true;
	} // End If Statement	
} // End If Statement
    
// Get the meta info
$course_video_embed = get_post_meta( $post->ID, '_course_video_embed', true );
if ( 'http' == substr( $course_video_embed, 0, 4) ) {
    // V2 - make width and height a setting for video embed
    $course_video_embed = wp_oembed_get( esc_url( $course_video_embed )/*, array( 'width' => 100 , 'height' => 100)*/ );
} // End If Statement

$wc_post_id = get_post_meta( $post->ID, '_course_woocommerce_product', true );

if ( sensei_check_if_product_is_in_cart( $wc_post_id ) ) {
	echo '<div class="woo-sc-box info">' . sprintf(  __('You have already added this Course to your cart. Please %1$s to access the course.', 'woothemes-sensei') . '</div>', '<a class="cart-contents" href="' . $woocommerce->cart->get_checkout_url() . '" title="' . __('complete the purchase', 'woothemes-sensei') . '">' . __('complete the purchase', 'woothemes-sensei') . '</a>' );	
}
?>


<section class="course-meta">
    
    <?php if ( is_user_logged_in() && ! $is_user_taking_course ) {
    	// Get the product ID
    	$wc_post_id = get_post_meta( $post->ID, '_course_woocommerce_product', true );
    	// Check for woocommerce	
    	if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) { 
    		sensei_wc_add_to_cart($post->ID); 
    	} else { 
    		sensei_start_course_form($post->ID);
    	} // End If Statement
    } elseif ( is_user_logged_in() ) {
    	// Check if course is completed
    	$user_course_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end', 'field' => 'comment_content' ) );
		$completed_course = false;
		if ( '' != $user_course_end ) {
			$completed_course = true;
		} else {
			// Do the check if all lessons complete
			$course_lessons = $woothemes_sensei->frontend->course->course_lessons( $post->ID );
    		$lessons_completed = 0;
    		foreach ($course_lessons as $lesson_item){
    			// Check if Lesson is complete
    			$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
				if ( '' != $user_lesson_end ) {
					$lessons_completed++;
				} // End If Statement
			} // End For Loop
    		if ( absint( $lessons_completed ) == absint( count( $course_lessons ) ) && ( 0 < absint( count( $course_lessons ) ) ) && ( 0 < absint( $lessons_completed ) ) ) {
    			// Mark course as complete
    			$args = array(
								    'post_id' => $post->ID,
								    'username' => $current_user->user_login,
								    'user_email' => $current_user->user_email,
								    'user_url' => $current_user->user_url,
								    'data' => 'Course completed by the user',
								    'type' => 'sensei_course_end', /* FIELD SIZE 20 */
								    'parent' => 0,
								    'user_id' => $current_user->ID,
								    'action' => 'update'
								);
    			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
				$dataset_changes = true;
				if ( $activity_logged ) {
					// Course is complete
					$completed_course = true;
				} // End If Statement
    		} // End If Statement
		} // End If Statement
		// Success message
   		if ( $completed_course ) { ?>
   			<div class="status completed"><?php _e( 'Completed', 'woothemes-sensei' ); ?></div>
   		<?php } else { ?>
    		<div class="status in-progress"><?php _e( 'In Progress', 'woothemes-sensei' ); ?></div>
    	<?php } ?>
    <?php } else {
    	// Get the product ID
    	$wc_post_id = get_post_meta( $post->ID, '_course_woocommerce_product', true );
    	// Check for woocommerce	
    	if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) { 
    		sensei_wc_add_to_cart($post->ID); 
    	} else {
    		// User needs to register
    		wp_register( '<div class="status register">', '</div>' );
    	} // End If Statement
    } // End If Statement ?>
    
</section>

<div class="course-video"><?php echo html_entity_decode($course_video_embed); ?></div>