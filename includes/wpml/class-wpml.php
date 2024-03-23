<?php
/**
 * File containing the WPML class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPML
 *
 * Compatibility code with WPML.
 * This class instantiates and initiates the WPML compatibility classes.
 */
class WPML {
	/**
	 * Init compatibility classes.
	 */
	public function init() {
		( new Course_Progress() )->init();
		( new Course_Translation() )->init();
		( new Custom_Fields() )->init();
		( new Email() )->init();
		( new Language_Details() )->init();
		( new Lesson_Progress() )->init();
		( new Lesson_Translation() )->init();
		( new Quiz_Progress() )->init();
		( new Quiz_Submission() )->init();
		( new Page() )->init();
		( new Utils() )->init();
	}
}
