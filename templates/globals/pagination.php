<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Pagination - Show numbered pagination for sensei archives
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
// Exit if accessed directly

global $wp_query;

if ( $wp_query->max_num_pages <= 1 ) {
    return;
}

?>
<nav class="sensei-pagination">
    <?php
    echo paginate_links( apply_filters( 'sensei_pagination_args', array(
        'base'         => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
        'format'       => '',
        'add_args'     => '',
        'current'      => max( 1, get_query_var( 'paged' ) ),
        'total'        => $wp_query->max_num_pages,
        'prev_text'    => '&larr;',
        'next_text'    => '&rarr;',
        'type'         => 'list',
        'end_size'     => 3,
        'mid_size'     => 3
    ) ) );
    ?>
</nav>
