<?php
/**
 * Class Sensei__S
 *
 * Responsible for wrapping for the underscores theme
 * with the correct markup
 *
 *
 * @package Views
 * @subpackage Theme-Integration
 * @author Automattic
 *
 * @since 1.9.0
*/
Class Sensei__S {

    /**
     * Output opening wrappers
     * @since 1.9.0
     */
    public function wrapper_start(){ ?>
        <div id="primary" class="content-area">
            <main id="main" class="site-main" role="main">

    <?php }

    /**
     * Output closing wrappers
     *
     * @since 1.9.0
     */
    public function wrapper_end(){ ?>

            </main> <!-- main-site -->
          </div> <!-- content-area -->

	    <?php

        get_sidebar();

    }
} // end class
