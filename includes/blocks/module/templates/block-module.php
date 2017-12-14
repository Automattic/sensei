<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Display a course module.
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     1.10.0
 */
?>

<?php
	/**
	 * Hook runs inside single-course/course-modules.php.
	 *
	 * It runs before the modules are shown. This hook fires on the single course page.
	 * It will show irrespective of whether or not the course has any modules.
	 *
	 * @since 1.10.0
	 *
	 */
	do_action( 'sensei_single_course_modules_before' );
?>

<article class="module">
	<?php
		/**
		 * Hook runs inside single-course/course-modules.php.
		 *
		 * It runs inside the if statement after the article tag opens just before the modules are shown.
		 * This hook will NOT fire if there are no modules to show.
		 *
		 * @since 1.9.0
		 * @since 1.9.7 Added the module ID to the parameters.
		 *
		 * @hooked Sensei()->modules->course_modules_title - 20
		 *
		 * @param int get_module_id() Module ID.
		 */
		do_action( 'sensei_single_course_modules_inside_before', $this->get_module_id() );
	?>

	<header>
		<h2>
			<a
				href="<?php echo esc_url_raw( $this->get_module_url() ); ?>"
				title="<?php echo esc_attr( $this->get_module_title() );?>">
				<?php echo esc_html( $this->get_module_title() ); ?>
			</a>
		</h2>
	</header>

	<section class="entry">
		<p class="module-description">
			<?php echo esc_html( $this->get_module_description() ); ?>
		</p>
	</section>

	<?php
		/**
		 * Hook runs inside single-course/course-modules.php.
		 *
		 * It runs before the closing article tag directly after the modules.
		 * This hook will not trigger if there are no modules to show.
		 *
		 * @since 1.9.0
		 * @since 1.9.7 Added the module ID to the parameters.
		 *
		 * @param int sensei_get_the_module_id() Module ID.
		 */
		do_action( 'sensei_single_course_modules_inside_after', $this->get_module_id() );
	?>

</article>

<?php
	/**
	 * Hook runs inside single-course/course-modules.php
	 *
	 * It runs after the modules are shown. This hook fires on the single course page, but only if the course has modules.
	 *
	 * @since 1.10.0
	 */
	do_action('sensei_single_course_modules_after');
?>
