<?php
/**
 * Pagination
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_query;
?>
<?php if ( $wp_query->max_num_pages > 1 ) : ?>

<div class="navigation">
	<div class="nav-next"><?php next_posts_link( __( 'Next <span class="meta-nav"></span>', 'woothemes-sensei' ) ); ?></div>
	<div class="nav-previous"><?php previous_posts_link( __( '<span class="meta-nav"></span> Previous', 'woothemes-sensei' ) ); ?></div>
</div>
<?php endif; ?>