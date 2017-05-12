<?php
/**
 * Class Sensei_Twentyseventeen
 *
 * Responsible for wrapping twenty seventeen theme Sensei content
 * with the correct markup
 *
 * @package Views
 * @subpackage Theme-Integration
 * @author Automattic
 *
 * @since 1.9.10
 */
class Sensei_Twentyseventeen extends Sensei__S  {

	/**
     * Output opening wrappers
     * @since 1.9.15
     */
    public function wrapper_start() {
    ?>

        <div class="wrap">
			<div id="primary" class="content-area">
				<main id="main" class="site-main" role="main">

    <?php }

    /**
     * Output closing wrappers
     *
     * @since 1.9.15
     */
    public function wrapper_end() { ?>

				</main><!-- #main -->
			</div><!-- #primary -->
		</div><!-- .wrap -->

	<?php }

}
