<?php
/**
 * Class Sensei_Twentytwelve
 *
 * Responsible for wrapping twenty twelve theme Sensei content
 * with the correct markup
 *
 * @package Views
 * @subpackage Theme-Integration
 * @author Automattic
 *
 * @since 1.9.0
*/
Class Sensei_Twentytwelve {

    /**
     * Output opening wrappers
     * @since 1.9.0
     */
    public function wrapper_start(){
    ?>

        <div id="primary" class="site-content">
            <div id="content" role="main" class="entry-content">

    <?php }

    /**
     * Output closing wrappers
     *
     * @since 1.9.0
     */
    public function wrapper_end(){ ?>

			</div>
		</div>
		<?php get_sidebar(); ?>
	</div>

	<?php }

}
