<?php
/**
 * The template for displaying a module.
 *
 * Override this template by copying it to your_theme/sensei/taxonomy-module.php.
 *
 * @author    Automattic
 * @package   Sensei
 * @category  Templates
 * @version   1.9.20
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_sensei_header();
?>

	<?php
		/**
		 * Fires before rendering any markup on the module page.
		 *
		 * @since 1.9.20
		 */
		do_action( 'sensei_taxonomy_module_content_before' );
	?>

	<?php if ( have_posts() ) : ?>
			<section class="module-container" >
				<?php
					/**
					 * Fires before rendering the module content.
					 *
					 * @since 1.9.20
					 */
					do_action( 'sensei_taxonomy_module_content_inside_before' );
				?>

				<?php
					/**
					 * Fires after rendering the module content.
					 *
					 * @since 1.9.20
					 */
					do_action( 'sensei_taxonomy_module_content_inside_after' );
				?>
			</section>
	<?php endif; ?>

	<?php
		/**
		 * Fires after rendering all markup on the module page.
		 *
		 * @since 1.9.20
		 */
		do_action( 'sensei_taxonomy_module_content_after' );
	?>

<?php get_sensei_footer(); ?>
