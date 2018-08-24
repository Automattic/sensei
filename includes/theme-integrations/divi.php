<?php
/**
 * Class Sensei_Divi
 *
 * Responsible for wrapping for the Divi theme with the correct markup.
 *
 *
 * @package Views
 * @subpackage Theme-Integration
 * @author Automattic
 *
 * @since 1.9.0
*/
class Sensei_Divi {

	/**
	 * Output opening wrappers
	 * @since 1.12.0
	 */
	public function wrapper_start() {
		?>
		<div id="main-content">
			<div class="container">
				<div id="content-area" class="clearfix">
					<div id="left-area">
		<?php
	}

	/**
	 * Output closing wrappers
	 *
	 * @since 1.12.0
	 */
	public function wrapper_end() {
		?>
					</div> <!-- #left-area -->

					<?php get_sidebar(); ?>

				</div> <!-- #content-area -->
			</div> <!-- .container -->
		</div> <!-- #main-content -->
		<?php

		get_sidebar();

	}
} // end class
