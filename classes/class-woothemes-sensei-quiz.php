<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Quiz Class
 *
 * All functionality pertaining to the quiz post type in Sensei.
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
 */
class WooThemes_Sensei_Quiz {
	public $token;
	public $meta_fields;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {
		$this->meta_fields = array( 'quiz_passmark', 'quiz_lesson', 'quiz_type', 'quiz_grade_type' );
	} // End __construct()

} // End Class
?>