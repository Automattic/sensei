<?php
/**
 * Pagination - Posts
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>
<nav id="post-entries" class="post-entries fix">
    <div class="sensei-nav-prev fl"><?php previous_post_link( '%link', '<span class="meta-nav"></span> %title' ); ?></div>
    <div class="sensei-nav-next fr"><?php next_post_link( '%link', '%title <span class="meta-nav"></span>' ); ?></div>
</nav><!-- #post-entries -->
