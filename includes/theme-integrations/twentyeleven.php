<?php
/**
 * Class Sensei_Twentyeleven
 *
 * Responsible for wrapping twenty eleven theme Sensei content
 * with the correct markup
 *
 * @package Views
 * @subpackage Theme-Integration
 * @author Automattic
 *
 * @since 1.9.0
*/
Class Sensei_Twentyeleven {

    /**
     * Output opening wrappers
     * @since 1.9.0
     */
    public function wrapper_start(){
    ?>

        <div id="primary">
            <div id="content" role="main">

    <?php }

    /**
     * Output closing wrappers
     *
     * @since 1.9.0
     */
    public function wrapper_end(){ ?>

            </div>
        </div>

	<?php }

}
