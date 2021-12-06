<?php
/**
 * The Template for displaying all single quizzes when using Course Theme.
 *
 * Override this template by copying it to yourtheme/sensei/course-theme/single-quiz.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.13.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

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
