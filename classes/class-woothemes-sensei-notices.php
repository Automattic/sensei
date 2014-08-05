<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Notices Class
 *
 * All functionality pertaining to displaying of various notices accross.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.6.3
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - add_notice()
 * - print_notices()
 * - clear_notices()
 */

class Woothemes_Sensei_Notices{

	/**
	*  @var $notices
	*/
	protected $notices;

	/**
	*  constructor 
 	*/
	public function __construct(){
		//initialize the notices variable
		$this->notices = array();
	}

	/**
	*  Add a notice to the array of notices for display at a later stage.
	* 
	*
	* 
	* @param string $message 
	* @param string $type defaults to alert options( alert, tick , download , note   )
	*
	* @return void
	*/

	public function add_notice( $content ,  $type = 'alert'   ){
		// append the new notice
		$this->notices[] = array('content' => $content , 'type'=> $type );
	} // end add_notice()

	/**
	*  Output all notices added 
	* 
	* @param string $message 
	* @param string $type
	*
	* @return void
	*/

	public function print_notices(){
		if(  count( $this->notices ) > 0  ){
			foreach ($this->notices  as  $notice) {

				$classes = 'sensei-message '. $notice['type'];
				$html = '<div class="'. $classes . '">'. $notice['content'] . '</div>';

				echo $html; 
			}
			// empty the notice queue to avoid reprinting the same notices
			$this->clear_notices();
		}
	} // end print_notice()

	/**
	*  Clear all notices  
	* 
	* @return void
	*/

	public function clear_notices(){
		// assign an empty array to clear all existing notices
		$this->notices = array();
	} // end clear_notices()

} // end Woothemes_Sensei_Notices