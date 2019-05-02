<?php
/**
 * Pagination - Lesson
 *
 * @author  Automattic
 * @package Sensei/Templates
 * @version 1.9.20
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
$nav_links   = sensei_get_prev_next_lessons( $quiz_lesson );

// Output HTML
if ( isset( $nav_links['previous'] ) || isset( $nav_links['next'] ) ) { ?>
	<nav id="post-entries" class="post-entries fix">
		<?php if ( isset( $nav_links['previous'] ) ) { ?>
			<div class="nav-prev fl">
				<a href="<?php echo esc_url( $nav_links['previous']['url'] ); ?>" rel="prev">
					<span class="meta-nav"></span>
					<?php echo esc_html( $nav_links['previous']['name'] ); ?>
				</a>
			</div>
		<?php } ?>

		<?php if ( isset( $nav_links['next'] ) ) { ?>
			<div class="nav-next fr">
				<a href="<?php echo esc_url( $nav_links['next']['url'] ); ?>" rel="next">
					<?php echo esc_html( $nav_links['next']['name'] ); ?>
					<span class="meta-nav"></span>
				</a>
			</div>
		<?php } ?>
	</nav><!-- #post-entries -->
<?php } ?>
