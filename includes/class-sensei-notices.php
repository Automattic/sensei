<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Notices Class
 *
 * All functionality pertaining to displaying of various notices on the frontend.
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.6.3
 */
class Sensei_Notices{

	/**
	*  @var $notices
	*/
	protected $notices;

	/**
	 * @var boolean $has_printed
	 */
	protected $has_printed;

	/**
	*  constructor 
 	*/
	public function __construct(){
		//initialize the notices variable
		$this->notices = array();
		$this->has_printed = false;
	}

	/**
	 *  Add a notice to the array of notices for display at a later stage.
	 *
	 *
	 * @param string $content
	 * @param string $type defaults to alert options( alert, tick , download , info   )
	 *
	 * @return void
	 */

	public function add_notice( $content ,  $type = 'alert'   ){
		// append the new notice
		$this->notices[] = array('content' => $content , 'type'=> $type );

		// if a notice is added after we've printed print it immediately.
		if( $this->has_printed  ){
			$this->maybe_print_notices();
		}

	} // end add_notice()

	/**
	 * Output all notices added
	 *
	 * @return void
	 */

	public function maybe_print_notices(){
		if(  count( $this->notices ) > 0  ){
			foreach ($this->notices  as  $notice) {

				$classes = 'sensei-message '. $notice['type'];
				$html = '<div class="'. $classes . '">'. $notice['content'] . '</div>';

				echo $html; 
			}
			// empty the notice queue to avoid reprinting the same notices
			$this->clear_notices();

		}

		// set this to print immediately if notices are added after the notices were printed
		$this->has_printed = true;

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

/**
 * Class Woothemes_Sensei_Notices
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class Woothemes_Sensei_Notices extends Sensei_Notices{}
