<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Administration Class
 *
 * All functionality pertaining to the administration sections of Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Administration
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - comments_admin_filter()
 * - install_page_output()
 * - create_page()
 * - create_pages()
 * - admin_styles_global()
 * - admin_install_notice()
 * - admin_notice_styles()
 *
 */
class WooThemes_Sensei_Admin {

	public $token;

	/**
	 * Constructor.
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct () {

		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_styles_global' ) );
		add_action( 'admin_print_styles', array( &$this, 'admin_notices_styles' ) );
		add_action( 'settings_before_form', array( &$this, 'install_pages_output' ) );
		add_filter( 'comments_clauses', array( &$this, 'comments_admin_filter' ), 10, 1 );

	} // End __construct()


	/**
	 * comments_admin_filter function.
	 *
	 * Filters the backend commenting system to not include the sensei prefixed comments
	 *
	 * @access public
	 * @param mixed $pieces
	 * @return void
	 */
	function comments_admin_filter( $pieces ) {

		// Filter Admin Comments Area to not display Sensei's use of commenting system
		if( is_admin() && current_user_can( 'moderate_comments' ) && !( isset($_GET['page']) && 'sensei_analysis' == $_GET['page'] ) ) {
			$pieces['where'] .= " AND comment_type NOT LIKE 'sensei_%' ";
		} // End If Statement

		return $pieces;

	} // End comments_admin_filter()


	/**
	 * install_pages_output function.
	 *
	 * Handles installation of the 2 pages needs for courses and my courses
	 *
	 * @access public
	 * @return void
	 */
	function install_pages_output() {
		global $woothemes_sensei;

		// Install/page installer
	    $install_complete = false;

	    // Add pages button
	    if (isset($_GET['install_sensei_pages']) && $_GET['install_sensei_pages']) {

			$this->create_pages();
	    	update_option('skip_install_sensei_pages', 1);
	    	$install_complete = true;

		// Skip button
	    } elseif (isset($_GET['skip_install_sensei_pages']) && $_GET['skip_install_sensei_pages']) {

	    	update_option('skip_install_sensei_pages', 1);
	    	$install_complete = true;

	    }

		if ($install_complete) {
			?>
	    	<div id="message" class="updated sensei-message sensei-connect">
				<div class="squeezer">
					<h4><?php _e( '<strong>Congratulations!</strong> &#8211; Sensei has been installed and setup. Enjoy :)', 'woothemes-sensei' ); ?></h4>
					<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/sensei/" data-text="A premium Learning Management plugin for #WordPress that helps you create courses. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="Sensei">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
				</div>
			</div>
			<?php

			// Flush rules after install
			flush_rewrite_rules( false );

			// Set installed option
			update_option('sensei_installed', 0);
		}

	} // End install_pages_output()


	/**
	 * create_page function.
	 *
	 * @access public
	 * @param mixed $slug
	 * @param mixed $option
	 * @param string $page_title (default: '')
	 * @param string $page_content (default: '')
	 * @param int $post_parent (default: 0)
	 * @return void
	 */
	function create_page( $slug, $option, $page_title = '', $page_content = '', $post_parent = 0 ) {
		global $wpdb;

		$option_value = get_option( $option );

		if ( $option_value > 0 && get_post( $option_value ) )
			return;

		$page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;", $slug ) );
		if ( $page_found ) :
			if ( ! $option_value )
				update_option( $option, $page_found );
			return;
		endif;

		$page_data = array(
	        'post_status' 		=> 'publish',
	        'post_type' 		=> 'page',
	        'post_author' 		=> 1,
	        'post_name' 		=> $slug,
	        'post_title' 		=> $page_title,
	        'post_content' 		=> $page_content,
	        'post_parent' 		=> $post_parent,
	        'comment_status' 	=> 'closed'
	    );
	    $page_id = wp_insert_post( $page_data );

	    update_option( $option, $page_id );
	} // End create_page()


