<?php
/**
 * The Template for displaying all single lessons when using Course Theme.
 *
 * Override this template by copying it to yourtheme/sensei/course-theme/single-lesson.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( have_posts() ) {
	the_post();
}

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
