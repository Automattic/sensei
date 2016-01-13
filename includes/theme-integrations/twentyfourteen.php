<?php
/**
 * Class Sensei_Twentyfourteen
 *
 * Responsible for wrapping twenty fourteen theme Sensei content
 * with the correct markup
 *
 * @package Views
 * @subpackage Theme-Integration
 * @author Automattic
 *
 * @since 1.9.0
*/
Class Sensei_Twentyfourteen {

    /**
     * Output opening wrappers
     * @since 1.9.0
     */
    public function wrapper_start(){
    ?>

        <div id="main-content" class="main-content">
            <div id="primary" class="content-area">
                <div id="content" class="site-content" role="main">
                    <div class="entry-content">

    <?php }

    /**
     * Output closing wrappers
     *
     * @since 1.9.0
     */
    public function wrapper_end(){ ?>


                    </div>
                </div>
            </div>
        </div>

        <?php
        get_sidebar();
	 }

}
