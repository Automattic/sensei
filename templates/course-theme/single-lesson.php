<?php
/**
 * The Template for displaying all single lessons when using Course Theme.
 *
 * Override this template by copying it to yourtheme/sensei/course-theme/single-lesson.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.13.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( have_posts() ) {
	the_post();
}
?>

<!-- wp:group {"className":"sensei-course-theme__header"} -->
<div class="wp-block-group sensei-course-theme__header">
	<!-- wp:paragraph -->
	<p>Header here</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:columns -->
<div class="wp-block-columns">
	<!-- wp:column {"width":"33.33%","className":"sensei-course-theme__sidebar"} -->
	<div class="wp-block-column sensei-course-theme__sidebar" style="flex-basis:33.33%">
		<!-- wp:paragraph -->
		<p>Sidebar</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"66.66%","className":"sensei-course-theme__main-content"} -->
	<div class="wp-block-column sensei-course-theme__main-content" style="flex-basis:66.66%">
		<!-- wp:html -->
		<?php
		if ( sensei_can_user_view_lesson() ) {
			the_content();
		} else {
			?>
			<p>
				<?php echo wp_kses_post( get_the_excerpt() ); ?>
			</p>
			<?php
		}
		?>
		<!-- /wp:html -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
