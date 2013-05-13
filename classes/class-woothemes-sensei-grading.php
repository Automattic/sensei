<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading Class
 *
 * All functionality pertaining to the Admin Grading in Sensei.
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
 * - analysis_admin_menu()
 */
class WooThemes_Sensei_Grading {
	public $token;
	public $name;
	public $file;

	/**
	 * Constructor
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct ( $file ) {
		$this->name = 'Grading';
		$this->file = $file;
		// Admin functions
		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'analysis_admin_menu' ), 10);
		} // End If Statement
	} // End __construct()


	/**
	 * analysis_admin_menu function.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function analysis_admin_menu() {
	    global $menu, $woocommerce;

	    if ( current_user_can( 'manage_options' ) )

	    $analysis_page = add_submenu_page('edit.php?post_type=lesson', __('Grading', 'woothemes-sensei'),  __('Grading', 'woothemes-sensei') , 'manage_options', 'sensei_grading', array( &$this, 'grading_page' ) );

	} // End analysis_admin_menu()

	/**
	 * grading_page function.
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function grading_page() {
		global $woothemes_sensei;
		$this->grading_default_view();
	} // End analysis_page()

	/**
	 * grading_default_view default view for grading page
	 * @since  1.2.0
	 * @return void
	 */
	public function grading_default_view( $type = '' ) {
		global $woothemes_sensei;

		// Wrappers
		do_action( 'grading_before_container' );
		do_action( 'grading_wrapper_container', 'top' );
		?><div id="poststuff" class="sensei-grading-wrap">
				<div class="sensei-grading-sidebar">
				</div>
				<div class="sensei-grading-main">

				</div>
			</div>
		<?php
		do_action( 'grading_wrapper_container', 'bottom' );
		do_action( 'grading_after_container' );
	} // End grading_default_view()

} // End Class
?>