	/**
	 * create_pages function.
	 *
	 * @access public
	 * @return void
	 */
	function create_pages() {

		// Courses page
	    $this->create_page( esc_sql( _x('courses-overview', 'page_slug', 'woothemes-sensei') ), $this->token . '_courses_page_id', __('Courses', 'woothemes-sensei'), '[newcourses][featuredcourses][freecourses][paidcourses]' );

		// User Dashboard page
	    $this->create_page( esc_sql( _x('my-courses', 'page_slug', 'woothemes-sensei') ), $this->token . '_user_dashboard_page_id', __('My Courses', 'woothemes-sensei'), '[usercourses]' );

	} // End create_pages()

	/**
	 * Load the global admin styles for the menu icon and the relevant page icon.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_styles_global () {
		global $woothemes_sensei;
		wp_register_style( $woothemes_sensei->token . '-global', $woothemes_sensei->plugin_url . 'assets/css/global.css', '', '1.3.4', 'screen' );
		wp_enqueue_style( $woothemes_sensei->token . '-global' );
		wp_register_style( $woothemes_sensei->token . '-chosen', $woothemes_sensei->plugin_url . 'assets/chosen/chosen.css', '', '1.3.0', 'screen' );
		wp_enqueue_style( $woothemes_sensei->token . '-chosen' );
	} // End admin_styles_global()


	/**
	 * admin_install_notice function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_install_notice() {
	    ?>
	    <div id="message" class="updated sensei-message sensei-connect">
	    	<div class="squeezer">
	    		<h4><?php _e( '<strong>Welcome to Sensei</strong> &#8211; You\'re almost ready to create some courses :)', 'woothemes-sensei' ); ?></h4>
	    		<p class="submit"><a href="<?php echo add_query_arg('install_sensei_pages', 'true', admin_url('edit.php?post_type=lesson&page=woothemes-sensei-settings')); ?>" class="button-primary"><?php _e( 'Install Sensei Pages', 'woothemes-sensei' ); ?></a> <a class="skip button" href="<?php echo add_query_arg('skip_install_sensei_pages', 'true', admin_url('edit.php?post_type=lesson&page=woothemes-sensei-settings')); ?>"><?php _e('Skip setup', 'woothemes-sensei'); ?></a></p>
	    	</div>
	    </div>
	    <?php
	} // End admin_install_notice()


	/**
	 * admin_installed_notice function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_installed_notice() {
	    ?>
	    <div id="message" class="updated sensei-message sensei-connect">
	    	<div class="squeezer">
	    		<h4><?php _e( '<strong>Sensei has been installed</strong> &#8211; You\'re ready to start creating courses :)', 'woothemes-sensei' ); ?></h4>

	    		<p class="submit"><a href="<?php echo admin_url('edit.php?post_type=lesson&page=woothemes-sensei-settings'); ?>" class="button-primary"><?php _e( 'Settings', 'woothemes-sensei' ); ?></a> <a class="docs button-primary" href="http://www.woothemes.com/sensei-docs/"><?php _e('Documentation', 'woothemes-sensei'); ?></a></p>

	    		<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/sensei/" data-text="A premium Learning Management plugin for #WordPress that helps you teach courses online. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="Sensei">Tweet</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
	    	</div>
	    </div>
	    <?php

	    // Set installed option
	    update_option('sensei_installed', 0);
	} // End admin_installed_notice()


	/**
	 * admin_notices_styles function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_notices_styles() {
		global $woothemes_sensei;
		// Installed notices
	    if ( get_option('sensei_installed')==1 ) {

	    	wp_enqueue_style( 'sensei-activation', plugins_url(  '/assets/css/activation.css', dirname( __FILE__ ) ) );

	    	if (get_option('skip_install_sensei_pages')!=1 && $woothemes_sensei->get_page_id('course')<1 && !isset($_GET['install_sensei_pages']) && !isset($_GET['skip_install_sensei_pages'])) {
	    		add_action( 'admin_notices', array( &$this, 'admin_install_notice' ) );
	    	} elseif ( !isset($_GET['page']) || $_GET['page']!='woothemes-sensei-settings' ) {
	    		add_action( 'admin_notices', array( &$this, 'admin_installed_notice' ) );
	    	} // End If Statement

	    } // End If Statement
	} // End admin_notices_styles()

} // End Class
?>