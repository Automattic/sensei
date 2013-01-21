<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Analysis Class
 *
 * All functionality pertaining to the Admin Analysis in Sensei.
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
 * - analysis_admin_menu()
 * - analysis_page()
 * - enqueue_scripts()
 * - enqueue_styles()
 */
class WooThemes_Sensei_Analysis {
	public $token;
	public $name;
	
	/**
	 * Constructor
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct () {
	
		$this->name = 'Analysis';
		// Admin functions
		if ( is_admin() ) {
			add_action('admin_menu', array( &$this, 'analysis_admin_menu' ), 10);
			add_action( 'admin_print_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'enqueue_styles' ) );
		} // End If Statement

	} // End __construct()
	
	
	/**
	 * analysis_admin_menu function.
	 * 
	 * @access public
	 * @return void
	 */
	public function analysis_admin_menu() {
	    global $menu, $woocommerce;
	
	    if ( current_user_can( 'manage_options' ) )
	    
	    $analysis_page = add_submenu_page('edit.php?post_type=lesson', __('Analysis', 'woothemes-sensei'),  __('Analysis', 'woothemes-sensei') , 'manage_options', 'sensei_analysis', array( &$this, 'analysis_page' ) );
	
	} // End analysis_admin_menu()

	
	/**
	 * analysis_page function.
	 * 
	 * @access public
	 * @return void
	 */
	public function analysis_page() {
		
		global $woothemes_sensei;
		// Get the data required
		$users = get_users();
		$total_courses = $woothemes_sensei->post_types->course->course_count();
		$total_lessons = $woothemes_sensei->post_types->lesson->lesson_count();
		$total_quiz_grades = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'type' => 'sensei_quiz_grade' ), true );
		$total_grade_count = 0;
		$total_grade_total = 0.00;
		// Calculate the average quiz grade
		foreach ( $total_quiz_grades as $total_quiz_key => $total_quiz_value ) {
		    $total_grade_total = $total_grade_total + doubleval( $total_quiz_value->comment_content );
		    $total_grade_count++;
		} // End For Loop
		// Handle Division by Zero
		if ( 0 == $total_grade_count ) {
			$total_grade_count = 1;
		} // End If Statement
		$total_average_grade = abs( round( doubleval( $total_grade_total / $total_grade_count ), 2 ) );
		$total_courses_started = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'type' => 'sensei_course_start' ), true );
		$total_courses_ended = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'type' => 'sensei_course_end' ), true );
		?>
		<?php do_action( 'analysis_before_container' ); ?>
		<div id="woothemes-sensei" class="wrap <?php echo esc_attr( $this->token ); ?>">
			<?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<ul class="subsubsub">
				<li><a href="admin.php?page=sensei_analysis" class="current">Overview</a></li>
			</ul>
			<br class="clear">
			<div id="poststuff" class="sensei-analysis-wrap">
				<div class="sensei-analysis-sidebar">
					<div class="postbox">
						<h3><span>Total Courses</span></h3>
						<div class="inside">
							<p class="stat"><?php echo $total_courses; ?></p>
						</div>
					</div>
					<div class="postbox">
						<h3><span>Total Lessons</span></h3>
						<div class="inside">
							<p class="stat"><?php echo $total_lessons; ?></p>
						</div>
					</div>
					<div class="postbox">
						<h3><span>Total Learners</span></h3>
						<div class="inside">
							<p class="stat"><?php echo count( $users ); ?></p>
						</div>
					</div>
					<div class="postbox">
						<h3><span>Average Courses per Learner</span></h3>
						<div class="inside">
							<p class="stat"><?php echo abs( round( doubleval( count( $total_courses_started ) / count( $users ) ), 2 ) ); ?></p>
						</div>
					</div>
					<div class="postbox">
						<h3><span>Average Grade</span></h3>
						<div class="inside">
							<p class="stat"><?php echo $total_average_grade; ?>%</p>
						</div>
					</div>
					<div class="postbox">
						<h3><span>Total Completed Courses</span></h3>
						<div class="inside">
							<p class="stat"><?php echo count( $total_courses_ended ); ?></p>
						</div>
					</div>
				</div>
				<div class="sensei-analysis-main">
					<table class="widefat">
						<thead>
							<tr>
								<th><?php _e('User', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Date Registered', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Active Courses', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Completed Courses', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Average Grade', 'woothemes-sensei'); ?></th>
							</tr>
						</thead>
						<tfoot>
								<?php
									$user_offset = 0;
									if ( isset( $_GET['user_offset'] ) && 0 <= abs( intval( $_GET['user_offset'] ) ) ) {
										$user_offset = abs( intval( $_GET['user_offset'] ) );
									} // End If Statement
									$user_length = 15;
									$output_counter = 0;
									foreach ( array_slice($users, $user_offset, $user_length, true) as $user_key => $user_item ) {
										$output_counter++;
										$user_courses_started = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_start' ), true );
										$user_courses_ended = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_end' ), true );
										$user_quiz_grades = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_quiz_grade' ), true );
										// Calculate the average grade for the user
										$grade_count = 0;
										$grade_total = 0.00;
										foreach ( $user_quiz_grades as $quiz_key => $quiz_value ) {
											$grade_total = $grade_total + doubleval( $quiz_value->comment_content );
											$grade_count++;
										} // End For Loop
										// Handle Division by Zero
										if ( 0 == $grade_count ) {
											$grade_count = 1;
										} // End If Statement
										$user_average_grade = abs( round( doubleval( $grade_total / $grade_count ), 2 ) );
										// Output the users data
										echo '<tr>
											<td>' . $user_item->user_login . '</td>
											<td class="total_row">' . $user_item->user_registered . '</td>
											<td class="total_row">' . ( count( $user_courses_started ) - count( $user_courses_ended ) ) . '</td>
											<td class="total_row">' . count( $user_courses_ended ) . '</td>
											<td class="total_row">' . $user_average_grade . '%</td>
											</tr>';					
									} // End For Loop
								?>
							<tr>
								<th><?php _e('User', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Date Registered', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Active Courses', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Completed Courses', 'woothemes-sensei'); ?></th>
								<th class="total_row"><?php _e('Average Grade', 'woothemes-sensei'); ?></th>
							</tr>
						</tfoot>
					</table>
					<div class="tablenav bottom">
						<div class="alignleft actions"></div>
						<div class="alignleft actions"></div>
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo sprintf( __( '%s items', 'woothemes-sensei' ), count( $users ) ); ?></span>
							<span class="pagination-links">
								<?php if ( 0 < $user_offset ) { ?>
									<a class="first-page disabled" title="Go to the first page" href="<?php echo esc_url( 'admin.php?page=sensei_analysis' ); ?>">«</a>
									<a class="prev-page" title="Go to the previous page" href="<?php echo esc_url( 'admin.php?page=sensei_analysis&user_offset=' . ( $user_offset - $user_length )  ); ?>">‹</a>
								<?php } ?>
								<span class="paging-input"><?php echo ( 1 + intval( ( $user_offset / $user_length ) ) ) ?> of <span class="total-pages"><?php echo ( 1 + intval( ( count( $users ) / $user_length ) ) ) ?></span></span>
								<?php if ( $user_length == $output_counter && ( count( $users ) > ( $user_offset + $user_length ) ) ) { ?>
									<a class="next-page" title="Go to the next page" href="<?php echo esc_url( 'admin.php?page=sensei_analysis&user_offset=' . ( $user_offset + $user_length )  ); ?>">›</a>
									<a class="last-page" title="Go to the last page" href="<?php echo esc_url( 'admin.php?page=sensei_analysis&user_offset=' . ( (  intval( ( count( $users ) / $user_length ) ) ) * $user_length )  ); ?>">»</a>
							</span><?php } ?>
						</div>

						<br class="clear">
					</div>
					
				</div>
			</div>
			
		</div><!--/#woothemes-sensei-->
		
		<?php do_action( 'analysis_after_container' ); ?>
		
	<?php 
	} // End analysis_page()
	
	/**
	 * enqueue_scripts function.
	 *
	 * @description Load in JavaScripts where necessary.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts () {
		global $woothemes_sensei;
		// None for now
		
	} // End enqueue_scripts()

	/**
	 * enqueue_styles function.
	 *
	 * @description Load in CSS styles where necessary.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		global $woothemes_sensei;
		wp_enqueue_style( $woothemes_sensei->token . '-admin' );

		wp_enqueue_style( 'woothemes-sensei-settings-api', $woothemes_sensei->plugin_url . 'assets/css/settings.css', '', '1.0.0' );

	} // End enqueue_styles()

	
} // End Class
?>